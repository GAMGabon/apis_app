<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Historiquetrans;
use App\Models\OrderPushAM;
use App\Models\Customer;
use App\Models\DetatilsOperation;
use App\Models\Pvit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\AssistanceDataCredit;
use App\Models\ForfaitDataCredit;
use Exception;
class PaymentController extends Controller
{
    public function launchSummary(){
        $summary = $this->createTokenPushAM();
        return $summary;
    }
    
    public function createTokenPushAM()
    {
        // accès prod gampay
        $client_id = "1ff01e5a-78f3-431f-ac06-b01457562650";
        $client_secret = "be0c6692-eaa4-4863-9085-dac8d01cd853";
        $url = "https://openapi.airtel.ga/auth/oauth2/token";
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
      "client_id":"' . $client_id . '",
      "client_secret":"' . $client_secret . '",
      "grant_type":"client_credentials"
      }',
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Content-Type: application/json'
            ),
        ));

        $result = curl_exec($curl);
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                return $response['access_token'];
            } else {
                return [10, $result];
            }
        } else {
            return [100, $result];
        }
        // Close handle
        curl_close($curl);
    }
    
    public function createTransPushAM($montant, $phone,$object)
    {

        
        $tokenPush = "";
        $id = rand(1000, 100000);
        $message = $object;
        $urlCollection = "https://openapi.airtel.ga/merchant/v1/payments/";
        
        $max = 500000;
        $min = 100;

        if (str_contains($montant, ',')) {
            return response()->json([
                "status" => [
                    "response_code" => "DP00800001004",
                    "code" => "400",
                    "success" => false,
                    "result_code" => "ESB000008",
                    "message" => "Le montant de la transaction ne doit pas être null ou être un nombre décimal."
                ]
            ]);
        }

        if (str_contains($montant, '.')) {
            return response()->json([
                "status" => [
                    "response_code" => "DP00800001004",
                    "code" => "400",
                    "success" => false,
                    "result_code" => "ESB000008",
                    "message" => "Le montant de la transaction ne doit pas être null ou être un nombre décimal."
                ]
            ]);
        }

        if ($montant < $min) {
            return response()->json([
                "status" => [
                    "response_code" => "DP00800001004",
                    "code" => "400",
                    "success" => false,
                    "result_code" => "ESB000008",
                    "message" => "Le montant de la transaction ne doit pas être null et doit être supérieur à ou égal 100."
                ]
            ]);
        }

        if ($montant > $max) {

            return response()->json([
                "status" => [
                    "response_code" => "DP00800001004",
                    "code" => "400",
                    "success" => false,
                    "result_code" => "ESB000008",
                    "message" => "Le montant de la transaction ne doit pas être null et doit être inférieur à 500001."
                ]
            ]);
        }
        //return $request->montant;

        $token = $this->createTokenPushAM();
        if($token == null){
            return null;
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $urlCollection,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "reference": "' . $message . '",
                "subscriber": {
                    "country": "GA",
                    "currency": "CFA",
                    "msisdn": "' . $phone . '"
                },
                "transaction": {
                    "amount": ' . $montant . ',
                    "country": "GA",
                    "currency": "CFA",
                    "id": "' . $id . '"
                }
            }',
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Content-Type: application/json',
                'X-Country: GA',
                'X-Currency: CFA',
                "Authorization: Bearer $token"
            ),
        ));

        $result = curl_exec($curl);
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                return $result;
            } else {
                return response()->json([
                    "status" => [
                        "success" => false,
                        "message" => "Statut code error"
                    ]
                ]);
            }
        } else {
            return response()->json([
                "status" => [
                    "success" => false,
                    "message" => "cUrl error"
                ]
            ]);
        }
        // Close handle
        curl_close($curl);
    }
    
    public function summaryPushAM()
    {
        // accès prod gampay
        $tokenPush = "";
        $urlSummary = "https://openapi.airtel.ga/merchant/v1/transactions";
        
        $params = [
            "from"   => "",
            "to"     => "",
            "limit" =>10
        ];
        
        $baseUrl = $urlSummary;
        $fullUrl = $baseUrl . '?' . http_build_query($params);
        $token = $this->createTokenPushAM();
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Content-Type: application/json',
                'x-country: GA',
                'x-currency: CFA',
                'Authorization: Bearer ' . $token
            ),
        ));

        $result = curl_exec($curl);
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if ($response['status']['success'] == 'true') {
                    return $response;
                } else {
                    return $response['status']['message'];
                }
            } else {
                return [10, $result];
            }
        } else {
            return [100, $result];
        }
        // Close handle
        curl_close($curl);
    }
    
    public function newCallBackAMOfflineAppRequest(Request $request)
    {
        try {
            $getSummary = $this->summaryPushAM();
            $data = $getSummary;
            if ($data['status']['success'] == true) {
                //return $data['data']['transactions'];

                foreach ($data['data']['transactions'] as $item) {

                    try {

                        $airtelId = $item['transaction']['airtel_money_id'] ?? '';
                        // 1. Filtrer uniquement ceux qui commencent par MP2
                        //return $airtelId;
                        if (str_starts_with($airtelId, 'MP2') && $item['transaction']['status'] == 'TS' && $item['transaction']['id'] == null) {


                            // 2. Vérifier si l'ID existe déjà en base
                            $alreadyExists = Historiquetrans::where('param6', $airtelId)->get();
                            if (count($alreadyExists) == 0) {

                                $nowDate = date("Y-m-d", $item['transaction']['created_at']);
                                $numclient = null;
                                //$date = date("Y-m-d H:", strtotime("$nowDate - 1 hour"));
                                $customer = Customer::where('payments', 'like', "%{$item['payee']['msisdn']}%")->orWhere('config_sims', 'like', "%{$item['payee']['msisdn']}%")->first();
                                if ($customer) {
                                    $newData = json_decode($customer->config_sims, true);
                                    $slotRecherche = substr((string)intval($item['transaction']['amount']), -1);
                                    $researchMontant = intval($item['transaction']['amount']) - intval($slotRecherche);
                                    $frais = 0;
                                    $operation = 'achat_credit';
                                    $operateur = 'LIBERTIS';
                                    $slot = 0;
                                    $montant = intval($item['transaction']['amount']);
                                    if (!in_array($slotRecherche, ['1', '2', '3', '4', '5', '6', '7', '8', '9'])) {
                                        $trans = Historiquetrans::where('phonevendeur', $customer->phoneclient)->where('montant', intval($item['transaction']['amount']))->where('etat', 'attend')->where('created_at', 'like', "$nowDate%")->orderBy('id', 'desc')->first();
                                        if ($trans) {
                                            $trans->update([
                                                'etat' => $trans->operation == 'recharge_visa_uba' ? 'en_attente' : 'atraiter',
                                                'param6' => $airtelId
                                            ]);
                                            $otherTrans = DetatilsOperation::where('transaction_id', $trans->id)->get();
                                            if (count($otherTrans) > 0) {
                                                foreach ($otherTrans as $otherTran) {
                                                    DetatilsOperation::where('id', $otherTran->id)->update([
                                                        'reference' => $airtelId
                                                    ]);
                                                }
                                            }
                                        }
                                    } else {
                                        //définition de l'opérateur
                                        if (intval($slotRecherche) % 2 != 0) {
                                            $operateur = 'AIRTEL_GA';
                                        }
                                        //définition du slot
                                        if (!in_array($slotRecherche, ['1', '2', '5', '6'])) {
                                            $slot = 1;
                                        }
                                        //définition de l'opération et du montant des frais
                                        if (intval($slotRecherche) > 4 && intval($slotRecherche) < 9) {
                                            $operation = 'data_credit';
                                        } else if (intval($slotRecherche) == 9) {
                                            $operation = 'recharge_wallet';
                                        } else {
                                            if ($montant - intval($slotRecherche) == 120) {
                                                $montant = $montant - 20;
                                                $frais = 20;
                                            } else {
                                                $montant = $montant - 100;
                                                $frais = 100;
                                            }
                                        }

                                        for ($i = 0; $i < count($newData); $i++) {
                                            if (strval($newData[$i]['slot']) == strval($slot)) {
                                                $numclient = strval($newData[$i]['number']);
                                            }
                                        }

                                        $trans = new Historiquetrans([
                                            'operation' => $operation,
                                            'reference' => $airtelId,
                                            'etat' => 'atraiter',
                                            'numclient' => $numclient,
                                            'phonevendeur' => $customer->phoneclient,
                                            'content' => $operateur,
                                            'montant' => intval($item['transaction']['amount']),
                                            'montant_sans_frais' => $montant,
                                            'frais' => $frais,
                                            'solde' => $customer->solde,
                                            'origine_operation' => 'summary_offline',
                                            'id_customer' => $customer->id,
                                            'param6' => $airtelId
                                        ]);

                                        $trans->save();
                                        if($trans->id){
                                            $account = AssistanceDataCredit::where('customer_id', $customer->id)->first();
                                            if($account){
                                                $newSolde = $account->solde + intval($trans->montant_sans_frais);
                                                $account->update([
                                                    'solde' => $newSolde,
                                                    'depannage' => null
                                                ]);
                                                
                                                $trans->update([
                                                    'solde' => $account->solde
                                                ]);
                                            }
                                            if ($operation == 'data_credit') {
                                                $priceSearch = intval($item['transaction']['amount']) - intval($slotRecherche);
                                                $forfait = ForfaitDataCredit::where('price', strval($priceSearch))->where('operateur', $operateur == 'AIRTEL_GA' ? 'airtel' : 'moov')->first();
                                                if ($forfait) {
                                                    $details_operation = [];
                                                    array_push($details_operation, array(
                                                        'operation' => 'achat_credit',
                                                        'qte' => $forfait->qte_credit,
                                                        'name' => 'achat_credit',
                                                        'status' => 'wait',
                                                        'validity' => null,
                                                        'transaction_id' => $trans->id,
                                                        'numclient' => $numclient,
                                                        'operateur' => $operateur,
                                                        'reference' => $airtelId,
                                                    ));

                                                    if ($forfait->qte_flex != null) {
                                                        array_push($details_operation, array(
                                                            'operation' => 'flex',
                                                            'qte' => $forfait->qte_flex,
                                                            'name' => 'flex',
                                                            'status' => 'wait',
                                                            'validity' => $forfait->validity_flex,
                                                            'transaction_id' => $trans->id,
                                                            'numclient' => $numclient,
                                                            'operateur' => $operateur,
                                                            'reference' => $airtelId,
                                                        ));
                                                    }

                                                    if ($forfait->qte_min != null) {
                                                        array_push($details_operation, array(
                                                            'operation' => 'Appels',
                                                            'qte' => $forfait->qte_min,
                                                            'name' => 'Appels',
                                                            'status' => 'wait',
                                                            'validity' => $forfait->validity_min,
                                                            'transaction_id' => $trans->id,
                                                            'numclient' => $numclient,
                                                            'operateur' => $operateur,
                                                            'reference' => $airtelId,
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
                                                            'numclient' => $numclient,
                                                            'operateur' => $operateur,
                                                            'reference' => $airtelId,
                                                        ));
                                                    }

                                                    if ($forfait->ticket_wifi != null) {
                                                        // a constituer
                                                    }

                                                    for ($i = 0; $i < count($details_operation); $i++) {
                                                        $details = new DetatilsOperation($details_operation[$i]);
                                                        $details->save();
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        return response()->json([
                            'statut' => $e
                        ]);
                    }
                }
            }
            //$this->launchPause("https://gampay.org/gamclients/public/api/callBackAMOfflineAppRequest");
            return response()->json([
                'statut' => 'cest bon'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'statut' => null,
                'message'=> $e->getMessage()
            ]);
        }
    }
    
    // Apis airtel money
    public function amPush(Request $request){
        $push = $this->amPushIn($request->phone, $request->montant, $request->object);
        return $push;
    }

    public function amPushIn($phone, $montant, $object){
        try {
            $push = $this-> createTransPushAM($montant,$phone,'GamPay');
            $data = json_decode($push, true);
            if($data['status']['success'] == true){
                return $data['data']['transaction']['id'];
            }
            return null;

        }catch (\Exception $e){
            return null;
        }
    }

    public function amPushInApp(Request $request){
        $trans = Historiquetrans::where('id', $request->trans)->first();
        if($trans){
            $object = $this->getText($trans->operation);
            $push = $this->amPushIn($request->phone, $request->montant, $object);
            if($push == null){
                $trans->update(['reference' => strval($push)]);
            }
            return $push;
        }
        return null;
    }

    public function callBackAM(Request $request){
        $json = file_get_contents('php://input');

        $dataPost = json_decode($json, true);
        try {
            $data = json_encode($dataPost);
            $order = new OrderPushAM([
                'partenaire' => 'airtelMoneyPush',
                'code' => $dataPost['transaction']['code'],
                'status_code'=> $dataPost['transaction']['status_code'],
                'airtel_money_id'=> $dataPost['transaction']['airtel_money_id'],
                'id_trans'=> $dataPost['transaction']['id'],
                'message'=> $dataPost['transaction']['message'],
                'param2'=> $data,
            ]);
            $order->save();
            if($dataPost['transaction']['status_code'] == 'TS'){
                $trans = Historiquetrans::where('reference', $dataPost['transaction']['id'])->where('etat', 'attend')->first();
                if($trans){
                    $trans->update(['etat' => $trans->operation == 'recharge_visa_uba'? 'en_attente' : 'atraiter']);
                    $order->update(['treatment' => 'ok']);
                }
                
                $account = AssistanceDataCredit::where('ref_recharge', $dataPost['id_trans'])->first();
                if ($account) {
                    $trans = new Historiquetrans([
                        'operation' => 'recharge_wallet',
                        'reference' => $dataPost['transaction']['airtel_money_id'],
                        'etat' => 'CONFIRMEE',
                        'numclient' => $account->customer_phone,
                        'phonevendeur' => $account->customer_phone,
                        'content' => 'GAM',
                        'montant' => $account->amount_recharge,
                        'montant_sans_frais' => $account->amount_recharge,
                        'frais' => '0',
                        'solde' => $account->solde,
                        'origine_operation' => 'summary_online_push',
                        'id_customer' => $account->customer_id,
                        'param6' => $dataPost['transaction']['airtel_money_id']
                    ]);

                    $trans->save();
                    
                    $newSolde = $account->solde + intval($account->amount_recharge);
                    $account->update([
                        'solde' => $newSolde,
                        'ref_recharge' => null,
                        'number_charge' => null,
                        'amount_recharge' => null
                    ]);
                }
            }
            
            return 'ok';
        } catch (Exception $th) {
            $data = $request->json;
            $order = new OrderPushAM([
                'partenaire'=> 'airtelMoneyPush',
                'message' => $th->getMessage(),
                'all_data'=> $data,
                'param1'=> 'dans le catch'
                /*'transId'=> $data['transaction']['id'],
                'status'=> $data['transaction']['status_code'],
                'message'=> $data['transaction']['message'],
                'param1'=> $data['hash'],*/
            ]);
            $order->save();
            if(array_key_exists('transaction', $dataPost)){
                if(array_key_exists('status_code', $dataPost['transaction'])){
                    if($dataPost['transaction']['status_code'] == 'TS'){
                        if(array_key_exists('id', $dataPost['transaction'])){
                            $trans = Historiquetrans::where('reference', $dataPost['transaction']['id'])->where('etat', 'attend')->first();
                            if($trans){
                                $trans->update(['etat' => $trans->operation == 'recharge_visa_uba'? 'en_attente' : 'atraiter']);
                                $order->update(['treatment' => 'ok']);
                            }
                        }
                    }
                }
            }
            return 'ok';
        }
    }

    /************************************ Pvit Apis ***************************************/
    public function getTokenPvit(){

        $response = Http::asForm()->post('https://api.mypvit.pro/v2/7HISWKYV1LPYVCIB/renew-secret', [
            'operationAccountCode' => 'ACC_69DC9B49A4C9E',
            'password' => 'IT@Team13$://'
        ]);

        if ($response->successful()) {
            return $response->json();
        }else{
            return null;
        }
    }

    public function getPaymentPvit(Request $request){
        $response = $this->getPaymentPvitIn($request->montant, $request->phone, $request->ref);
        return $response;
    }

    public function getPaymentPvitIn($montant, $phone, $ref){
        $token = $this->getTokenPvit();
        if($token == null){
            return null;
        }
        $response = Http::withHeaders([
            'X-Secret' => $token['secret'],
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->post('https://api.mypvit.pro/v2/EH0H6X2E7EL66NNE/rest', [
            "agent"=> "AGENT-1",
            "amount"=> intval($montant),
            "callback_url_code"=> "SRR3W",
            "customer_account_number"=> $phone,
            "merchant_operation_account_code"=> "ACC_69DC9B49A4C9E",
            "transaction_type"=> "PAYMENT",
            "owner_charge"=> "CUSTOMER",
            "owner_charge_operator"=> "CUSTOMER",
            "free_info"=> "INFORMATION",
            "product"=> "PRODUIT TEL",
            "operator_code"=> "MOOV_MONEY",
            "reference"=> $ref,
            "service"=> "RESTFUL"
        ]);

        if ($response->successful()) {
            return $ref;
        }else{
            return null;
        }
    }

    public function getInfoCustomerPvit(Request $request){
        return $this->getInfoCustomerPvitIn($request->operateur, $request->phone);
    }
    
     public function getInfoCustomerPvitIn($operateur, $phone){
        $token = $this->getTokenPvit();
        if($token == null){
            return 'error';
        }
        
        $operateurValue = "MOOV_MONEY";
        if($operateur == 'airtel'){
            $operateurValue = "AIRTEL_MONEY"; 
        }

        $response = Http::withHeaders([
            'X-Secret' => $token['secret']
        ])->get('https://api.mypvit.pro/v2/JLDR1PAQW6WXZFWI/kyc', [
            "customerAccountNumber"=> $phone,
            "operatorCode"=> $operateurValue
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data']['firstname'];
        }else{
            return null;
        }
    }

    public function getStatusTransPvit(Request $request){
        $token = $this->getTokenPvit();
        if($token == null){
            return 'error token';
        }

        $response = Http::withHeaders([
            'X-Secret' => $token['secret']
        ])->get('https://api.mypvit.pro/T9UWJ0WPVW8RK0CZ/status', [
            "transactionId"=> $request->ref,
            "accountOperationCode"=> "ACC_69DC9B49A4C9E",
            "transactionOperation"=> "PAYMENT"
        ]);

        if ($response->successful()) {
            return $response->json();
        }else{
            return [null, $response->body()];
        }
    }

    public function callBackPvit(Request $request)
    {
        $json = file_get_contents('php://input');
        $response1 = null;
        $response2 = null;
        try {
            $dataPost = json_decode($json, true);
            $order = new Pvit($dataPost);
            $order->save();
            $response1 = $dataPost['transactionId'];
            $response2 =  $dataPost['code'];
            
            if(str_contains($dataPost['merchantReferenceId'],'wallet') && $dataPost['status'] == 'SUCCESS'){
               $account = AssistanceDataCredit::where('ref_recharge', $dataPost['merchantReferenceId'])->first();
                if ($account) {
                    
                     $trans = new Historiquetrans([
                        'operation' => 'recharge_wallet',
                        'reference' => $dataPost['merchantReferenceId'],
                        'etat' => 'CONFIRMEE',
                        'numclient' => $account->customer_phone,
                        'phonevendeur' => $account->customer_phone,
                        'content' => 'GAM',
                        'montant' => $dataPost['amount'],
                        'montant_sans_frais' => $dataPost['amount'],
                        'frais' => '0',
                        'solde' => $account->solde,
                        'origine_operation' => 'summary_online_push',
                        'id_customer' => $account->customer_id,
                        'param6' => $dataPost['merchantReferenceId']
                    ]);

                    $trans->save();
                    
                    $newSolde = $account->solde + intval($dataPost['amount']);
                    $account->update([
                        'solde' => $newSolde,
                        'ref_recharge' => null,
                        'number_charge' => null,
                        'amount_recharge' => null
                    ]);
                }
            }

        } catch (Exception $th) {
            $dataPost = json_decode($json, true);
            $order = new Pvit([
                "transactionId"=> $dataPost["transactionId"],
                "merchantReferenceId"=>  $dataPost["merchantReferenceId"],
                "status"=>  $dataPost["status"],
                "amount"=>  $dataPost["amount"],
                "customerID"=>  $dataPost["customerID"],
                "totalAmount"=> $dataPost["totalAmount"],
                "all_data" => $json,
                "param1" => $th->getMessage(),
            ]);
            $order->save();
            $response1 = $dataPost['transactionId'];
            $response2 =  $dataPost['code'];
 
        } finally {
            if($response1 == null){
                $order = new Pvit([
                    "all_data" => $json,
                    'treatment' => 'alerte'
                ]);
                $order->save();
            }
           
        }
        
        if($response1 != null){
           if($dataPost['status'] == "SUCCESS"){
                $trans = Historiquetrans::where('reference', $dataPost['merchantReferenceId'])->where('etat', 'attend')->first();
                if($trans){
                    $trans->update(['etat' => $trans->operation == 'recharge_visa_uba'? 'en_attente' : 'atraiter']);
                    $order->update(['treatment' => 'ok']);
                }
            } 
        }
        
        return response()->json([
            'transactionId'=> $response1,
            'responseCode'=> $response2,
        ]);
       
    }
    
    public function chargeWalletWithPush(Request $request){
        try {
            if($request->operateur == 'moov'){
                $push =  $this->getPaymentPvitIn($request->amount, $request->phone, 'wallet'.time());
            }else{
                $push =  $this->amPushIn($request->phone,$request->amount, 'GamPay_Wallet');
            }

            if($push != null){
                AssistanceDataCredit::where('customer_id', $request->customer)->update([
                    'ref_recharge' => $push,
                    'number_recharge' => $request->phone,
                    'amount_recharge' => $request->amount
                ]);

                return response()->json([
                    'statut'=> true,
                    'data'=> $push,
                    'message'=> 'Transaction en cours'
                ]);
            }else{
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Une erreur s\'est produite '
                ]);
            }
            
        }catch (Exception $e){
            return response()->json([
                'statut'=> false,
                'message'=> 'Une erreur s\'est produite '.$e->getMessage()
            ]);
        }
        return null;
    }

}
