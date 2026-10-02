<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ConfigSim extends Model
{
    protected $table = 'config_sims';

    protected $fillable = [
        'customer_id', 'slot', 'number',
        'operateur_credit', 'operateur_transfert', 'operateur',
        'credit',
        'call_time', 'call_time_recharge', 'call_time_change',
        'forfait', 'forfait_recharge', 'forfait_change',
        'forfait_last_verify_at',
    ];

    // ============================================================
    // NUMÉROS
    // ============================================================

    /**
     * Variantes d'un numéro : avec / sans 0 initial, sans indicatif 241.
     * Les numéros arrivent sous plusieurs formes selon la source
     * (config app, USSD, transactions).
     */
    public static function variantesNumero($numero)
    {
        $n = preg_replace('/\D/', '', (string) $numero);
        if (strlen($n) > 9 && str_starts_with($n, '241')) {
            $n = substr($n, 3);
        }
        if ($n === '') {
            return [];
        }
        return array_values(array_unique([$n, '0' . $n, ltrim($n, '0')]));
    }

    /** Ligne SIM correspondant à un numéro (la plus récente). */
    public static function trouverParNumero($numero)
    {
        $variantes = self::variantesNumero($numero);
        if (empty($variantes)) {
            return null;
        }
        return self::whereIn('number', $variantes)->orderBy('updated_at', 'desc')->first();
    }

    public static function memeNumero($a, $b)
    {
        return count(array_intersect(self::variantesNumero($a), self::variantesNumero($b))) > 0;
    }

    // ============================================================
    // SYNCHRONISATION AVEC LA CONFIG DE L'APP
    // ============================================================

    /** Lignes SIM du client, par slot. */
    public static function actives($customerId)
    {
        return self::where('customer_id', $customerId)->orderBy('slot')->get();
    }

    /**
     * Enregistre les SIM du client à partir de la config envoyée par l'app
     * (registerConfigSims). Une ligne = une SIM, identifiée par son numéro :
     *  - SIM déjà connue (même numéro, chez ce client ou un autre) : on met
     *    à jour customer_id, slot et opérateurs ; ses compteurs sont gardés ;
     *  - SIM inconnue : nouvelle ligne, compteurs à 0 ;
     *  - SIM du client absente de la nouvelle config : détachée
     *    (customer_id = 0), jamais supprimée.
     */

    public static function synchroniser($customerId, $data)
    {
        $idsGardes = [];

        foreach (($data ?? []) as $item) {
            if (!isset($item['slot']) || empty($item['number'])) {
                continue;
            }
            $numero = preg_replace('/\D/', '', (string) $item['number']);
            $infos = [
                'customer_id' => $customerId,
                'slot' => intval($item['slot']),
                'number' => $numero,
                'operateur_credit' => $item['operateur_credit'] ?? null,
                'operateur_transfert' => $item['operateur_transfert'] ?? null,
                'operateur' => $item['operateur'] ?? null,
            ];

            $sim = self::whereIn('number', self::variantesNumero($numero))
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($sim) {
                $sim->update($infos);
            } else {
                $sim = self::create($infos);
            }
            $idsGardes[] = $sim->id;
        }

        // SIM qui ne font plus partie de la config du client : détachées
        self::where('customer_id', $customerId)
            ->whereNotIn('id', $idsGardes)
            ->update(['customer_id' => 0]);

        return self::actives($customerId);
    }

    // ============================================================
    // REPRISE DU TEMPS D'APPEL / FORFAIT DU WALLET
    // ============================================================

    /**
     * Passage au suivi par SIM (une seule fois par client, drapeau
     * wallet.suivi_sim) : le temps d'appel et le forfait du wallet sont
     * TRANSFÉRÉS sur la SIM de l'opérateur du wallet (sinon la première).
     * Rien n'est perdu, rien n'est compté deux fois.
     *
     * $reprise = ['call_time' => ..., 'forfait' => ...] : valeurs actuelles
     * envoyées par l'app (plus à jour que le serveur). À défaut, celles du
     * wallet. Les recharges en attente du wallet s'y ajoutent.
     *
     * Retourne true si le client est (désormais) en suivi par SIM.
     */

    public static function reprendreSoldeWallet($customerId, $reprise = null)
    {
        $wallet = AssistanceDataCredit::where('customer_id', $customerId)->first();
        if (!$wallet) {
            return true; // pas de wallet : rien à reprendre
        }
        if (intval($wallet->suivi_sim) === 1) {
            return true; // déjà fait
        }

        $sims = self::actives($customerId);
        if ($sims->isEmpty()) {
            return false; // pas encore de SIM : on reprendra plus tard
        }

        $op = self::operateurSimple($wallet->operateur);
        $cible = $sims->first(fn ($s) => self::operateurSimple($s->operateur_credit) === $op) ?? $sims->first();

        $minutes = is_array($reprise) && is_numeric($reprise['call_time'] ?? null)
            ? floatval($reprise['call_time'])
            : floatval($wallet->call_time);
        $mega = is_array($reprise) && is_numeric($reprise['forfait'] ?? null)
            ? floatval($reprise['forfait'])
            : floatval($wallet->forfait_qte);

        // Recharges du wallet pas encore récupérées par l'app
        $minutes += floatval($wallet->call_time_recharge);
        $mega += floatval($wallet->forfait_qte_recharge);

        $minutes = max(0, round($minutes, 2));
        $mega = max(0, round($mega, 3));

        DB::transaction(function () use ($wallet, $cible, $minutes, $mega) {
            // Ajouté en "recharge" : l'app l'applique et l'acquitte comme
            // n'importe quel ravitaillement (même si la SIM a déjà un solde).
            if ($minutes > 0 || $mega > 0) {
                self::where('id', $cible->id)->update([
                    'call_time_recharge' => DB::raw('call_time_recharge + ' . number_format($minutes, 2, '.', '')),
                    'forfait_recharge' => DB::raw('forfait_recharge + ' . number_format($mega, 3, '.', '')),
                    'updated_at' => now(),
                ]);
            }
            // Transféré : le wallet ne suit plus le temps / forfait.
            $wallet->update([
                'suivi_sim' => 1,
                'call_time' => 0, 'call_time_recharge' => 0, 'call_time_change' => 0,
                'forfait_qte' => 0, 'forfait_qte_recharge' => 0, 'forfait_qte_change' => 0,
            ]);
        });
        return true;
    }

    /**
     * true si les recharges de ce client doivent aller sur ses SIM
     * (client passé au suivi par SIM, ou sans wallet).
     */
    public static function clientSuiviParSim($customerId)
    {
        $wallet = AssistanceDataCredit::where('customer_id', $customerId)->first();
        return !$wallet || intval($wallet->suivi_sim) === 1;
    }

    private static function operateurSimple($operateur)
    {
        $o = strtolower((string) $operateur);
        if (str_contains($o, 'airtel')) {
            return 'airtel';
        }
        if (str_contains($o, 'moov') || str_contains($o, 'libertis')) {
            return 'moov';
        }
        return $o;
    }

    /**
     * Lignes SIM du client. Si le client n'en a pas encore mais possède une
     * ancienne config (customer.config_sims), elle est reprise (compteurs à 0).
     */
    public static function pourCustomer($customer)
    {
        $sims = self::actives($customer->id);
        if ($sims->isEmpty() && !empty($customer->config_sims)) {
            $data = json_decode($customer->config_sims, true);
            if (is_array($data) && count($data) > 0) {
                $sims = self::synchroniser($customer->id, $data);
            }
        }
        return $sims;
    }

    // ============================================================
    // RAVITAILLEMENT
    // ============================================================

    /**
     * Ajoute du temps d'appel (min) et / ou du forfait (Mo) à récupérer
     * par l'app. Mise à jour atomique (pas de perte si deux recharges
     * arrivent en même temps).
     */
    public static function ajouterRecharge($numero, $minutes, $mega)
    {
        $sim = self::trouverParNumero($numero);
        if (!$sim) {
            return null;
        }
        $minutes = round(floatval($minutes), 2);
        $mega = round(floatval($mega), 3);
        if ($minutes <= 0 && $mega <= 0) {
            return $sim;
        }
        self::where('id', $sim->id)->update([
            'call_time_recharge' => DB::raw('call_time_recharge + ' . number_format(max(0, $minutes), 2, '.', '')),
            'forfait_recharge' => DB::raw('forfait_recharge + ' . number_format(max(0, $mega), 3, '.', '')),
            'updated_at' => now(),
        ]);
        return $sim->fresh();
    }
}
