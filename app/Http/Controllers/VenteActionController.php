<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Historiquetrans;
use App\Models\Projet;
use App\Models\ProjetAction;
use App\Models\VenteAction;
use App\Models\VenteActionAchat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Revente d'actions EMOTION entre actionnaires.
 *
 * 1) Le vendeur met N actions d'une de ses lignes projet_actions en vente
 *    (prix unitaire imposé = projets.price_action).
 * 2) L'acheteur consulte les offres, réserve une quantité (achat partiel
 *    possible) puis paie avec le flux Payments existant : transaction
 *    'achat_action_revente', param8 = id de la réservation, param9 = nombre.
 * 3) Dès que la transaction est payée (callback AM / Pvit, paiement GamPay
 *    ou balayage traiterAchatsRevente) : transfert des actions du vendeur
 *    vers l'acheteur et crédit du solde GamPay du vendeur.
 */
class VenteActionController extends Controller
{
    /** Durée de validité d'une réservation non payée (minutes). */
    const DUREE_RESERVATION = 30;

    /** États d'une transaction considérée comme payée. */
    const ETATS_PAYES = ['atraiter', 'en_attente', 'CONFIRMEE', 'succes'];

    /** États d'une transaction définitivement en échec. */
    const ETATS_ECHEC = ['annule', 'echec', 'rembourse'];

    /* ------------------------------------------------------------------ */
    /*  Calculs de disponibilité                                          */
    /* ------------------------------------------------------------------ */

    /** Actions d'une offre encore achetables (hors vendues et réservées). */
    private function disponibleOffre(VenteAction $vente, $exceptReservation = null)
    {
        $reserve = VenteActionAchat::where('vente_action', $vente->id)
            ->where('statut', 'reserve')
            ->where('created_at', '>=', now()->subMinutes(self::DUREE_RESERVATION))
            ->when($exceptReservation, function ($q) use ($exceptReservation) {
                $q->where('id', '!=', $exceptReservation);
            })
            ->sum('nbr_action');

        return max(0, intval($vente->nbr_action) - intval($vente->nbr_action_vendu) - intval($reserve));
    }

    /** Réservation annulée ou expirée : sa quantité a pu être reprise par un autre acheteur. */
    private function reservationExpiree(VenteActionAchat $achat)
    {
        return $achat->statut == 'annule'
            || $achat->created_at < now()->subMinutes(self::DUREE_RESERVATION);
    }

    /** Actions d'une ligne projet_actions pouvant encore être mises en vente. */
    private function vendableLigne(ProjetAction $ligne)
    {
        $enVente = VenteAction::where('projet_action_id', $ligne->id)
            ->where('statut', 'en_vente')
            ->get()
            ->sum(function ($v) {
                return intval($v->nbr_action) - intval($v->nbr_action_vendu);
            });

        return max(0, intval($ligne->customer_nbr_action) - intval($enVente));
    }

    private function nomProjet($projetId, $ligne = null)
    {
        $projet = Projet::find($projetId);
        if ($projet && !empty($projet->nom)) return $projet->nom;
        return $ligne ? ($ligne->param2 ?? 'Projet') : 'Projet';
    }

    /** Pourcentage détenu, recalculé seulement si le projet connaît son nombre d'actions. */
    private function pourcentage($nbr, $projet)
    {
        if ($projet && intval($projet->nbr_action) > 0) {
            return round(intval($nbr) * 100 / intval($projet->nbr_action), 4);
        }
        return null;
    }

    /* ------------------------------------------------------------------ */
    /*  Côté vendeur                                                       */
    /* ------------------------------------------------------------------ */

