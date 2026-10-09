<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Models\Customer;
use App\Models\DetatilsOperation;
use App\Models\GamElectriciteHist;
use App\Models\Historiquetrans;
use App\Models\Orders;
use App\Models\ProgramSubscription;
use App\Models\ForfaitDataCredit;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransactionsController
{
    protected $general;
    protected $alertes;
    public function __construct(Request $request)
    {
        $this->general = new GeneralController();
        $this->alertes = new AlertesController();
    }

    public function newTransaction (Request $request){
        $push = null;
        $data = $request->transaction;
        try {
            if($data['operation'] == 'recharge_visa_uba'){
                if(intval($data['montant_sans_frais']) < 2000){
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Le montant minimum est de 2000'
                    ]);
                }
            }

            $code = null;
            try{
                if(in_array($data['operation'],['data_credit','data_credit_plus','data_credit_flex', 'forfait_credit']) && !empty($data['param3'])){
                    $detais = json_decode($data['param3'], true);
                    for ($i=0; $i<count($detais); $i++) {
                        $forfait = $detais[$i];
                        if($forfait['name'] == 'Appels'){
                            $code = $this->codeEnableCallForfait($forfait['qte'], str_replace(" ","",$data['numclient']));
                        }
                    }
                }
            }catch (Exception $e){}
            
            $content = $data['content'];
            $numcleint = str_replace(" ","",$data['numclient']);
            
            $debut = $numcleint[0] . $numcleint[1];
            if ($debut == '06' && $data['operation'] == 'data_credit') {
                $content = 'LIBERTIS';
            }
            
            if ($debut == '07' && $data['operation'] == 'data_credit') {
                $content = 'AIRTEL_GA';
            }

            $trans = new Historiquetrans([
                'operation' => $data['operation'],
                'reference' => $data['reference'] ,
                'etat' => $data['etat'],
                'numclient' => str_replace(" ","",$data['numclient']),
                'phonevendeur' => $data['phonevendeur'],
                'content'=> $content,
                'montant' => $data['montant'],
                'montant_sans_frais' => $data['montant_sans_frais'],
                'frais' => $data['frais'],
                'solde' => $data['solde'],
                'origine_operation' => $data['origine_operation'],
                'id_customer' =>$data['id_customer'],
                'param1' =>$data['param1'],
                'param2' =>$data['param2'],
                'param3' => $data['param3'],
                'param4' => empty($code)? $data['param4'] : $code,
                'param5' => $data['param5'],
                'param7' => $data['param7'],
                'param8' => $data['param8'],
                'param9' =>  $data['param9']
            ]);
            $trans->save();
            
            if ($trans->operation == 'data_credit') {
                $this->createDetailsPackInApp($trans);
            }

            $link = $this->getUssdCode(strval($trans->id), $trans->montant, $data['payment_methode']);
            $customer = Customer::where('phoneclient', $data['phonevendeur'])->first();

            try{ // add details operation
                if(in_array($data['operation'],['data_credit','data_credit_plus','data_credit_flex','forfait_credit']) && !empty($data['param3'])){
                    $details = json_decode($data['param3'], true);
                    $this->addDetalsOperation($details, $trans);
                }
            }catch (Exception $e){}

            if($customer){
                if($request->push != 'no_push'){
                    $object = $this->getText($trans->operation);
                    $push = $this->verifandLaunchPush($customer, $trans, $data['payment_methode'], $object, $request->push);
                }
                
                Customer::where('id', $customer->id)->update([
                    'last_message' => $data['message'],
                    'last_message_time' =>  date("Y-m-d H:i:s"),
                    'no_read' => $customer->no_read +1
                ]);

                if($push != null){
                    $trans->update(['reference' => $push[0]]);
                }
                /*$name = $customer->nom;
                $message = $data['message'];
                $agents = DB::select("SELECT * from customer where username like '%gamSC%'");
                foreach ($agents as $agents){
                    // Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$agents->unlock_token&app=android&message=$message&titre=$name");
                }*/
            }
            return response()->json([
                'statut'=> true,
                'link' => $push != null? 'push' : $link,
                'other' => $link,
                'phonePush' => $push != null? $push[1] : null,
                'message'=> 'Une erreur s\'est produite',
                'body'=> $trans,
            ]);

        }catch (Exception $e){
            return response()->json([
                'statut'=> false,
                'message'=> 'Une erreur s\'est produite'
            ]);
        }
    }

    public function newTransactionGam (Request $request){
        $data = $request->transaction;
        try {

            if($data['operation'] == 'recharge_visa_uba'){
                if(intval($data['montant_sans_frais']) < 2000){
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Le montant minimum est de 2000'
                    ]);
                }
            }

            $customer = Customer::where('phoneclient', $data['phonevendeur'])->first();

            $verifMySoldeCustomer = 'ko';
            $newSolde = doubleval($customer->solde) - doubleval($data['montant']);

            if($data['param8'] == 'partenaire'){
                if($newSolde > - 5000){
                    $verifMySoldeCustomer = 'ok';
                }
            }else{
                if($newSolde > 0){
                    $verifMySoldeCustomer = 'ok';
                }
            }

            if($verifMySoldeCustomer == 'ko'){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Votre solde GamPay est insuffissant'
                ]);
            }

            $code = null;
            try{
                if(in_array($data['operation'],['data_credit', 'forfait_credit']) && !empty($data['param3'])){
                    $detais = json_decode($data['param3'], true);
                    for ($i=0; $i>count($detais); $i++) {
                        $forfait = $detais[$i];
                        if($forfait['name'] == 'Appels'){
                            $code = $this->codeEnableCallForfait($forfait['qte'], str_replace(" ","",$data['numclient']));
                        }
                    }
                }
            }catch (Exception $e){}

            $trans = new Historiquetrans([
                'operation' => $data['operation'],
                'reference' => $data['reference'] ,
                'etat' => $data['etat'],
                'numclient' => str_replace(" ","",$data['numclient']),
                'phonevendeur' => $data['phonevendeur'],
                'content'=> $data['content'],
                'montant' => $data['montant'],
                'montant_sans_frais' => $data['montant_sans_frais'],
                'frais' => $data['frais'],
                'solde' => $customer->solde,
                'origine_operation' => $data['origine_operation'],
                'id_customer' =>$data['id_customer'],
                'param1' =>$data['param1'],
                'param2' =>$data['param2'],
                'param3' => $data['param3'],
                'param4' => empty($code)? $data['param4'] : $code,
                'param5' => $data['param5'],
                'param7' => $data['param7'],
                'param8' => $data['param8'],
                'param9' =>  $data['param9']
            ]);
            $trans->save();

            Customer::where('id', $customer->id)->update([
                'solde' => strval($newSolde)
            ]);

            return response()->json([
                'statut'=> true,
                'body'=> $trans,
                'link' => '',
                'message'=> 'Transaction en cours'
            ]);

        }catch (Exception $e){
            return response()->json([
                'statut'=> false,
                'message'=> 'Une erreur s\'est produite'
            ]);
        }
    }


    public function newTransactionRm (Request $request){
        $data = $request->transaction;
        try {
            $customer = Customer::where('phoneclient', $data['phonevendeur'])->first();

            if(doubleval($data['montant']) > doubleval($customer->solde)){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Votre solde GamPay est insuffissant'
                ]);
            }

            $frais = 0;
            if($data['operation'] != 'achat_credit'){
                $frais = doubleval($data['montant_sans_frais']) * 0.025;
            }
            $amountTotal =  intval(round(doubleval($data['montant_sans_frais']) + $frais));

            $trans = new Historiquetrans([
                'operation' => $data['operation'],
                'reference' => $data['reference'] ,
                'etat' => $data['etat'],
                'numclient' => str_replace(" ","",$data['numclient']),
                'phonevendeur' => $data['phonevendeur'],
                'content'=> $data['content'],
                'montant' => strval($amountTotal),
                'montant_sans_frais' => $data['montant_sans_frais'],
                'frais' => strval($frais),
                'solde' => $customer->solde,
                'origine_operation' => $data['origine_operation'],
                'id_customer' =>$data['id_customer'],
                'param1' =>$data['param1'],
                'param2' =>$data['param2'],
                'param3' => $data['param3'],
                'param4' => $data['param4'],
                'param5' => $data['param5'],
                'param7' => $data['param7'],
                'param8' => 'partenaire',
                'param9' =>  $data['param9']
            ]);
            $trans->save();
            Customer::where('id', $customer->id)->update([
                'solde' => strval(doubleval($customer->solde) - $amountTotal)
            ]);

            return response()->json([
                'statut'=> true,
                'body'=> $trans,
                'link' => '',
                'message'=> 'Transaction en cours'
            ]);

        }catch (Exception $e){
            return response()->json([
                'statut'=> false,
                'message'=> 'Une erreur s\'est produite'
            ]);
        }
    }

    public function newTransactionVisa (Request $request){
        $data = $request->transaction;
        try {
            $order = $this->orderOrabank(strval($data['montant']));
            if($order['status'] == 0){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Une erreur s\'est produite'
                ]);
            }
            $trans = new Historiquetrans([
                'operation' => $data['operation'],
                'reference' => $data['reference'] ,
                'etat' => $data['etat'],
                'numclient' => str_replace(" ","",$data['numclient']),
                'phonevendeur' => $data['phonevendeur'],
                'content'=> $data['content'],
                'montant' => $data['montant'],
                'montant_sans_frais' => $data['montant_sans_frais'],
                'frais' => $data['frais'],
                'solde' => $data['solde'],
                'origine_operation' => $data['origine_operation'],
                'id_customer' =>$data['id_customer'],
                'param1' =>$data['param1'],
                'param2' =>$data['param2'],
                'param3' => $data['param3'],
                'param4' => $data['param4'],
                'param5' => $data['param5'],
                'param7' => $data['param7'],
                'param8' =>  $order['body']['_embedded']['payment'][0]['orderReference'],
                'param9' =>  $data['param9']
            ]);
            $trans->save();

            return response()->json([
                'statut'=> true,
                'body'=> $trans,
                'link' => $order['body']['_links']['payment']['href'],
                'message'=> 'Transaction en cours'
            ]);

        }catch (Exception $e){
            return response()->json([
                'statut'=> false,
                'message'=> 'Une erreur s\'est produite'
            ]);
        }
    }

    public function getHistoric(Request $request){

        $numSansZero = substr($request->numCustomer, 1);
        $numeroZero = $request->numCustomer;
        $id= $request->idCustomer;
        $now = date("Y-m-d");

        if(!empty($request->search)){
            $search = $request->search;
            $hisrory = Historiquetrans::where(function($q) use($numeroZero, $search) {
                $q->where('numclient', 'like', '%'.$search.'%')->orWhere('montant_sans_frais', 'like', '%'.$search.'%')->orWhere('tentative_autorise', 'like', '%'.$search.'%')->orWhere('created_at', 'like', '%'.$search.'%');
            })->where('phonevendeur',$numeroZero)->whereNotIn('etat',['attend'])->whereNotIn('operation',['paiement_partenaire', 'debit'])->orderBy('created_at', 'desc')->take(20)->get();

        }else if($request->operation == '' || empty($request->operation)){
            $hisrory = Historiquetrans::where(function($q) use($numeroZero,$numSansZero) {
                $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero);
            })->whereNotIn('etat',['attend'])->whereNotIn('operation',['paiement_partenaire', 'debit'])->orderBy('created_at', 'desc')->take(100)->get();
        }
        elseif ($request->operation == 'visa'){
            $mesCartes = DB::table('carte_information')
                ->select('id_client')
                ->where('id_customer', $request->idCustomer);
            $hisrory = Historiquetrans::where(function($q) use($numeroZero,$mesCartes) {
                $q->whereIn('content', $mesCartes)->orWhere('phonevendeur',$numeroZero);
            })->whereNotIn('etat',['attend'])->whereIn('operation',[ 'recharge_visa_uba', 'achat_visa_uba','livraison_visa_uba'])->where('etat','!=','QR')->orderBy('created_at', 'desc')->take(25)->get();
        }
        elseif ($request->operation == 'edan'){
            $hisrory = Historiquetrans::where(function($q) use($numeroZero,$numSansZero){
                $q->where('phonevendeur',$numeroZero);
            })->whereNotIn('etat',['attend'])->where('operation', 'achat_unites_electrique')->where('etat','!=','QR')->orderBy('created_at', 'desc')->take(25)->get();
            //$hisrory = GamElectriciteHist::where('num_client', $request->numCustomer)->where('etat','!=','QR')->orderBy('created_at', 'desc')->get();
        }else if($request->operation == 'paiement'){
            $hisrory = Historiquetrans::where(function($q) use($numeroZero,$numSansZero, $id) {
                $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero)->orWhere('id_customer',$id);
            })->whereIn('operation',[ 'paiement_partenaire', 'achat_action', 'debit', 'paiement'])->orderBy('created_at', 'desc')->take(50)->get();
        }
        else if($request->operation == 'partenaire'){
            $id= $request->idCustomer;
            $num= $request->numCustomer;
            if($numeroZero == '076334432'){
                $hisrory = Historiquetrans::whereIn('operation',['achat_credit','achat_wifi','rendu_monnaie_simple', 'rendu_monnaie_credit','recharge_compte_gam'])->whereNotIn('etat',['attend'])->where(function($q) use($id,$num, $numSansZero) {
                    $q->whereIn('numclient', [$num, $numSansZero])->orWhere('phonevendeur', $num)->orWhere('id_customer',$id);
                })->where('created_at','like', ''.strval($now).'%')->orderBy('id', 'desc')->get();
            }else {
                $hisrory = Historiquetrans::whereIn('operation',['achat_credit', 'rendu_monnaie_simple', 'rendu_monnaie_credit','recharge_compte_gam'])->whereNotIn('etat',['attend'])->where(function($q) use($id,$num, $numSansZero) {
                    $q->whereIn('numclient', [$num, $numSansZero])->orWhere('phonevendeur', $num)->orWhere('id_customer',$id);
                })->orderBy('created_at', 'desc')->take(150)->get();
            }

        }else if($request->operation == 'transfert'){
            $hisrory = Historiquetrans::where(function($q) use($numeroZero,$numSansZero) {
                $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero);
            })->whereNotIn('etat',['attend'])->whereIn('operation',['rendu_monnaie_simple','transfert_ria','gam_transfert', 'transfert_mobile', 'transfert_visa', 'recharge_compte_gam'])->orderBy('created_at', 'desc')->take(25)->get();
        }else if($request->operation == 'forfait'){
            $hisrory = Historiquetrans::where(function($q) use($numeroZero,$numSansZero, $id) {
                $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero)->orWhere('id_customer',$id);
            })->whereNotIn('etat',['attend'])->whereIn('operation',[ 'esim_international', 'sim_international', 'achat_forfait', 'forfait_international', 'achat_wifi'])->orderBy('created_at', 'desc')->take(25)->get();
        }else if($request->operation == 'agent'){
            $hisrory = Historiquetrans::where(function($q) use($numeroZero,$numSansZero, $id) {
                $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero)->orWhere('id_customer',$id);
            })->whereNotIn('etat',['attend'])->whereIn('operation',[ 'demande_solde'])->orderBy('created_at', 'desc')->take(25)->get();
        }
        else if($request->operation == 'paymentWait'){
            $hisrory = Historiquetrans::where('phonevendeur',$numeroZero)->whereIn('etat',['attend', 'en_agence_attend'])->orderBy('created_at', 'desc')->take(25)->get();
        }else if($request->operation == 'commande_gampay'){
            if(!empty($request->etat)){
                $hisrory = Historiquetrans::where('operation', $request->operation)->where('etat',$request->etat)->orderBy('created_at', 'desc')->take(50)->get();
            }else{
                $hisrory = Historiquetrans::where('operation', $request->operation)->where('phonevendeur',$numeroZero)->orderBy('created_at', 'desc')->take(50)->get();
            }
        }else{
            $hisrory = Historiquetrans::where(function($q) use($numeroZero,$numSansZero) {
                $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero);
            })->whereNotIn('etat',['attend'])->where('operation', $request->operation)->orderBy('created_at', 'desc')->take(25)->get();
        }

        if (count($hisrory)>0) {
            if($request->operation == 'paiement'){
                $last = Historiquetrans::where(function($q) use($numeroZero,$numSansZero, $id) {
                    $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero)->orWhere('id_customer',$id);
                })->whereIn('operation',[ 'paiement_partenaire', 'debit', 'paiement'])->orderBy('created_at', 'desc')->take(1)->get();
                return json_encode([
                    'statut'=> true,
                    'customer'=> $hisrory,
                    'last'=>$last
                ]);
            }else{
                return json_encode([
                    'statut'=> true,
                    'customer'=> $hisrory,
                ]);
            }
        }else {
            if($request->operation == 'paiement'){
                return json_encode([
                    'statut'=> true,
                    'customer'=> $request->operationNUll,
                    'last'=>$request->operationNUll
                ]);
            }
            return json_encode([
                'statut'=> false,
                'customer'=> 'Aucunes transaction efféctuées',
            ]);
        }
    }

    public function orabankGetAcces(){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api-gateway.orabankga.ngenius-payments.com/identity/auth/access-token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/vnd.ni-identity.v1+json","Authorization: Basic MmY2NWFhMTItODdmNi00YjFkLWI2Y2QtM2RlODgzNDY0NTk4OjAyYWI2NzQ3LTM5ZjEtNDk3My1iOGFmLTJjYWU3ZTQwMTBiNA=="));
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $result = curl_exec($ch);
        // Check HTTP status code
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ( $http_code == 200 ||  $http_code == 201) {
                $token = json_decode($result, true);
                return ['status'=>1, 'body'=>$token['access_token']];
            }else{
                return ['status'=>0, 'body'=>'error token', 'http_code' =>$http_code ];
            }
        }
        // Close handle
        curl_close($ch);
    }

    public function orabankTransStatus($ref, $token){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api-gateway.orabankga.ngenius-payments.com/transactions/outlets/d8600d10-7d5c-437c-8aec-58a1ad899709/orders/".$ref);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer ". $token));
        $result = curl_exec($ch);

        // Check HTTP status code
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ( $http_code == 200 ||  $http_code == 201) {
                $tatus = json_decode($result, true);
                return $tatus['_embedded']['payment'][0]['state'];
            }else{
                return 0;
            }
        }
        // Close handle
        curl_close($ch);
    }


    public function orderStatusOrabank(Request $request){

        if(empty($request->ref)){


            $orders = Orders::where('partenaire', 'orabank')->where('event', 'PURCHASED')->whereNull('status')->take(5)->get();
        }else{
            $orders = Orders::where('partenaire', 'orabank')->where('event', 'PURCHASED')->where('reference',$request->ref)->get();
            $this->sendNotificationInprogram('074582442', '074582442', 'TEST CALBACK', '','TEST SINGLE API VISA PROD', '', '');
        }
        if(count($orders)>0){
            foreach ($orders as $order){
                Orders::where('reference', $order['reference'])->update([
                    'status' => 'true'
                ]);

                $operationWhatsApp = 'opération';
                $nomAgent = 'votre gestionnaire';
                $customer = '';

                $agents = Customer::where('username', 'gam_international')->orderBy('id', 'desc')->get();// recupère la liste des agents
                $transValideVisa =  Historiquetrans::where('param8', $order['reference'])->first();
                if(!empty($transValideVisa)){

                    $customer = $transValideVisa->phonevendeur;
                    switch ($transValideVisa->operation){
                        case 'recharge_visa_uba':
                            $operationWhatsApp = 'recharge visa';
                            break;
                        case 'achat_credit':
                            $operationWhatsApp = 'achat de crédit';
                            break;
                        case 'transfert_visa':
                            $operationWhatsApp = 'transfert mobile';
                            break;
                        case 'achat_forfait':
                            $operationWhatsApp = 'achat de forfait';
                            break;
                        case 'sim_international': case 'esim_international':
                        $operationWhatsApp = 'achat de sim internationale';
                        break;
                        case 'forfait_international':
                            $operationWhatsApp = 'achat forfait international';
                            break;
                    }

                    if($transValideVisa->operation == 'recharge_compte_gam' || $transValideVisa->operation == 'gam_transfert'){
                        $client = Customer::whereIn('phoneclient', ['0'.$transValideVisa->numclient,$transValideVisa->numclient])->first();
                        $newSoldeClient = strval(doubleval($transValideVisa->montant_sans_frais) + doubleval($client->solde));
                        Customer::where('id',$client->id)->update(['solde'=>$newSoldeClient]);

                        Historiquetrans::where('id', $transValideVisa->id)->update([
                            'etat' => 'CONFIRMEE',
                        ]);
                        $this->sendNotificationInprogram($transValideVisa->phonevendeur,$transValideVisa->numclient,'recharge_compte_gam', $transValideVisa->montant_sans_frais,null,null, null  );
                    }elseif($transValideVisa->operation == 'paiement_partenaire') {
                        $client = Customer::where('phoneclient', $transValideVisa->phonevendeur)->first();
                        if($client->etat == 'lock' || $client->statut == 'closed' ){
                            Historiquetrans::where('id', $transValideVisa->id)->update([
                                'etat' => 'locked'
                            ]);
                        }else{
                            Historiquetrans::where('id', $transValideVisa->id)->update([
                                'etat' => 'atraiter',
                                'param2'=> 'visa'
                            ]);
                        }
                    }else{
                        $client = Customer::where('phoneclient', $transValideVisa->phonevendeur)->first();
                        if($client->etat == 'lock' || $client->statut == 'closed'){
                            Historiquetrans::where('id', $transValideVisa->id)->update([
                                'etat' => 'locked'
                            ]);
                        }else{
                            Historiquetrans::where('id', $transValideVisa->id)->update([
                                'etat' =>  $transValideVisa->operation =='recharge_visa_uba'? 'en_attente':'atraiter',
                            ]);
                        }
                    }

                    if($agents){
                        foreach ($agents as $agent){
                            $message = "$transValideVisa->operation de $transValideVisa->montant_sans_frais vers le $transValideVisa->numclient effectué via VISA ";
                            $this->sendNotificationInprogram($agent['phoneclient'], $agent['phoneclient'], 'info', '',$message, '', '');
                        }
                    }

                    $this->storePriority($transValideVisa->phonevendeur, $transValideVisa->operation );

                }else{
                    $transValideVisaEdan = GamElectriciteHist::where('param8', $order['reference'])->first();
                    $operationWhatsApp = "achat d'unité edan";
                    $customer = $transValideVisaEdan->num_client;
                    if(!empty($transValideVisaEdan)){
                        GamElectriciteHist::where('id', $transValideVisaEdan->id)->update([
                            'etat' => 'attente',
                        ]);

                        if($agents){
                            foreach ($agents as $agent){
                                $message = "Recharge Edan de $transValideVisaEdan->montant_transaction pour le $transValideVisa->num_compteur éffectué via VISA";
                                $this->sendNotificationInprogram($agent['phoneclient'], $agent['phoneclient'], 'info', '',$message, '', '');
                            }
                        }

                        $this->storePriority($transValideVisaEdan->num_client, 'achat_edan');
                    }
                }

                if($customer!=''){
                    $myCustomer = Customer::where('phoneclient', $customer)->first();
                    $messageclientWhatsApp = "Cher client votre $operationWhatsApp via votre carte VISA est en cours de traitement.

                Je suis $nomAgent je reste disponible pour toute vos préocupations.

                contactez-moi directement via ce lien : https://api.whatsapp.com/send?phone=24105949831";
                    if($myCustomer && in_array($customer,['074582442', '074595435']) ){
                        if(empty($myCustomer->whatsapp)){
                            $whatsapp = $myCustomer->phoneclient;
                        }else{
                            $whatsapp = $myCustomer->whatsapp;
                        }
                        http::get("https://gampay.app/gamclients/public/api/sendWhatsAppMessageGet?service=international&message=$messageclientWhatsApp&pays=$myCustomer->pays&phone=$whatsapp");
                    }
                }

            }
            return[
                "reponse"=> "true",
                "orders"=> $orders
            ];
        }
        return[
            "reponse"=> "false",
            "orders"=> $orders
        ];
    }

    public function orderOrabank($montant){
        $data = [
            "action"=> "PURCHASE",
            "amount" => [
                "currencyCode" => "XAF",
                "value" => strval($montant)
            ]
        ];

        $recupToken = ProgramSubscription::where('id', 34357)->first();
        if(date('Y-m-d H:i', strtotime($recupToken->updated_at.'+ 5 min')) <= date('Y-m-d H:i') || $recupToken->param1 == null){
            //$tokenGenerate = Http::get('https://3ef541be3555.ngrok.app/gamclients/public/api/storeTokenOrabank')->body();
            $tokenGenerate =  $this->orabankGetAcces();
            if($tokenGenerate['status'] != 0){
                ProgramSubscription::where('id', 34357)->update([
                    'param1' => $tokenGenerate['body']
                ]);
                $token = $tokenGenerate['body'];
            }else{
                $token = 0;
            }
        }else{
            $token = $recupToken->param1;
        }



        if($token!= 0){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api-gateway.orabankga.ngenius-payments.com/transactions/outlets/d8600d10-7d5c-437c-8aec-58a1ad899709/orders");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/vnd.ni-payment.v2+json","Accept: application/vnd.ni-payment.v2+json","Authorization: Bearer ".$token));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $result = curl_exec($ch);

            // Check HTTP status code
            if (!curl_errno($ch)) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ( $http_code == 200 ||  $http_code == 201) {
                    return ['status'=>1, 'body'=>json_decode($result, true)];
                }else{
                    return ['status'=>0, 'body'=>$result, 'bad order response'=> $http_code ];
                }
            }else{
                return ['status'=>0, 'body'=>$result, 'bad order'=>'token :'.$token];
            }
            // Close handle
            curl_close($ch);
        }else{
            return ['status'=>0, 'body'=>$token, 'bad'=>'token :'.$token];
        }

    }

    public function getUssdCode($trans, $montant, $payment){
        switch ($payment){
            case 'moov' :
                return "*555*5*7*746*$montant*553$trans*";
            case 'am' :
                return "*150*3*10*746*$montant*553$trans*";
            default:
                return '';
        }
    }

    public function codeEnableCallForfait($forfait, $receveur){
        $startNyumber = $receveur[0].''.$receveur[0];
        $code = null;
        $forfaitNumber = '0';
        switch ($forfait){
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
        if($startNyumber == '06'){
            $code= "*222*3*3*$forfaitNumber#";
        }else if($startNyumber == '07'){
            $code= "*111*2*1*1*$forfaitNumber*2#";
        }
        return $code;
    }

    public function updateTransFirebase(Request $request) {
        $trans = Historiquetrans::where('id', $request->id)->first();
        if($trans){
            $resultat = $this->updateTransFirebaseIn($trans);
            return $resultat;
        }
    }


    public function updateTransFirebaseIn($trans) {
        switch ($trans->operation){
            case 'achat_wifi' :
                $data = ['infoTrans.etat' => $trans->etat, 'infoTrans.code_validation' => $trans->code_validation];
                break;
            ///case 'forfait_credit' :  case 'data_credit' :
            //if(empty($trans->param2)){
            //    $trans = $this->updateStatsForfaitIn($trans);
            //}
            // $data = ['infoTrans.etat' => $trans->etat, 'infoTrans.param3' => json_encode($trans->param3)];
            // break;
            case 'recharge_visa_uba':
                $data = ['infoTrans.etat' => $trans->etat];
                break;
            case 'rendu_monnaie_simple':case 'transfert_mobile':case 'transfert_visa':
            $data = ['infoTrans.etat' => $trans->etat, 'infoTrans.reference' => $trans->reference, 'infoTrans.tentative_autorise' => $trans->tentative_autorise];
            break;
            default :
                $data = ['infoTrans.etat' => $trans->etat, 'infoTrans.reference' => $trans->reference];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://us-central1-status-9986a.cloudfunctions.net/api/setTransInMessage?id=$trans->id&trans=". json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);

        // Check HTTP status code
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ( $http_code == 200 ||  $http_code == 201) {
                return ['status'=>1, 'body'=>json_decode($result, true), 'data'=> $data];
            }else{
                return ['status'=>0, 'body'=>$result, 'bad order response'=> $http_code ];
            }
        }else{
            return ['status'=>0, 'body'=>$result ];
        }
        // Close handle
        curl_close($ch);
    }

    public function updateStatsForfaitIn($trans){
        $pack = json_decode($trans->param1, true);
        for($i=0; $i<count($pack['data']); $i++){
            if($trans->etat == 'CONFIRMEE'){
                if($pack['data'][$i]['name'] == 'Bonus crédit'){
                    $pack['data'][$i]['status'] = 'ok';
                }else if($pack['data'][$i]['name'] == 'Appels'){
                    $pack['data'][$i]['status'] = 'load';
                }else if($pack['data'][$i]['name'] == 'Forfait' && in_array($trans->param2 , ['ok', 'OK'])){
                    $pack['data'][$i]['status'] = 'ok';
                }
            }
        }
        $trans->update([
            'param1' => json_encode($pack),
            'param3' => json_encode($pack['data'])
        ]);
        return $trans;
    }

    public function updateStatsForfait(Request $request){
        $trans = Historiquetrans::where('id', $request->id)->first();
        if($trans){
            $resultat = $this->updateStatsForfaitIn($trans);
            return $resultat;
        }
        return null;
    }

    public function getStartOperateur($operateur){
        switch ($operateur){
            case 'am' :
                return ['07', '04', '77', '76', '74'];
            case 'moov' :
                return ['06', '02', '05', '66', '65', '62'];
            default :
                return [''];
        }
    }

    public function verifandLaunchPush($customer, $trans, $payment, $object, $phonePushApp){
        try {
            $phone = $phonePushApp;
            if($phone == null){
                if($customer->payments != null){
                    $userpayment = json_decode($customer->payments, true);
                    for ($i =0; $i<count($userpayment); $i++){
                        if($userpayment[$i]['operateur'] == $payment && $userpayment[$i]['number'] != null){
                            $phone = $userpayment[$i]['number'];
                        }
                    }
                }
            }

            if($phone == null){
                $numAccount = $customer->phoneclient;
                $startOperateur = $this->getStartOperateur($payment);
                if(in_array($numAccount[0].''.$numAccount[1], $startOperateur)){
                    if(strlen($numAccount) == 9){
                        $phone = $numAccount;
                    }
                }
            }

            if($phone != null){
                 $apisFunction = new PaymentController;
                if($payment == 'moov'){
                    $push =  $apisFunction->getPaymentPvitIn($trans->montant, $phone, 'PV'.strval($trans->id));
                }else{
                    $push =  $apisFunction->amPushIn($phone, $trans->montant, $object);
                }

                if($push != null){
                    return [$push, $phone];
                }
            }

        }catch (Exception $e){}
        return null;
    }

    public function getText($operation) {
        switch ($operation) {
            case "achat_credit":
                return "GamPay Achat Crédit";
            case "forfait":
                return "GamPay Forfait GAM";
            case "rendu_monnaie_simple":
                return "GamPay Rendu monnaie";
            case "recharge_compte_gam":
                return "Recharge GamPay";
            case "recharge_visa_uba":
                return "GamPay Recharge Visa";
            case "achat_forfait":
                return "GamPay Achat forfait";
            case "gam_transfert":
                return "GamPay GAM Transfert";
            case "achat_edan":
                return "GamPay Achat Edan";
            case "esim_international":
                return "GamPay Achat e-sim";
            case "sim_international":
                return "GamPay Achat sim";
            case "forfait_international":
                return "GamPay Forfait international";
            case "transfert_mobile":
                return "GamPay Transfert Mobile";
            case "transfert_visa":
                return "GamPay Transfert via VISA";
            case "achat_status":
                return "GamPay Publication";
            case "paiement_partenaire":
                return "GamPay Paiement";
            case "transfert_ria":
                return "GamPay Transfert RIA";
            case "location_vehicule":
                return "GamPay Location véhicule";
            case "achat_wifi":
                return "GamPay Wifi zone";
            default:
                return $operation;
        }
    }
    
    public function addDetalsOperation($details, $trans) {
        try {
            if (isset($details['name'])) {
                $details = [$details];
            }
            foreach ($details as $forfait) {
                if ($forfait['name'] != 'Bonus crédit') {
                    $detailsOperation = new DetatilsOperation([
                        'operation'      => $forfait['name'],
                        'qte'            => $forfait['qte'] ?? null,
                        'name'           => $forfait['name'],
                        'status'         => 'wait',
                        'validity'       => $forfait['validity'] ?? null,
                        'transaction_id' => $trans->id,
                        'numclient'      => $trans->numclient,
                        'operateur'      => $trans->content, // Note : votre JSON a aussi une clé $forfait['operateur'] ("moov") si besoin
                    ]);
                    
                    $detailsOperation->save();
        
                    if($detailsOperation){
                        $detailsOperationCredit = DetatilsOperation::where('transaction_id',$trans->id)->where('name','achat_credit')->first();
                        if(empty($detailsOperationCredit)){
                              $detailsOperationCredit = new  DetatilsOperation([
                                'operation' => 'achat_credit',
                                'name'=> 'achat_credit',
                                'status' => 'wait',
                                'transaction_id' => $trans->id,
                                'numclient'=> $trans->numclient,
                                'operateur'=> $trans->content,
                                'qte' => $trans->param4,
                            ]);
                            $detailsOperationCredit->save();  
                        }
                    }
                }
            }
        } catch (Exception $th) {

        }

    }

    public function addDetalsOperationOld($details, $trans) {
        try {
            for ($i=0; $i<count($details); $i++) {
                $forfait = $details[$i];
                if($forfait['name'] != 'Bonus crédit'){
                    $detailsOperation = new  DetatilsOperation([
                        'operation' => $forfait['name'],
                        'qte' => $forfait['qte'],
                        'name'=> $forfait['name'],
                        'status' => 'wait',
                        'validity' =>  $forfait['validity'],
                        'transaction_id' => $trans->id,
                        'numclient'=> $trans->numclient,
                        'operateur'=> $trans->content,
                    ]);
                    $detailsOperation->save();
                    if($detailsOperation){
                        $detailsOperationCredit = DetatilsOperation::where('transaction_id',$trans->id)->where('name','achat_credit')->first();
                        if(empty($detailsOperationCredit)){
                              $detailsOperationCredit = new  DetatilsOperation([
                                'operation' => 'achat_credit',
                                'name'=> 'achat_credit',
                                'status' => 'wait',
                                'transaction_id' => $trans->id,
                                'numclient'=> $trans->numclient,
                                'operateur'=> $trans->content,
                                'qte' => $trans->param4,
                            ]);
                            $detailsOperationCredit->save();  
                        }
                    }
                }
            }
        } catch (Exception $th) {

        }

    }
    
    public function getTrans(Request $request){
        if(!empty($request->id)){
            $trans = Historiquetrans::where('reference', $request->id)->first();
        }else{
            $trans = Historiquetrans::where('phonevendeur', $request->phonevendeur)->where('numclient', $request->numclient)->where('reference', $request->reference)->where('montant', $request->montant)->first();
        }
        Http::get("https://gampay.org/gamclients/public/api/callBackAMOfflineAppRequest");
        
        return json_encode([
            'statut'=> true,
            'body'=> $trans
        ]);
    }
    
    public function createDetailsPackInApp($trans)
    {
        try {
            $forfait = ForfaitDataCredit::where('id', intval($trans->param1))->first();
            if ($forfait) {
                $details_operation = [];
                if ($forfait->qte_mega != null) {
                    array_push($details_operation, array(
                        'operation' => 'achat_forfait',
                        'qte' => $forfait->qte_mega,
                        'name' => 'achat_forfait',
                        'status' => 'wait',
                        'validity' => $forfait->validity_mega,
                        'transaction_id' => $trans->id,
                        'numclient' => $trans->numclient,
                        'operateur' => 'LIBERTIS',
                    ));
                }

                if ($forfait->qte_credit != null) {
                    array_push($details_operation, array(
                        'operation' => 'achat_credit',
                        'qte' => $forfait->qte_credit,
                        'name' => 'achat_credit',
                        'status' => 'wait',
                        'validity' => null,
                        'transaction_id' => $trans->id,
                        'numclient' => $trans->numclient,
                        'operateur' => 'LIBERTIS',
                    ));
                }
                for ($i = 0; $i < count($details_operation); $i++) {
                    $details = new DetatilsOperation($details_operation[$i]);
                    $details->save();
                }
            }
        } catch (\Exception $e) {
            // erreur
        }
    }
}
