<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DetatilsOperation;
use App\Models\GamRechargeHist;
use App\Models\Historiquetrans;
use App\Models\AssistanceDataCredit;
use App\Models\ConfigSim;
use App\Models\GeneralController;
use App\Models\ForfaitDataCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class AgentController extends Controller
{
    /**
     * Plus petit forfait internet fourni par chaque opérateur.
     * Utilisé par dataAssistance quand le solde ne peut payer aucun des
     * forfaits "Forfait" disponibles (dépannage internet).
     */
    private $petitForfaitData = [
        'airtel' => ['price' => 150, 'qte' => '20', 'validity' => null],   // 150 F = 20 Mo
        'moov' => ['price' => 100, 'qte' => '100', 'validity' => '1H'],    // Libertis : 100 F = 100 Mo / 1 h
    ];

    public function verifTrans()
    {
        try {
            $general = new GeneralController();
            $start = 384165650;
            $now = date('Y-m-d H:i:s');
            $timeAlerte = date('Y-m-d H:i:s', strtotime("$now -3 minutes"));
            //return $timeAlerte;
            $trans = DB::select("SELECT * from historiquetrans h where CAST(h.montant_sans_frais as integer) <= 2000 and h.operation in ('rendu_monnaie_simple', 'achat_credit', 'transfert_mobile', 'transfert_visa') and h.etat not in ('CONFIRMEE', 'atraiter', 'attend') and (h.code_validation not in ('verif', 'traite', 'alerte', 'assistance', 'error_traite') or h.code_validation is null) and id >$start and h.updated_at < '" . $timeAlerte . "' order by id desc limit 5");
            if (count($trans) > 0) {
                foreach ($trans as $tran) {
                    $content = $tran->content;
                    $operation = $tran->operation;
                    $etat = $tran->etat;
                    try {
                        $reload = true;
                        switch ($operation) {
                            case 'rendu_monnaie_simple':
                                if (str_contains($tran->etat, '2.Depot')) {
                                    $content = $tran->content == 'MOBICASH' ? 'LIBERTIS' : 'AIRTEL_GA';
                                    $operation = 'achat_credit';
                                    $etat = 'atraiter';
                                }
                                break;
                            case 'achat_credit':
                                $etat = 'atraiter';
                                break;
                            case 'transfert_mobile':
                            case 'transfert_visa':
                                $etat = 'atraiter';
                                if (str_contains($tran->etat, '2.Depot')) {
                                    $reload = false;
                                    $general->alerteAgents("Transfert de *$tran->montant_sans_frais* F vers le *$tran->numclient* sans compte *$content*, contacter le client");
                                }
                                break;
                        }

                    } catch (Exception $e) {
                        // send error treatment
                        Historiquetrans::where('id', $tran->id)->update([
                            'code_validation' => 'error_traite',
                        ]);
                    }
                }
            }

            return $trans;
        } catch (Exception $e) {
            return response()->json([
                'statut' => false,
                'message' => 'Une erreur s\'est produite'
            ]);
        }
    }

    public function alerteTrans()
    {
        try {
            $general = new GeneralController();
            $now = date('Y-m-d H:i:s');
            $timeAlerte = date('Y-m-d H:i:s', strtotime("$now -3 minutes"));
            //return $timeAlerte;
            $trans = DB::select("SELECT * from historiquetrans h where h.etat not in ('CONFIRMEE', 'confirme', 'atraiter', 'attend') and h.code_validation ='traite' and h.updated_at < '" . $timeAlerte . "' limit 3");
            if (count($trans) > 0) {
                foreach ($trans as $tran)
                    try {
                        Historiquetrans::where('id', $tran->id)->update([
                            'code_validation' => 'alerte',
                        ]);
                        // send alerte support for treatment failed
                        $general->alerteAgents("*$tran->operation* de *$tran->montant_sans_frais* avec un etat *$tran->etat* depuis plus de 5 minutes");
                    } catch (Exception $e) {
                        // error send alerte support customer
                    }
            }

            return $trans;
        } catch (Exception $e) {
            return response()->json([
                'statut' => false,
                'message' => 'Une erreur s\'est produite'
            ]);
        }
    }


    public function assisteCustomer()
    {
        try {
            $general = new GeneralController();
            $now = date('Y-m-d H:i:s');
            $timeAlerte = date('Y-m-d H:i:s', strtotime("$now -4 minutes"));
            //return $timeAlerte;
            $trans = DB::select("SELECT * from historiquetrans h where h.etat not in ('CONFIRMEE', 'confirme', 'atraiter', 'attend') and h.code_validation = 'alerte' and h.updated_at < '" . $timeAlerte . "' limit 3");
            if (count($trans) > 0) {
                foreach ($trans as $tran) {
                    try {
                        // Open ticket
                        Historiquetrans::where('id', $tran->id)->update([
                            'code_validation' => 'assistance',
                        ]);
                        Customer::where('phoneclient', $tran->phonevendeur)->update([
                            'statut' => 'assistance',
                            'conversation_delais' => date('Y-m-d H:i:s')
                        ]);
                        // send message customer
                        // send alerte for open ticket
                        $general->alerteAgents("Alerte 2 ,*$tran->operation* de *$tran->montant_sans_frais* avec un etat *$tran->etat* depuis plus de 10 minutes");
                    } catch (Exception $e) {
                        // error send alerte for assistance failed
                    }
                }
            }

            return $trans;
        } catch (Exception $e) {
            return response()->json([
                'statut' => false,
                'message' => 'Une erreur s\'est produite'
            ]);
        }
    }


    public function finalAssisteCustomer()
    {
        try {
            $now = date('Y-m-d H:i:s');
            $timeAlerte = date('Y-m-d H:i:s', strtotime("$now -4 minutes"));
            $customers = DB::select("SELECT * from customer where statut = 'assistance' and conversation_delais  < '" . $timeAlerte . "' order by conversation_delais asc limit 5");
            if (count($customers) > 0) {
                foreach ($customers as $customer) {
                    try {
                        $trans = DB::select("SELECT * from historiquetrans h where h.etat not in ('CONFIRMEE', 'confirme', 'atraiter', 'attend') and h.code_validation = 'assistance' and h.phonevendeur = '" . $customer->phoneclient . "' ");
                        if (count($trans) == 0) {
                            foreach ($trans as $tran) {
                                try {
                                    Historiquetrans::where('id', $tran->id)->update([
                                        'code_validation' => 'assistance_closed',
                                    ]);
                                    Customer::where('phoneclient', $tran->phonevendeur)->update([
                                        'statut' => 'assistance_closed',
                                        'conversation_delais' => date('Y-m-d H:i:s')
                                    ]);
                                } catch (Exception $e) {
                                }
                            }
                        }
                    } catch (Exception $e) {
                        // error send alerte failed close assistance
                    }
                }
            }
        } catch (Exception $e) {
            return response()->json([
                'statut' => false,
                'message' => 'Une erreur s\'est produite'
            ]);
        }
    }



    public function dataCreditAssistance(Request $request)
    {
        try {
            $customer = Customer::where('id', $request->customer)->first();
            $depannage = false;
            $ticketWifi = false;
            $account = AssistanceDataCredit::where('customer_id', $customer->id)->first();
            if ($account) {
                $action = $request->action;
                $operateur = $request->operateur;
                $operation = 'data_credit';
                $qte = $request->qte == 'default' ? $account->defaut_forfait : $request->qte;
                $receveur = $request->receveur;
                $frais = '0';
                $qte_depannage = '';

                if ($request->operation == 'achat_credit') {
                    $operation = $request->operation;
                    $qte = $request->qte == 'default' ? $account->defaut_credit : $request->qte;
                    $frais = '100';
                } elseif ($request->operation == 'achat_forfait') {
                    $operation = $request->operation;
                    $qte = $request->qte == 'default' ? $account->defaut_mega : $request->qte;
                }

                if ($account->solde < (intval($qte) + intval($frais))) {
                    $depannage = true;
                    $frais = '50';
                    $operation = 'data_credit';
                    if ($action == 'credit') {
                        $qte = '150';
                        $qte_depannage = '150';
                        if ($operateur == 'LIBERTIS') {
                            $qte = '200';
                            $qte_depannage = '25';
                            $operation = 'data_credit';
                        }
                    } else {
                        $qte = '150';
                        $qte_depannage = '100';
                        if ($operateur == 'LIBERTIS') {
                            $qte = '200';
                            $qte_depannage = '200';
                        }
                    }
                    if ($account->solde < (intval($qte) + intval($frais))) {
                        return response()->json([
                            'statut' => true,
                            'action' => 'charge'
                        ]);
                    }
                }

                $trans = new Historiquetrans([
                    'operation' => $operation,
                    'reference' => $depannage == true ? 'MP_depannage' : 'MP_assistance',
                    'etat' => 'atraiter',
                    'numclient' => $receveur,
                    'phonevendeur' => $account->customer_phone,
                    'content' => $operateur,
                    'montant' => strval(intval($qte) + intval($frais)),
                    'montant_sans_frais' => $qte,
                    'frais' => $frais,
                    'solde' => $account->solde,
                    'origine_operation' => 'assistant',
                    'id_customer' => $account->customer_id
                ]);
                $trans->save();

                if ($trans->id) {
                    $newSolde = $trans->montant;
                    if (!empty($account->solde)) {
                        $newSolde = $account->solde - intval($trans->montant);
                    }
                    $account->update([
                        'solde' => $newSolde,
                        'operateur' => $operateur,
                        'depannage' => $depannage == true ? date('Y-m-d H:i:s') : null
                    ]);

                    if ($operation == 'data_credit') {
                        $details_operation = [];
                        if ($depannage == true) {
                            array_push($details_operation, array(
                                'operation' => $action == 'credit' ? $operateur != 'LIBERTIS' ? 'achat_credit' : 'Appels' : 'achat_forfait',
                                'qte' => $qte_depannage,
                                'name' => $action == 'credit' ? 'Appels' : 'achat_forfait',
                                'status' => 'wait',
                                'validity' => '1H',
                                'transaction_id' => $trans->id,
                                'numclient' => $receveur,
                                'operateur' => $operateur,
                                'reference' => $depannage == true ? 'MP_depannage' : 'MP_assistance',
                            ));
                        } else {
                            $forfait = ForfaitDataCredit::where('price', strval($qte))->where('operateur', $operateur == 'AIRTEL_GA' ? 'airtel' : 'moov')->first();
                            if ($forfait) {
                                if ($forfait->qte_credit != null) {
                                    array_push($details_operation, array(
                                        'operation' => 'achat_credit',
                                        'qte' => $forfait->qte_credit,
                                        'name' => 'achat_credit',
                                        'status' => 'wait',
                                        'validity' => null,
                                        'transaction_id' => $trans->id,
                                        'numclient' => $receveur,
                                        'operateur' => $operateur,
                                        'reference' => $depannage == true ? 'MP_depannage' : 'MP_assistance',
                                    ));
                                }

                                if ($forfait->qte_flex != null) {
                                    array_push($details_operation, array(
                                        'operation' => 'flex',
                                        'qte' => $forfait->qte_flex,
                                        'name' => 'flex',
                                        'status' => 'wait',
                                        'validity' => $forfait->validity_flex,
                                        'transaction_id' => $trans->id,
                                        'numclient' => $receveur,
                                        'operateur' => $operateur,
                                        'reference' => $depannage == true ? 'MP_depannage' : 'MP_assistance',
                                    ));
                                }

                                if ($forfait->qte_min != null) {
                                    array_push($details_operation, array(
                                        'operation' => $operateur == 'AIRTEL_GA' ? 'achat_credit' : 'Appels',
                                        'qte' => $operateur == 'AIRTEL_GA' ? $forfait->param2 : $forfait->qte_min,
                                        'name' => 'Appels',
                                        'status' => 'wait',
                                        'validity' => $forfait->validity_min,
                                        'transaction_id' => $trans->id,
                                        'numclient' => $receveur,
                                        'operateur' => $operateur,
                                        'reference' => $depannage == true ? 'MP_depannage' : 'MP_assistance',
                                    ));
                                }

                                if ($forfait->qte_mega != null) {
                                    array_push($details_operation, array(
                                        'operation' => 'achat_forfait',
                                        'qte' => $forfait->qte_mega,
                                        'name' => 'achat_forfait',
                                        'status' => 'wait',
                                        'validity' => $forfait->validity_mega,
                                        'transaction_id' => $trans->id,
                                        'numclient' => $receveur,
                                        'operateur' => $operateur,
                                        'reference' => $depannage == true ? 'MP_depannage' : 'MP_assistance',
                                    ));
                                }

                                if ($forfait->ticket_wifi != null) {
                                    $ticketWifi = true;
                                }
                            }
                        }

                        for ($i = 0; $i < count($details_operation); $i++) {
                            $details = new DetatilsOperation($details_operation[$i]);
                            $details->save();
                        }
                    }


                    try {
                        $marchands = Customer::where('last_transaction_operation', str_contains($operation, 'transfert') ? 'transfert' : $operation)->where('type', 'default_marchand')->first();
                        if ($marchands) {
                            Http::asForm()->post('https://us-central1-status-9986a.cloudfunctions.net/api/updateConversation', [
                                'trans' => $trans,
                                'marchand_code' => $marchands->last_transaction_operation,
                                'customer' => $customer->id,
                                'marchand' => $marchands->id,
                                'message' => ''
                            ]);

                            if ($ticketWifi == true) {
                                $wifi = $this->createdVoucher(strval($customer->phoneclient . '-' . $customer->nom ?? '' . ' ' . $customer->prenom ?? ''));
                                if ($wifi['status'] == true) {
                                    Http::asForm()->post('https://us-central1-status-9986a.cloudfunctions.net/api/updateConversation', [
                                        'trans' => null,
                                        'marchand_code' => 'wifi',
                                        'customer' => $customer->id,
                                        'marchand' => 21086895,
                                        'message' => 'Votre ticket wifi d\'une semaine ' . strval($wifi['code'])
                                    ]);
                                }
                            }
                        }
                        $message = "Ravitaillement en cours...";
                        Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$customer->unlock_token&app=android&message=$message&titre=$customer->nom");
                    } catch (\Exception $th) {
                        //throw $th;
                    }

                    return response()->json([
                        'statut' => true,
                        'trans' => $trans,
                        'action' => 'next'
                    ]);
                } else {
                    return response()->json([
                        'statut' => false,
                        'action' => 'no trans'
                    ]);
                }
            } else {
                return response()->json([
                    'statut' => false,
                    'action' => 'no wallet'
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'statut' => false,
                'action' => 'error'
            ]);
        }
    }

    /**
     * Assistance pendant un appel : forfait crédit par défaut (defaut_credit),
     * sinon forfait "Appels" équivalent, sinon achat de crédit, sinon recharge.
     */
    public function creditAssistance(Request $request)
    {
        return $this->assistanceForfait($request, 'Appels', 'defaut_credit', true);
    }

    /**
     * Assistance forfait internet : forfait internet par défaut (defaut_mega),
     * sinon forfait "Forfait" équivalent, sinon recharge du wallet.
     */
    public function dataAssistance(Request $request)
    {
        return $this->assistanceForfait($request, 'Forfait', 'defaut_mega', false);
    }

    /**
     * Décode un forfait par défaut stocké au format "operateur_prix"
     * (ex. "airtel_1200"). L'ancien format (prix seul) est accepté.
     */
    private function decoderForfaitDefaut($code)
    {
        $code = trim((string) $code);
        if ($code === '' || $code === 'null') {
            return null;
        }

        // Mode "Crédit uniquement" : "airtel_500_creditOnly"
        $creditOnly = false;
        $suffixe = '_creditonly';
        if (str_ends_with(strtolower($code), $suffixe)) {
            $creditOnly = true;
            $code = substr($code, 0, -strlen($suffixe));
        }

        $pos = strrpos($code, '_');
        if ($pos === false) {
            return ['operateur' => null, 'price' => intval($code), 'creditOnly' => $creditOnly];
        }
        return [
            'operateur' => strtolower(substr($code, 0, $pos)),
            'price' => intval(substr($code, $pos + 1)),
            'creditOnly' => $creditOnly,
        ];
    }

    /**
     * Logique commune à creditAssistance et dataAssistance.
     *
     * @param string $nomForfait   'Appels' (crédit) ou 'Forfait' (internet)
     * @param string $colonneDefaut 'defaut_credit' ou 'defaut_mega'
     * @param bool   $achatCredit  autoriser l'achat de crédit simple si aucun forfait
     */
    private function assistanceForfait(Request $request, $nomForfait, $colonneDefaut, $achatCredit)
    {
        try {
            $customer = Customer::where('id', $request->customer)->first();
            if (!$customer) {
                return response()->json(['statut' => false, 'action' => 'no customer']);
            }

            $account = AssistanceDataCredit::where('customer_id', $customer->id)->first();
            if (!$account) {
                return response()->json(['statut' => false, 'action' => 'no wallet']);
            }

            $ticketWifi = false;
            $amount = 0;
            $frais = 0;
            $operation = 'data_credit';
            $receveur = $request->receveur;
            $operateur = $request->operateur; // 'AIRTEL_GA' | 'LIBERTIS'
            $opForfait = $operateur == 'AIRTEL_GA' ? 'airtel' : 'moov';
            $solde = intval($account->solde);
            $forfait = null;
            $qteForfaitTrans = null;
            $qteCallTimeTrans = null;

            // 1. Forfait par défaut, s'il est de l'opérateur de la SIM et que le solde suffit
            // Offre choisie à l'achat direct dans l'app ("operateur_prix"),
            // sinon le forfait par défaut du wallet
            $codeForfait = trim((string) $request->forfait_choisi) !== ''
                ? $request->forfait_choisi
                : $account->{$colonneDefaut};
            $defaut = $this->decoderForfaitDefaut($codeForfait);
            $prixDefaut = $defaut ? $defaut['price'] : 0;
            $memeOperateur = $defaut && ($defaut['operateur'] === null || $defaut['operateur'] == $opForfait);

            // Mode "Crédit uniquement" (defaut_credit = "airtel_500_creditOnly") :
            // le client n'a souscrit à aucun forfait, on ne lui envoie que du crédit.
            $creditSeul = $achatCredit && $defaut && $defaut['creditOnly'];

            if ($creditSeul) {
                if ($solde < 120) {
                    return response()->json([
                        'statut' => true,
                        'action' => 'charge'
                    ]);
                }
                $operation = 'achat_credit';
                $frais = $solde >= 200 ? 100 : 20;
                // montant par défaut, ou ce que le solde permet
                $amount = min($prixDefaut, $solde - $frais);
            } elseif ($defaut && $memeOperateur && $solde >= $prixDefaut) {
                $forfait = ForfaitDataCredit::where('name', $nomForfait)
                    ->where('operateur', $opForfait)
                    ->whereRaw('CAST(price AS UNSIGNED) = ?', [$prixDefaut])
                    ->first();
            }

            // 2. Sinon, le forfait le plus proche que le solde permet
            //    (sans dépasser le forfait par défaut)
            if (!$forfait && !$creditSeul) {
                $requete = ForfaitDataCredit::where('name', $nomForfait)
                    ->where('operateur', $opForfait)
                    ->whereRaw('CAST(price AS UNSIGNED) <= ?', [$solde]);
                if ($prixDefaut > 0) {
                    $requete->whereRaw('CAST(price AS UNSIGNED) <= ?', [$prixDefaut]);
                }
                $forfait = $requete->orderByRaw('CAST(price AS UNSIGNED) DESC')->first();
            }

            $depannage = null;
            if ($creditSeul) {
                // déjà calculé plus haut : crédit uniquement
            } elseif ($forfait) {
                $amount = intval($forfait->price);
            } elseif ($achatCredit && $solde >= 120) {
                // 3a. Crédit : achat de crédit avec ce que le solde permet,
                //     plafonné au montant du forfait crédit par défaut
                $operation = 'achat_credit';
                $frais = $solde >= 200 ? 100 : 20;
                $amount = $solde - $frais;
                if ($prixDefaut > 0) {
                    $amount = min($prixDefaut, $amount);
                }
            } elseif (!$achatCredit && $solde >= $this->petitForfaitData[$opForfait]['price']) {
                // 3b. Internet : plus petit forfait de l'opérateur (dépannage)
                $depannage = $this->petitForfaitData[$opForfait];
                $amount = $depannage['price'];
            } else {
                // 4. Solde insuffisant : recharger le wallet
                return response()->json([
                    'statut' => true,
                    'action' => 'charge'
                ]);
            }

            $reference = $depannage ? 'MP_depannage' : 'MP_assistance';

            $trans = new Historiquetrans([
                'operation' => $operation,
                'reference' => $reference,
                'numclient' => $receveur,
                'phonevendeur' => $account->customer_phone,
                'content' => $operateur,
                'montant' => strval($amount + $frais),
                'montant_sans_frais' => strval($amount),
                'frais' => strval($frais),
                'solde' => $account->solde,
                'origine_operation' => 'assistant',
                'id_customer' => $account->customer_id
            ]);
            $trans->save();

            if (!$trans->id) {
                return response()->json(['statut' => false, 'action' => 'no trans']);
            }

            $account->update([
                'solde' => $solde - intval($trans->montant),
                'operateur' => $operateur,
                'depannage' => $depannage ? date('Y-m-d H:i:s') : null,
            ]);

            if ($depannage) {
                $details = new DetatilsOperation([
                    'operation' => 'achat_forfait',
                    'qte' => $depannage['qte'],
                    'name' => 'achat_forfait',
                    'status' => 'wait',
                    'validity' => $depannage['validity'],
                    'transaction_id' => $trans->id,
                    'numclient' => $receveur,
                    'operateur' => $operateur,
                    'reference' => $reference,
                ]);
                $details->save();
                $qteForfaitTrans = $depannage['qte'];
            }

            if ($forfait != null) {
                $details_operation = [];
                if ($forfait->qte_flex != null) {
                    array_push($details_operation, array(
                        'operation' => 'flex',
                        'qte' => $forfait->qte_flex,
                        'name' => 'flex',
                        'status' => 'wait',
                        'validity' => $forfait->validity_flex,
                        'transaction_id' => $trans->id,
                        'numclient' => $receveur,
                        'operateur' => $operateur,
                        'reference' => 'MP_assistance',
                    ));
                    $qteCallTimeTrans =  ceil($forfait->qte_flex /174);
                }

                if ($forfait->qte_min != null) {
                    array_push($details_operation, array(
                        'operation' => $operateur == 'AIRTEL_GA' ? 'achat_credit' : 'Appels',
                        'qte' => $operateur == 'AIRTEL_GA' ? $forfait->param2 : $forfait->qte_min,
                        'name' => 'Appels',
                        'status' => 'wait',
                        'validity' => $forfait->validity_min,
                        'transaction_id' => $trans->id,
                        'numclient' => $receveur,
                        'operateur' => $operateur,
                        'reference' => 'MP_assistance',
                    ));
                    if($qteCallTimeTrans == null){
                        $qteCallTimeTrans = intval($forfait->qte_min);
                    }else{
                        $qteCallTimeTrans = $qteCallTimeTrans + intval($forfait->qte_min);
                    }
                }

                if ($forfait->qte_mega != null) {
                    array_push($details_operation, array(
                        'operation' => 'achat_forfait',
                        'qte' => $forfait->qte_mega,
                        'name' => 'achat_forfait',
                        'status' => 'wait',
                        'validity' => $forfait->validity_mega,
                        'transaction_id' => $trans->id,
                        'numclient' => $receveur,
                        'operateur' => $operateur,
                        'reference' => 'MP_assistance',
                    ));
                    $qteForfaitTrans = $forfait->qte_mega;
                }

                if ($forfait->ticket_wifi != null) {
                    $ticketWifi = true;
                }

                for ($i = 0; $i < count($details_operation); $i++) {
                    $details = new DetatilsOperation($details_operation[$i]);
                    $details->save();
                }
            }

            // La transaction n'est mise à traiter qu'une fois ses détails créés
            $trans->update([
                'etat' => 'atraiter',
                'param8' => $qteCallTimeTrans,
                'param9'=> $qteForfaitTrans
            ]);

            $this->assistancePartyFirebase($customer, $trans, $operation, $ticketWifi);

            return response()->json([
                'statut' => true,
                'trans' => $trans,
                'action' => $depannage ? 'depannage' : 'next'
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'statut' => false,
                'action' => 'error'
            ]);
        }
    }

    public function getWallet(Request $request)
    {
        $account = AssistanceDataCredit::where('customer_id', $request->customer)->first();
        if ($account) {
            return response()->json([
                'statut' => true,
                'body' => $account,
                'default_message' => "Vous n'avez pas assez de crédit pour passer cet appel?

Rechargez votre solde dès aujourd'hui et laissez votre assistant virtuel s'occuper du reste.

À partir de votre solde disponible, je vous envoie automatiquement du crédit ou un forfait Internet dès que vous en avez besoin, sans aucune intervention de votre part"
            ]);
        } else {
            return response()->json([
                'statut' => false,
                'message' => 'Pas de wallet',
                'default_message' => "Vous n'avez pas assez de crédit pour passer cet appel?

Rechargez votre solde dès aujourd'hui et laissez votre assistant virtuel s'occuper du reste.

À partir de votre solde disponible, je vous envoie automatiquement du crédit ou un forfait Internet dès que vous en avez besoin, sans aucune intervention de votre part"

            ]);
        }
    }

    public function addWallet(Request $request)
    {
        // Seules les valeurs réellement envoyées sont enregistrées :
        // - forfait_credit / forfait_data vides ne doivent pas effacer
        //   defaut_credit / defaut_mega ;
        // - un montant à 0 (enregistrement des forfaits seul, depuis
        //   Charger()) ne doit pas écraser amount_recharge, utilisé par le
        //   retour de paiement pour créditer le wallet.
        $donnees = [];
        if (!empty($request->operateur)) {
            $donnees['operateur'] = $request->operateur;
        }
        if (!empty($request->forfait)) {
            $donnees['defaut_forfait'] = $request->forfait;
        }
        if (!empty($request->forfait_credit)) {
            $donnees['defaut_credit'] = $request->forfait_credit;
        }
        if (!empty($request->forfait_data)) {
            $donnees['defaut_mega'] = $request->forfait_data;
        }
        if (intval($request->amount) > 0) {
            $donnees['amount_recharge'] = $request->amount;
        }

        $account = AssistanceDataCredit::where('customer_id', $request->customer)->first();
        if ($account) {
            $account->update($donnees);
            return response()->json([
                'statut' => true,
                'body' => $account->fresh()
            ]);
        } else {
            $newWallet = new AssistanceDataCredit(array_merge($donnees, [
                'customer_id' => $request->customer,
                'customer_phone' => $request->phone,
            ]));
            $newWallet->save();

            return response()->json([
                'statut' => true,
                'body' => $newWallet->fresh()
            ]);
        }
    }

    public function getDetailsForfait(Request $request)
    {
        $trans = Historiquetrans::where('id', $request->trans)->first();
        $flex = null;
        if ($trans) {

            if ($trans->reference == 'MP_depannage' && $trans->content == 'AIRTEL_GA') {
                $flex = $this->codeEnableCallForfait($trans->montant_sans_frais, $trans->content);
            } else {
                $forfait = ForfaitDataCredit::where('price', $trans->montant_sans_frais)->where('operateur', $trans->content == 'AIRTEL_GA' ? 'airtel' : 'moov')->orderBy('updated_at', 'desc')->first();
                if ($forfait) {
                    $flex = $this->codeEnableCallForfait($forfait->operateur == 'airtel' ? $forfait->param2 : $forfait->qte_flex, $trans->content);
                }
            }

            $details = DetatilsOperation::where('transaction_id', $request->trans)->get();

            // Opération uniquement internet (forfait ou dépannage internet) :
            // aucun code flex / minutes à activer. Sans ça, un forfait
            // "Appels" de même prix pouvait déclencher un USSD d'appel.
            if (count($details) > 0 && $details->every(function ($d) {
                    return $d->operation == 'achat_forfait';
                })) {
                $flex = null;
            }

            if (count($details) > 0) {
                return response()->json([
                    'statut' => true,
                    'body' => $details,
                    'trans' => $trans,
                    'flex' => $flex,
                    'text' => $trans->content == 'AIRTEL_GA' ? 'Activation des minutes' : 'Activation du flex'
                ]);
            } else {
                return response()->json([
                    'statut' => false,
                    'message' => 'Pas de details'

                ]);
            }
        } else {
            return response()->json([
                'statut' => false,
                'message' => 'Pas de transaction'

            ]);
        }
    }

    public function codeEnableCallForfait($forfait, $operateur)
    {
        $code = null;
        $forfaitNumber = '0';
        if ($operateur != 'AIRTEL_GA') {
            switch ($forfait) {
                case '2500':
                    $forfaitNumber = '1';
                    break;
                case '5000':
                    $forfaitNumber = '2';
                    break;
                case '10000':
                    $forfaitNumber = '3';
                    break;
                case '25000':
                    $forfaitNumber = '4';
                    break;
                case '50000':
                    $forfaitNumber = '5';
                    break;
            }

            if ($forfaitNumber != '0') {
                $code = "*222*3*3*$forfaitNumber#";
            }
        } else {
            switch ($forfait) {
                case '150':
                    $forfaitNumber = '1';
                    break;
                case '300':
                    $forfaitNumber = '2';
                    break;
                case '500':
                    $forfaitNumber = '3';
                    break;
                case '1000':
                    $forfaitNumber = '4';
                    break;
                case '2000':
                    $forfaitNumber = '5';
                    break;
                case '5000':
                    $forfaitNumber = '6';
                    break;
                case '10000':
                    $forfaitNumber = '7';
                    break;
            }

            if ($forfaitNumber != '0') {
                $code = "*111*2*2*1*$forfaitNumber*2#";
            }
        }
        return $code;
    }

    public function rechargeWallet(Request $request)
    {
        $account = AssistanceDataCredit::where('customer_id', $request->customer)->first();
        if ($account) {
            $account->update([
                'ref_recharge' => $request->ref,
                'number_charge' => $request->phone,
                'amount_recharge' => $request->amount
            ]);
            return response()->json([
                'statut' => true,
                'body' => $account
            ]);
        } else {
            return response()->json([
                'statut' => false,
            ]);
        }
    }

    public function detailOperationMessage(Request $request)
    {
        try {
            $marchands = Customer::where('last_transaction_operation', 'data_credit')->where('type', 'default_marchand')->first();
            if ($marchands) {
                $details = $request->details;
                $message = $request->message;
                if ($details != null) {
                    switch ($details['operation']) {
                        case 'achat_credit':
                            $message = 'Credit de communication de ' . $details['qte'] . 'F envoyé';
                            break;
                        case 'flex':
                            $message = 'Credit FLEX de ' . $details['qte'] . 'F valable ' . $details['validity'] . ' activé';
                            break;
                        case 'Appels':
                            $message = $details['qte'] . ' minutes d\'appel  valable ' . $details['validity'] . ' activé';
                            break;
                        case 'achat_forfait':
                            $message = 'Forfait internet de ' . $details['qte'] . ' Mo activé';
                            break;
                        default:
                            $message = 'Ravitaillement GamPay effectué';
                            break;
                    }
                }
                $request = Http::asForm()->post('https://us-central1-status-9986a.cloudfunctions.net/api/updateConversation', [

                    'marchand_code' => $marchands->last_transaction_operation,
                    'customer' => $request->customer,
                    'marchand' => $marchands->id,
                    'message' => $message ?? 'Ravitaillement GamPay effectué'
                ])->body();

                return $request;
            }

            return $marchands;
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'action' => 'error : ' . $th->getMessage()
            ]);
        }
    }

    public function createdVoucher($number)
    {
        $baseUrl = "https://api.ui.com/v1/connector/consoles/6C63F88C8FA100000000092C833C0000000009AA92FC0000000068453ADC:867554590/proxy/network/integration/v1/sites/88f7af54-98f8-306a-a1c7-c9349722b1f6/hotspot/vouchers";


        $time = 10080; // 7 day in minutes
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
             "count": 1,
             "name": "' . $number . '",
             "authorizedGuestLimit": 1,
             "timeLimitMinutes": ' . $time . '
                }',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'X-API-Key: JuJ5THpo6QOa7_CI3WVfvcM7Vi_PLgdR'
            ),
        ));

        $result = curl_exec($curl);
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                return [
                    'status' => true,
                    'code' => $response['vouchers'][0]['code'],
                    'numero' => $response['vouchers'][0]['name'],
                    'duree' => $response['vouchers'][0]['timeLimitMinutes'] . " minutes"
                ];
            } else {
                return [10, $result];
            }
        } else {
            return [100, $result];
        }
    }

    public function testCreateMessagMarchand(Request $request)
    {
        try {
            $customer = Customer::where('id', $request->customer)->first();
            $trans = Historiquetrans::where('id', $request->trans)->first();
            $marchands = Customer::where('last_transaction_operation', str_contains($trans->operation, 'transfert') ? 'transfert' : $trans->operation)->where('type', 'default_marchand')->first();

            $send1= Http::asForm()->post('https://us-central1-status-9986a.cloudfunctions.net/api/updateConversation', [
                'trans' => $trans,
                'marchand_code' => $marchands->last_transaction_operation,
                'customer' => $customer->id,
                'marchand' => $marchands->id,
                'message' => ''
            ])->body();

            $wifi = $this->createdVoucher(strval($customer->phoneclient . '-' . $customer->nom ?? '' . ' ' . $customer->prenom ?? ''));
            if ($wifi['status'] == true) {
                $send2  = Http::asForm()->post('https://us-central1-status-9986a.cloudfunctions.net/api/updateConversation', [
                    'trans' => null,
                    'marchand_code' => $marchands->last_transaction_operation,
                    'customer' => $customer->id,
                    'marchand' => $marchands->id,
                    'message' => 'Votre ticket wifi d\'une semaine ' . strval($wifi['code'])
                ])->body();

                return response()->json([
                    'statut' => true,
                    'data' => $wifi,
                    'send1' =>$send1,
                    'send2' =>$send2
                ]);
            }
            $message = "Ravitaillement en cours...";
            Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$customer->unlock_token&app=android&message=$message&titre=$customer->nom");
            return response()->json([
                'statut' => true,
                'send1' =>$send1,
                'data' => $wifi
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'statut' => false,
                'action' => 'error ' . $th->getMessage(),

            ]);
        }
    }

    public function udateCallTimeOnline(Request $request)
    {
        $wallet = AssistanceDataCredit::where('id', $request->wallet)->first();
        if ($wallet) {
            $wallet->update([
                'call_time' => $request->call_time,
                'call_time_recharge' => $request->recharge == 'true' ? '0' : $wallet->call_time_recharge,
                'call_time_change' => $request->recharge == 'true' ? '0' : $wallet->call_time_change,
                'forfait_qte' => $request->forfait_qte??'0',
                'forfait_last_verify_at' => $request->forfait_last_verify_at,
                'forfait_qte_recharge' => $request->recharge == 'true' ? '0' : $wallet->forfait_qte_recharge,
                'forfait_qte_change' => $request->recharge == 'true' ? '0' : $wallet->forfait_qte_change,
            ]);
            return response()->json([
                'statut' => true,
                'wallet' => $wallet,
                'message' => 'Wallet ok '
            ]);
        } else {
            return response()->json([
                'statut' => false,
                'message' => 'Pas de wallet'
            ]);
        }
    }


    /**
     * Synchronisation du temps d'appel et du forfait PAR SIM.
     *
     * Entrée : customer (id), sims = [{slot, number, call_time, forfait,
     *   forfait_last_verify_at, applied_call_time_recharge,
     *   applied_call_time_change, applied_forfait_recharge,
     *   applied_forfait_change}]
     *   - call_time / forfait : valeurs actuelles côté app ;
     *   - applied_* : recharges déjà ajoutées côté app, à retirer ici
     *     (acquittement : une recharge arrivée entre-temps n'est pas perdue).
     *
     * Sortie : toutes les SIM du client, avec les recharges en attente.
     */
    public function udateSimsOnline(Request $request)
    {
        try {
            $customer = Customer::where('id', $request->customer)->first();
            if (!$customer) {
                return response()->json(['statut' => false, 'message' => 'Pas de client']);
            }

            $sims = ConfigSim::pourCustomer($customer);

            foreach (($request->sims ?? []) as $item) {
                $sim = $sims->firstWhere('slot', intval($item['slot'] ?? -1));
                if (!$sim) {
                    continue;
                }

                $maj = ['updated_at' => now()];

                // Valeurs de l'app, seulement si c'est bien la même SIM
                $memeSim = empty($item['number']) || ConfigSim::memeNumero($item['number'], $sim->number);
                if ($memeSim) {
                    if (isset($item['call_time']) && is_numeric($item['call_time'])) {
                        $maj['call_time'] = max(0, round(floatval($item['call_time']), 2));
                    }
                    if (isset($item['forfait']) && is_numeric($item['forfait'])) {
                        $maj['forfait'] = max(0, round(floatval($item['forfait']), 3));
                    }
                    if (!empty($item['forfait_last_verify_at']) && $item['forfait_last_verify_at'] != 'null') {
                        try {
                            $maj['forfait_last_verify_at'] = \Carbon\Carbon::parse($item['forfait_last_verify_at'])->format('Y-m-d H:i:s');
                        } catch (\Exception $th) {
                        }
                    }
                }

                // Acquittement des recharges appliquées par l'app
                $appCall = round(floatval($item['applied_call_time_recharge'] ?? 0), 2);
                $appData = round(floatval($item['applied_forfait_recharge'] ?? 0), 3);
                if ($appCall > 0) {
                    $maj['call_time_recharge'] = DB::raw('GREATEST(call_time_recharge - ' . number_format($appCall, 2, '.', '') . ', 0)');
                }
                if ($appData > 0) {
                    $maj['forfait_recharge'] = DB::raw('GREATEST(forfait_recharge - ' . number_format($appData, 3, '.', '') . ', 0)');
                }
                $appCallChange = floatval($item['applied_call_time_change'] ?? 0);
                $appDataChange = floatval($item['applied_forfait_change'] ?? 0);
                if ($appCallChange > 0 && abs(floatval($sim->call_time_change) - $appCallChange) < 0.001) {
                    $maj['call_time_change'] = 0;
                }
                if ($appDataChange > 0 && abs(floatval($sim->forfait_change) - $appDataChange) < 0.001) {
                    $maj['forfait_change'] = 0;
                }

                ConfigSim::where('id', $sim->id)->update($maj);
            }

            return response()->json([
                'statut' => true,
                'sims' => ConfigSim::where('customer_id', $customer->id)->orderBy('slot')->get(),
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'statut' => false,
                'message' => 'error ' . $th->getMessage(),
            ]);
        }
    }


    public function calculTimeCallUssd(Request $request)
    {
        $customer = Customer::where('config_sims', 'like', "%{$request->numeroclient}%")->orWhere('config_sims', 'like', "%{0$request->numeroclient}%")->first();
        if($customer){
            $account = AssistanceDataCredit::where('customer_id', $customer->id)->first();
            if($account){
                $forfait = "";
                if ($request->price == 1200) {
                    $forfait = 1000;
                    $montant = 500 / 174;
                } elseif ($request->price == 2200) {
                    $forfait = 2000;
                    $montant = 1000 / 174;
                } elseif ($request->price == 5200) {
                    $forfait = 5000;
                    $montant = 3000 / 174;
                } elseif ($request->price == 10200) {
                    $forfait = 14000;
                    $montant = 5000 / 174;
                }else{
                    $montant = $request->price / 174;
                }

                $time = floor((strval($account->call_time_recharge) + strval($montant)) * 100) / 100;
                $account->update([
                    'call_time_recharge' => $time,
                    'forfait_qte_recharge' => empty($forfait) ? $account->forfait_qte_recharge : $account->forfait_qte_recharge + $forfait
                ]);
                // Suivi par SIM : la SIM rechargée
                try {
                    ConfigSim::ajouterRecharge($request->numeroclient, floor($montant * 100) / 100, empty($forfait) ? 0 : $forfait);
                } catch (\Exception $th) {
                }
                return response()->json([
                    'statut' => true,
                    'time' => $time,
                    'message' => 'call time recharge ok'
                ]);
            }
        }
    }

    public function rechargeCallTimeOrData(Request $request){
        return $this->rechargeCallTimeOrDataIn($request->trans, $request->receveur, $request->operation, $request->qte_credit, $request->qte_data,$request->amount, $request->type);
    }

    public function rechargeCallTimeOrDataIn($trans, $receveur, $operation, $qte_credit, $qte_data,$amount, $type = 'gampay'){
        $response = [
            'statut' => false,
            'message' => 'no account'
        ];
        $status_operation = 'point_wallet_no_charge';

        // Quantités à ravitailler (min d'appel / Mo)
        $minutesRecharge = 0;
        $megaRecharge = 0;
        if ($operation == 'achat_credit' || $operation == 'data_credit') {
            $minutesRecharge = $qte_credit == null ? ceil($amount / 174) : doubleval($qte_credit);
        }
        if ($operation != 'achat_credit') {
            $megaRecharge = doubleval($qte_data);
        }

        // Suivi par SIM : la SIM qui a reçu le crédit / forfait, si son
        // client est passé au suivi par SIM (sinon : wallet, plus bas).
        try {
            $simRecue = ConfigSim::whereIn('number', ["0$receveur", $receveur])->first();
            if($simRecue){
                $simRecue->update([
                    'call_time_recharge' => $simRecue->call_time_recharge + $minutesRecharge,
                    'forfait_recharge' => $simRecue->call_time_recharge + $megaRecharge
                ]);
                $status_operation = 'point_wallet_charge';
                $customer = Customer::where('id', $simRecue->customer_id)->first();
                if($customer){
                    if($minutesRecharge>0){
                        $this->sendNotif("Temps d'appel Cloud rechargé", null, $customer);
                    }

                    if($megaRecharge>0){
                        $this->sendNotif("Forfait internet Cloud, rechargé", null, $customer);
                    }

                }
            }
        } catch (\Exception $th) { }

        if($type == 'gampay'){
            Historiquetrans::where('id', $trans)->update([
                'param5' => $status_operation
            ]);
        }else{
            GamRechargeHist::where('id', $trans)->update([
                'param4' => $status_operation
            ]);
        }

        return response()->json($response);
    }

    public function getQteForfait($price, $operateur){
        if($operateur == 'LIBERTIS'){
            switch ($price){
                case '200' :
                    return '25';
                case '500' :
                    return '1000';
                case '1000' :
                    return '2000';
            }
        }else{
            switch ($price){
                case '150' :
                    return '20';
                case '500' :
                    return '500';
                case '1000' :
                    return '2000';
            }
        }
    }

    public function assistancePartyFirebase($customer, $trans, $operation,$ticketWifi){
        try {
            $marchands = Customer::where('last_transaction_operation', str_contains($operation, 'transfert') ? 'transfert' : $operation)->where('type', 'default_marchand')->first();
            if ($marchands) {
                Http::asForm()->post('https://us-central1-status-9986a.cloudfunctions.net/api/updateConversation', [
                    'trans' => $trans,
                    'marchand_code' => $marchands->last_transaction_operation,
                    'customer' => $customer->id,
                    'marchand' => $marchands->id,
                    'message' => ''
                ]);

                if ($ticketWifi == true) {
                    $wifi = $this->createdVoucher(strval($customer->phoneclient . '-' . $customer->nom ?? '' . ' ' . $customer->prenom ?? ''));
                    if ($wifi['status'] == true) {
                        Http::asForm()->post('https://us-central1-status-9986a.cloudfunctions.net/api/updateConversation', [
                            'trans' => null,
                            'marchand_code' => 'wifi',
                            'customer' => $customer->id,
                            'marchand' => 21086895,
                            'message' => 'Votre ticket wifi d\'une semaine ' . strval($wifi['code'])
                        ]);
                    }
                }
            }
            $message = "Ravitaillement en cours...";
            Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$customer->unlock_token&app=android&message=$message&titre=$customer->nom");
        } catch (\Exception $th) {
            //throw $th;
        }
    }


    public function sendNotif($message, $id, $customerData){
        if($customerData != null){
            $customer =   $customerData;
        }else{
            $customer = Customer::where('id', $id)->first();
        }
        if($customer){
            Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$customer->unlock_token&app=android&message=$message&titre=$customer->nom");
        }
    }

    public function achatDirectOffre(Request $request)
    {
        try {
            $customer = Customer::where('id', $request->customer)->first();
            if (!$customer) {
                return response()->json(['statut' => false, 'message' => 'Client introuvable']);
            }

            $paiement = $request->paiement == 'moov' ? 'moov' : 'airtel';
            $mode = $request->mode == 'ussd' ? 'ussd' : 'push';

            // Relance du paiement d'une opération déjà créée
            if ($request->trans) {
                $trans = Historiquetrans::where('id', $request->trans)
                    ->where('id_customer', $customer->id)
                    ->where('etat', 'attend')
                    ->first();
                if (!$trans) {
                    return response()->json(['statut' => false, 'message' => 'Opération introuvable']);
                }
                $push = $mode == 'push'
                    ? $this->lancerPaiementOffre($trans, $paiement, $request->phone)
                    : true;
                return response()->json([
                    'statut' => $push != null,
                    'trans' => $trans->fresh(),
                    'message' => $push != null ? 'Paiement en cours' : "La demande de paiement n'a pas abouti",
                ]);
            }

            $operateur = $request->operateur == 'AIRTEL_GA' ? 'AIRTEL_GA' : 'LIBERTIS';
            $opForfait = $operateur == 'AIRTEL_GA' ? 'airtel' : 'moov';
            $receveur = $request->receveur;
            $internet = $request->type == 'internet';

            $offre = $this->decoderForfaitDefaut($request->forfait_choisi);
            if (!$offre || $offre['price'] <= 0) {
                return response()->json(['statut' => false, 'message' => 'Offre invalide']);
            }

            $operation = 'data_credit';
            $amount = intval($offre['price']);
            $frais = 0;
            $forfait = null;

            if (!$internet && $offre['creditOnly']) {
                // Crédit simple : mêmes frais que dans l'application
                $operation = 'achat_credit';
                $frais = ($amount + 100 >= 200) ? 100 : 20;
                // Offre et moyen de paiement du même opérateur : pas de frais
                // (offre Airtel payée par Airtel Money, Libertis par Moov Money)
                if (($paiement == 'airtel' && $operateur == 'AIRTEL_GA')
                    || ($paiement == 'moov' && $operateur == 'LIBERTIS')) {
                    $frais = 0;
                }
            } else {
                $forfait = ForfaitDataCredit::where('name', $internet ? 'Forfait' : 'Appels')
                    ->where('operateur', $opForfait)
                    ->whereRaw('CAST(price AS UNSIGNED) = ?', [$amount])
                    ->first();
                if (!$forfait) {
                    return response()->json(['statut' => false, 'message' => 'Offre indisponible']);
                }
            }

            $reference = $paiement == 'moov' ? 'offre' . time() . $customer->id : 'MP_achat_direct';

            $trans = new Historiquetrans([
                'operation' => $operation,
                'reference' => $reference,
                'etat' => 'attend',
                'numclient' => $receveur,
                'phonevendeur' => $customer->phoneclient,
                'content' => $operateur,
                'montant' => strval($amount + $frais),
                'montant_sans_frais' => strval($amount),
                'frais' => strval($frais),
                'solde' => '0',
                'origine_operation' => 'assistant_achat_direct',
                'id_customer' => $customer->id,
            ]);
            $trans->save();

            if (!$trans->id) {
                return response()->json(['statut' => false, 'message' => "L'opération n'a pas pu être créée"]);
            }

            [$qteCallTime, $qteForfait, $ticketWifi] = $forfait
                ? $this->creerDetailsOffre($forfait, $trans, $receveur, $operateur, $reference)
                : $this->creerDetailCredit($trans, $receveur, $operateur, $reference, $amount);

            $trans->update([
                'param8' => $qteCallTime,
                'param9' => $qteForfait,
            ]);

            // Paiement
            $push = true;
            if ($mode == 'push') {
                $push = $this->lancerPaiementOffre($trans, $paiement, $request->phone);
            }

            // Conversation avec le marchand (l'opération apparaît dans l'app)
            $this->assistancePartyFirebase($customer, $trans, $operation, $ticketWifi);

            return response()->json([
                'statut' => true,
                'trans' => $trans->fresh(),
                'push' => $push != null,
                'message' => $push != null ? 'Paiement en cours' : "La demande de paiement n'a pas abouti",
            ]);
        } catch (\Exception $e) {
            return response()->json(['statut' => false, 'message' => 'Une erreur s\'est produite']);
        }
    }

    /** Push Mobile Money du montant de l'opération. Retourne la référence ou null. */
    private function lancerPaiementOffre($trans, $paiement, $phone)
    {
        if (!$phone) {
            return null;
        }
        $payment = new PaymentController();
        if ($paiement == 'moov') {
            // La référence Pvit = référence de l'opération (callBackPvit)
            return $payment->getPaymentPvitIn($trans->montant, $phone, $trans->reference);
        }
        $push = $payment->amPushIn($phone, $trans->montant, 'GamPay_Offre');
        if ($push != null) {
            // callBackAM retrouve l'opération par la référence du push
            $trans->update(['reference' => strval($push)]);
        }
        return $push;
    }

    /** Détail unique d'un achat de crédit simple. */
    public function creerDetailCredit($trans, $receveur, $operateur, $reference, $amount)
    {
        $details = new DetatilsOperation([
            'operation' => 'achat_credit',
            'qte' => strval($amount),
            'name' => 'Crédit',
            'status' => 'wait',
            'validity' => null,
            'transaction_id' => $trans->id,
            'numclient' => $receveur,
            'operateur' => $operateur,
            'reference' => $reference,
        ]);
        $details->save();
        return [ceil($amount / 174), null, false];
    }

    /** Détails d'un forfait (même découpage que assistanceForfait). */
    public function creerDetailsOffre($forfait, $trans, $receveur, $operateur, $reference)
    {
        $lignes = [];
        $qteCallTime = null;
        $qteForfait = null;

        $ligne = function ($operation, $qte, $name, $validity) use ($trans, $receveur, $operateur, $reference) {
            return [
                'operation' => $operation,
                'qte' => $qte,
                'name' => $name,
                'status' => 'wait',
                'validity' => $validity,
                'transaction_id' => $trans->id,
                'numclient' => $receveur,
                'operateur' => $operateur,
                'reference' => $reference,
            ];
        };

        if ($forfait->qte_flex != null) {
            $lignes[] = $ligne('flex', $forfait->qte_flex, 'flex', $forfait->validity_flex);
            $qteCallTime = ceil($forfait->qte_flex / 174);
        }
        if ($forfait->qte_credit != null) {
            $lignes[] = $ligne('achat_credit', $forfait->qte_credit, 'Crédit', $forfait->validity_min);
            $qteCallTime = ($qteCallTime ?? 0) + ceil($forfait->qte_credit / 174);
        }
        if ($forfait->qte_min != null) {
            $lignes[] = $ligne(
                $operateur == 'AIRTEL_GA' ? 'achat_credit' : 'Appels',
                $operateur == 'AIRTEL_GA' ? $forfait->param2 : $forfait->qte_min,
                'Appels',
                $forfait->validity_min
            );
            $qteCallTime = ($qteCallTime ?? 0) + intval($forfait->qte_min);
        }
        if ($forfait->qte_mega != null) {
            $lignes[] = $ligne('achat_forfait', $forfait->qte_mega, 'achat_forfait', $forfait->validity_mega);
            $qteForfait = $forfait->qte_mega;
        }

        foreach ($lignes as $l) {
            (new DetatilsOperation($l))->save();
        }

        return [$qteCallTime, $qteForfait, $forfait->ticket_wifi != null];
    }

}