    /** Lignes d'actions du client avec la quantité encore vendable. */
    public function getMesActionsVendables(Request $request)
    {
        try {
            $lignes = ProjetAction::where('customer', $request->id)
                ->where('customer_nbr_action', '>', 0)
                ->orderBy('id', 'desc')
                ->get();

            $body = [];
            foreach ($lignes as $ligne) {
                $projet = Projet::find($ligne->projet_action);
                $body[] = [
                    'projet_action_id' => $ligne->id,
                    'projet' => $ligne->projet_action,
                    'projet_nom' => $this->nomProjet($ligne->projet_action, $ligne),
                    'categorie' => $ligne->param1,
                    'customer_nbr_action' => intval($ligne->customer_nbr_action),
                    'vendable' => $this->vendableLigne($ligne),
                    'price_action' => $projet ? intval($projet->price_action) : null,
                ];
            }

            return response()->json(['statut' => true, 'body' => $body]);
        } catch (\Throwable $th) {
            return response()->json(['statut' => false, 'body' => null, 'message' => 'Une erreur s\'est produite']);
        }
    }

    public function mettreActionsEnVente(Request $request)
    {
        try {
            $nbr = intval($request->nbr_action);
            if ($nbr <= 0) {
                return response()->json(['statut' => false, 'message' => 'Entrez un nombre d\'actions valide']);
            }

            return DB::transaction(function () use ($request, $nbr) {
                $ligne = ProjetAction::where('id', $request->projet_action_id)
                    ->where('customer', $request->id)
                    ->lockForUpdate()
                    ->first();
                if (!$ligne) {
                    return response()->json(['statut' => false, 'message' => 'Actions introuvables']);
                }

                $vendable = $this->vendableLigne($ligne);
                if ($nbr > $vendable) {
                    return response()->json([
                        'statut' => false,
                        'message' => "Vous ne pouvez mettre en vente que $vendable action(s)"
                    ]);
                }

                $projet = Projet::find($ligne->projet_action);
                if (!$projet || intval($projet->price_action) <= 0) {
                    return response()->json(['statut' => false, 'message' => 'Prix de l\'action introuvable']);
                }

                $customer = Customer::find($request->id);
                $vente = new VenteAction([
                    'vendeur' => $request->id,
                    'vendeur_phone' => $customer ? $customer->phoneclient : null,
                    'projet_action_id' => $ligne->id,
                    'projet' => $ligne->projet_action,
                    'nbr_action' => $nbr,
                    'nbr_action_vendu' => 0,
                    'price_action' => intval($projet->price_action),
                    'statut' => 'en_vente',
                ]);
                $vente->save();

                return response()->json([
                    'statut' => true,
                    'message' => "$nbr action(s) mise(s) en vente",
                    'body' => $vente
                ]);
            });
        } catch (\Exception $th) {
            return response()->json(['statut' => false, 'message' => 'Une erreur s\'est produite']);
        }
    }

