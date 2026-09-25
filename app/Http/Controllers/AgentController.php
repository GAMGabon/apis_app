<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DetatilsOperation;
use App\Models\Historiquetrans;
use App\Models\AssistanceDataCredit;
use App\Models\GeneralController;
use App\Models\ForfaitDataCredit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Exception;

class AgentController extends Controller
{
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



                        /*if($reload){
                            Historiquetrans::where('id', $tran->id)->update([
                                'param2' => null,
                                'etat' => $etat,
                                'operation'=> $operation,
                                'content' => $content,
                                'timestamps' => date('Y-m-d H:i:s'),
                                'code_validation' => 'traite',
                            ]);
                        }*/
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
        $account = AssistanceDataCredit::where('customer_id', $request->customer)->first();
        if ($account) {
            $account->update([
                'operateur' => $request->operateur,
                'amount_recharge' => $request->amount,
                'defaut_forfait' => $request->forfait
            ]);
            return response()->json([
                'statut' => true,
                'body' => $account
            ]);
        } else {
            $newWallet = new AssistanceDataCredit([
                'customer_id' => $request->customer,
                'customer_phone' => $request->phone,
                'defaut_forfait' => $request->forfait,
                'operateur' => $request->operateur
            ]);
            $newWallet->save();

            return response()->json([
                'statut' => true,
                'body' => $newWallet
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
                return response()->json([
                    'statut' => true,
                    'time' => $time,
                    'message' => 'call time recharge ok'
                ]);
            }
        }
    }

    public function rechargeCallTimeOrData(Request $request){
        return $this->rechargeCallTimeOrDataIn($request->trans, $request->receveur, $request->operation, $request->qte_credit, $request->qte_data,$request->amount);
    }

    public function rechargeCallTimeOrDataIn($trans, $receveur, $operation, $qte_credit, $qte_data,$amount){
        $response = [
            'statut' => false,
            'message' => 'no account'
        ];
        $status_operation = 'point_wallet_no_charge';
        try {
            $customers = DB::select(
                "select * from customer where config_sims like ? or config_sims like ? limit 1",
                ["%{$receveur}%", "%0{$receveur}%"]
            );
            if(count($customers)>0){
                foreach ($customers AS $customer){
                    $wallet = AssistanceDataCredit::where('customer_id', $customer->id)->first();
                    if ($wallet) {
                        $qteRechargeCallTime = doubleval($wallet->call_time_recharge);
                        $qteRechargeData = doubleval($wallet->forfait_qte_recharge);
                        if($operation == 'achat_credit'){
                            if($qte_credit == null){
                                $qteRechargeCallTime = $qteRechargeCallTime + ceil($amount/174);
                            }else{
                                $qteRechargeCallTime = $qteRechargeCallTime + $qte_credit;
                            }
                        }else if($operation == 'data_credit') {
                            if($qte_credit == null){
                                $qteRechargeCallTime = $qteRechargeCallTime + ceil($amount/174);
                            }else{
                                $qteRechargeCallTime = $qteRechargeCallTime + $qte_credit;
                            }
                            $qteRechargeData = $qteRechargeData + $qte_data;
                        }else{

                            $qteRechargeData = $qteRechargeData + $qte_data;
                        }
                        $wallet->update([
                            'forfait_qte_recharge' => $qteRechargeData,
                            'call_time_recharge' => $qteRechargeCallTime
                        ]);
                        $status_operation = 'point_wallet_charge';
                        $response = [
                            'statut' => true,
                            'message' => 'wallet rechergé',
                            'body' => $wallet
                        ];
                    } else {
                        $response = [
                            'statut' => false,
                            'message' => 'no wallet',
                            'account' => $customer
                        ];
                    }
                }

            }
        }catch (\Exception $th) {
            return response()->json([
                'statut' => false,
                'action' => 'error ' . $th->getMessage()
            ]);
        }

        Historiquetrans::where('id', $trans)->update([
            'param5' => $status_operation
        ]);
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

}