    public function annulerVenteAction(Request $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $vente = VenteAction::where('id', $request->vente_action)
                    ->where('vendeur', $request->id)
                    ->where('statut', 'en_vente')
                    ->lockForUpdate()
                    ->first();
                if (!$vente) {
                    return response()->json(['statut' => false, 'message' => 'Offre introuvable']);
                }

                $reservations = VenteActionAchat::where('vente_action', $vente->id)
                    ->where('statut', 'reserve')
                    ->where('created_at', '>=', now()->subMinutes(self::DUREE_RESERVATION))
                    ->count();
                if ($reservations > 0) {
                    return response()->json([
                        'statut' => false,
                        'message' => 'Un achat est en cours sur cette offre, réessayez dans quelques minutes'
                    ]);
                }

                // Les actions déjà vendues restent vendues : l'offre est simplement close.
                $vente->update(['statut' => intval($vente->nbr_action_vendu) > 0 ? 'vendu' : 'annule']);

                return response()->json(['statut' => true, 'message' => 'Offre retirée de la vente']);
            });
        } catch (\Exception $th) {
            return response()->json(['statut' => false, 'message' => 'Une erreur s\'est produite']);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Consultation                                                       */
    /* ------------------------------------------------------------------ */

    /**
     * body.offres     : offres des autres actionnaires encore disponibles
     * body.mes_ventes : offres en cours du client
     */
    public function getActionsEnVente(Request $request)
    {
        try {
            // Rattrape les paiements confirmés non encore traités.
            $this->traiterAchatsReventeIn();

            $ventes = VenteAction::where('statut', 'en_vente')->orderBy('id', 'desc')->get();
            $offres = [];
            $mesVentes = [];

            foreach ($ventes as $vente) {
                $vendeur = Customer::find($vente->vendeur);
                $nomVendeur = $vendeur
                    ? trim(($vendeur->prenom ?? '') . ' ' . mb_substr($vendeur->nom ?? '', 0, 1) . '.')
                    : 'Actionnaire';

                $item = [
                    'id' => $vente->id,
                    'projet' => $vente->projet,
                    'projet_nom' => $this->nomProjet($vente->projet),
                    'price_action' => intval($vente->price_action),
                    'nbr_action' => intval($vente->nbr_action),
                    'nbr_action_vendu' => intval($vente->nbr_action_vendu),
                    'disponible' => $this->disponibleOffre($vente),
                    'vendeur_nom' => $nomVendeur,
                    'created_at' => $vente->created_at,
                ];

                if (strval($vente->vendeur) == strval($request->id)) {
                    $mesVentes[] = $item;
                } elseif ($item['disponible'] > 0) {
                    $offres[] = $item;
                }
            }

            return response()->json([
                'statut' => true,
                'body' => ['offres' => $offres, 'mes_ventes' => $mesVentes]
            ]);
        } catch (\Exception $th) {
            return response()->json(['statut' => false, 'body' => null, 'message' => 'Une erreur s\'est produite']);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Côté acheteur                                                      */
    /* ------------------------------------------------------------------ */

    /** Bloque la quantité choisie le temps du paiement. */
    public function reserverAchatAction(Request $request)
    {
        try {
            $nbr = intval($request->nbr_action);
            if ($nbr <= 0) {
                return response()->json(['statut' => false, 'message' => 'Entrez un nombre d\'actions valide']);
            }

            return DB::transaction(function () use ($request, $nbr) {
                $vente = VenteAction::where('id', $request->vente_action)
                    ->where('statut', 'en_vente')
                    ->lockForUpdate()
                    ->first();
                if (!$vente) {
                    return response()->json(['statut' => false, 'message' => 'Cette offre n\'est plus disponible']);
                }
                if (strval($vente->vendeur) == strval($request->id)) {
                    return response()->json(['statut' => false, 'message' => 'Vous ne pouvez pas acheter vos propres actions']);
                }

                // Une seule réservation active par acheteur et par offre.
                VenteActionAchat::where('vente_action', $vente->id)
                    ->where('acheteur', $request->id)
                    ->where('statut', 'reserve')
                    ->whereNull('id_trans')
                    ->update(['statut' => 'annule']);

                $disponible = $this->disponibleOffre($vente);
                if ($nbr > $disponible) {
                    return response()->json([
                        'statut' => false,
                        'message' => $disponible > 0
                            ? "Seulement $disponible action(s) disponible(s)"
                            : 'Cette offre n\'est plus disponible'
                    ]);
                }

                $achat = new VenteActionAchat([
                    'vente_action' => $vente->id,
                    'acheteur' => $request->id,
                    'acheteur_phone' => $request->phone,
                    'nbr_action' => $nbr,
                    'price_action' => intval($vente->price_action),
                    'montant' => $nbr * intval($vente->price_action),
                    'statut' => 'reserve',
                ]);
                $achat->save();

                return response()->json([
                    'statut' => true,
                    'body' => [
                        'reservation' => $achat->id,
                        'vente_action' => $vente->id,
                        'projet_nom' => $this->nomProjet($vente->projet),
                        'nbr_action' => $nbr,
                        'price_action' => intval($vente->price_action),
                        'montant' => $achat->montant,
                        'expire_minutes' => self::DUREE_RESERVATION,
                    ]
                ]);
            });
        } catch (\Exception $th) {
            return response()->json(['statut' => false, 'message' => 'Une erreur s\'est produite']);
        }
    }

    /** L'acheteur abandonne avant de payer. */
    public function annulerReservationAction(Request $request)
    {
        try {
            VenteActionAchat::where('id', $request->reservation)
                ->where('acheteur', $request->id)
                ->where('statut', 'reserve')
                ->whereNull('id_trans')
                ->update(['statut' => 'annule']);
            return response()->json(['statut' => true]);
        } catch (\Exception $th) {
            return response()->json(['statut' => false]);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Finalisation après paiement                                        */
    /* ------------------------------------------------------------------ */

    /** Route manuelle / externe : {id_trans} (historiquetrans.id). */
    public function validerAchatActionRevente(Request $request)
    {
        $trans = Historiquetrans::find($request->id_trans);
        if (!$trans) {
            return response()->json(['statut' => false, 'message' => 'Transaction introuvable']);
        }
        $resultat = $this->finaliserDepuisTransaction($trans);
        return response()->json(['statut' => $resultat === 'ok', 'message' => $resultat]);
    }

    /** Route de balayage (cron possible). */
    public function traiterAchatsRevente(Request $request)
    {
        return response()->json(['statut' => true, 'body' => $this->traiterAchatsReventeIn()]);
    }

    /** Finalise toutes les transactions 'achat_action_revente' payées et non traitées. */
    public function traiterAchatsReventeIn()
    {
        $resultats = [];
        try {
            $transactions = Historiquetrans::where('operation', 'achat_action_revente')
                ->whereIn('etat', self::ETATS_PAYES)
                ->where('etat', '!=', 'CONFIRMEE')
                ->where('created_at', '>=', now()->subDays(7))
                ->get();
            foreach ($transactions as $trans) {
                $resultats[$trans->id] = $this->finaliserDepuisTransaction($trans);
            }

            // Paiements en échec : la réservation est libérée.
            $echecs = Historiquetrans::where('operation', 'achat_action_revente')
                ->whereIn('etat', self::ETATS_ECHEC)
                ->where('created_at', '>=', now()->subDays(7))
                ->pluck('param8');
            if (count($echecs) > 0) {
                VenteActionAchat::whereIn('id', $echecs)->where('statut', 'reserve')->update(['statut' => 'annule']);
            }
        } catch (\Exception $th) {
            $resultats['erreur'] = $th->getMessage();
        }
        return $resultats;
    }

    /**
     * Transfère les actions et paie le vendeur pour une transaction payée.
     * Idempotent : un second appel sur une réservation déjà payée ne fait rien.
     * Retour : 'ok' | 'deja_traite' | 'non_paye' | 'a_rembourser' | message d'erreur
     */
    public function finaliserDepuisTransaction($trans)
    {
        if (!$trans || $trans->operation != 'achat_action_revente') return 'operation_invalide';
        if (!in_array($trans->etat, self::ETATS_PAYES)) return 'non_paye';

        try {
            $resultat = DB::transaction(function () use ($trans) {
                $achat = VenteActionAchat::where('id', $trans->param8)->lockForUpdate()->first();
                if (!$achat) return 'reservation_introuvable';
                if ($achat->statut == 'paye') return 'deja_traite';
                if ($achat->statut == 'a_rembourser') return 'a_rembourser';
                if (strval($achat->acheteur) != strval($trans->id_customer)) return 'acheteur_invalide';

                $vente = VenteAction::where('id', $achat->vente_action)->lockForUpdate()->first();
                $ligneVendeur = $vente
                    ? ProjetAction::where('id', $vente->projet_action_id)->lockForUpdate()->first()
                    : null;

                $nbr = intval($achat->nbr_action);
                $resteOffre = $vente ? intval($vente->nbr_action) - intval($vente->nbr_action_vendu) : 0;

                // Payé mais plus rien à livrer (offre close, réservation expirée revendue…)
                if (!$vente || !$ligneVendeur || $resteOffre < $nbr
                    || intval($ligneVendeur->customer_nbr_action) < $nbr
                    || ($this->reservationExpiree($achat) && $this->disponibleOffre($vente, $achat->id) < $nbr)) {
                    $achat->update(['statut' => 'a_rembourser', 'id_trans' => $trans->id]);
                    return 'a_rembourser';
                }

                $projet = Projet::find($vente->projet);

                // 1) Le vendeur cède ses actions
                $resteVendeur = intval($ligneVendeur->customer_nbr_action) - $nbr;
                $majVendeur = ['customer_nbr_action' => $resteVendeur];
                $pVendeur = $this->pourcentage($resteVendeur, $projet);
                if ($pVendeur !== null) $majVendeur['pourcentage_action'] = $pVendeur;
                $ligneVendeur->update($majVendeur);

                // 2) L'acheteur les reçoit (ligne existante du même projet ou nouvelle)
                $ligneAcheteur = ProjetAction::where('customer', $achat->acheteur)
                    ->where('projet_action', $vente->projet)
                    ->lockForUpdate()
                    ->first();
                if ($ligneAcheteur) {
                    $total = intval($ligneAcheteur->customer_nbr_action) + $nbr;
                    $majAcheteur = ['customer_nbr_action' => $total];
                    $pAcheteur = $this->pourcentage($total, $projet);
                    if ($pAcheteur !== null) $majAcheteur['pourcentage_action'] = $pAcheteur;
                    $ligneAcheteur->update($majAcheteur);
                } else {
                    $pAcheteur = $this->pourcentage($nbr, $projet);
                    $ligneAcheteur = new ProjetAction([
                        'projet_action' => $vente->projet,
                        'customer' => $achat->acheteur,
                        'customer_nbr_action' => $nbr,
                        'recette_day_action' => 0,
                        'recette_month_action' => 0,
                        'recette_year_action' => 0,
                        'delay_action' => $ligneVendeur->delay_action,
                        'pourcentage_action' => $pAcheteur ?? 0,
                        'param1' => $ligneVendeur->param1,
                        'param2' => $ligneVendeur->param2,
                        'param3' => 0,
                        'param4' => 0,
                        'param5' => 0,
                    ]);
                    $ligneAcheteur->save();
                }

                // 3) Offre mise à jour
                $vendu = intval($vente->nbr_action_vendu) + $nbr;
                $vente->update([
                    'nbr_action_vendu' => $vendu,
                    'statut' => $vendu >= intval($vente->nbr_action) ? 'vendu' : $vente->statut,
                ]);

                // 4) Le vendeur est payé sur son solde GamPay (montant hors frais)
                $vendeur = Customer::where('id', $vente->vendeur)->lockForUpdate()->first();
                if ($vendeur) {
                    $ancienSolde = doubleval($vendeur->solde);
                    $vendeur->update(['solde' => strval($ancienSolde + intval($achat->montant))]);

                    (new Historiquetrans([
                        'operation' => 'vente_action',
                        'reference' => 'VA' . $achat->id,
                        'etat' => 'CONFIRMEE',
                        'numclient' => $vendeur->phoneclient,
                        'phonevendeur' => $vendeur->phoneclient,
                        'content' => 'GAM',
                        'montant' => strval($achat->montant),
                        'montant_sans_frais' => strval($achat->montant),
                        'frais' => '0',
                        'solde' => strval($ancienSolde),
                        'origine_operation' => 'revente_action',
                        'id_customer' => $vendeur->id,
                        'param8' => strval($achat->id),
                        'param9' => strval($nbr),
                    ]))->save();
                }

                $achat->update(['statut' => 'paye', 'id_trans' => $trans->id, 'paye_at' => now()]);
                $trans->update(['etat' => 'CONFIRMEE']);
                return 'ok';
            });

            if ($resultat === 'ok') {
                // Mise à jour du message dans la conversation (Firebase)
                try {
                    app(TransactionsController::class)->updateTransFirebaseIn($trans->fresh());
                } catch (\Exception $e) {}
            }

            return $resultat;
        } catch (\Exception $th) {
            return 'erreur : ' . $th->getMessage();
        }
    }
}
