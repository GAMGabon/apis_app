<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client2;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\WhatsAppBackUp;
use App\Models\GamElectriciteHist;
use App\Models\Historiquetrans;
use App\Models\Orders;
use App\Models\ProgramSubscription;
use Illuminate\Foundation\Exceptions\Renderer\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UsersController
{
    protected $general;
    protected $alertes;
    public function __construct(Request $request)
    {
        $this->general = new GeneralController();
        $this->alertes = new AlertesController();
    }

    public function loginCustomerLevel1(Request $request){
        try {
            $phone = $request->numCustomer;
            $server =  $request->ip();
            $num='0'.$request->numCustomer;
            $verifNumber = $this->general->getInfoNumberIn($request->numCustomer,$num,$request->numCustomer, 'register');
            $myIp = $request->ip();
            $contryIp = $this->general->getContryIp($myIp);
            // verification du pays
            if( in_array($contryIp, ['CM', 'CMR', 'BY'])){
                return response()->json([
                    'statut'=> false,
                    'action'=> 'stop',
                    'message'=> 'Service indisponible, contacter le service client'
                ]);
            }
            // recherche du compte
            $customerVeri = Customer::whereIn('phoneclient',[$request->numCustomer, $num] )->first();

            if(empty($customerVeri)){
                $phone = $request->numCustomer;
                $nom = 'Client';
                $prenom = 'GAM';
                if(in_array($request->pays, ['+241', '241'])){

                    if($phone[0] != '0'){
                        $phone = $num;
                    }
                    $verifNumber = $this->general->getInfoNumberIn($phone,$num,$request->numCustomer, '');
                    if($verifNumber[0]!=''){
                        $nom = $verifNumber[0];
                        $prenom = '';
                    }
                }
                //return $verifNumber;
                $customerVeri = new Customer([
                    'nom'=> $nom,
                    'prenom'=> $prenom,
                    'pays'=> $request->pays,
                    'phoneclient'=> $phone,
                    'mdpclient' => '1234',
                    'otp' => 'go',
                    'reset_password_token' => $request->tokenPhone,
                    'status'=>1
                ]);
                $customerVeri->save();
            }


            if($customerVeri){
                if($customerVeri->statut == 'closed'){
                    return response()->json([
                        'statut'=> false,
                        'action' => 'stop',
                        'message'=> 'Contactez le service client',
                    ]);
                }

                // add ip
                if(!empty($customerVeri->confirmation_token)){
                    if(str_contains(strval($customerVeri->confirmation_token), strval($server))){
                        $listIp = $customerVeri->confirmation_token;
                    }else{
                        $listIp = $customerVeri->confirmation_token.','. $server;
                    }
                }else{
                    $listIp = $server;
                }
                Customer::where('id',$customerVeri->id)->update([
                    'confirmation_token' => $listIp
                ]);

                if($request->methode == 'validation'){
                    if($customerVeri->otp == 'go' || $customerVeri->otp == 'open' ){
                        $action = 'go';
                        $message = "Conexion en cours";
                        $level = '2';
                    }else{
                        return response()->json([
                            'statut'=> false,
                            'action'=> 'stop',
                            'message'=> 'En attente de validation'
                        ]);
                    }
                }elseif($request->methode == 'login'){
                    // conditions connexion réussie
                    if(in_array( strval($request->goodPhone) ,[$request->numCustomer, $num])){
                        $action = 'go';
                        $message = "Connexion en cours";
                        $level = '2';
                    }elseif($customerVeri->otp == 'open' || $customerVeri->otp == 'go'){
                        $action = 'go';
                        $message = "Connexion en cours";
                        $level = $customerVeri->otp == 'go'? '2':'1';
                    }elseif(((empty($customerVeri->unlock_token) && $customerVeri->solde =='0' && empty($customerVeri->last_transaction_at)) || $customerVeri->option2 == '99999999999' || (!empty($request->tokenPhone) && $request->tokenPhone == $customerVeri->unlock_token))){
                        $action = 'go';
                        $message = "Connexion en cours";
                        $level = '2';
                    }else{
                        if(!empty($customerVeri->last_connexion_at)) {
                            $now = date('Y-m-d');
                            //$activity = date('Y-m-d', strtotime("$customerVeri->last_connexion_at +10 day"));
                            //return $activity;

                            $action = 'auth';

                        }else{
                            $action = 'go';
                            $message = "Connexion en cours";
                            $level = '2';
                        }
                        if($action == 'auth'){
                            // récuperation du numero whatsapp
                            if(!empty($customerVeri->whatsapp)){
                                $whatsAppNum = $customerVeri->whatsapp;
                            }else{
                                $whatsAppNum = $this->alertes->getWhatsAppNumber($customerVeri->phoneclient, $customerVeri->pays);
                            }
                            // verification du compte whatsapp
                            $verify = $this->alertes->checkNumberUseWhatsapp($whatsAppNum, 'whatsapp');
                            $key = 'login'.rand(1000,9999).''.time();
                            $key2 = $key.'&option=lock';
                            // Pour le service client
                            $message = "Un appareil tente d'accéder à votre compte GamPay *$customerVeri->phoneclient*";
                            //$this->sendWhatsAppGroupMessage($messageService, 'GAM Service client (4)', 'other');
                            // fin

                            if($verify == 1){ //mplate de validation de compte
                                $response = 0;
                                /* try{
                                     $curl = curl_init();
                                     curl_setopt_array($curl, array(
                                         CURLOPT_URL => 'https://graph.facebook.com/v24.0/908295725690586/messages',
                                         CURLOPT_RETURNTRANSFER => true,
                                         CURLOPT_ENCODING => '',
                                         CURLOPT_MAXREDIRS => 10,
                                         CURLOPT_TIMEOUT => 0,
                                         CURLOPT_FOLLOWLOCATION => true,
                                         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                         CURLOPT_CUSTOMREQUEST => 'POST',
                                         CURLOPT_POSTFIELDS => '{
                                         "messaging_product": "whatsapp",
                                         "to": "' . $whatsAppNum . '",
                                         "type": "template",
                                         "template": {
                                             "name": "login",
                                             "language": {
                                                 "code": "fr"
                                             },
                                             "components": [
                                                 {
                                                     "type": "header",
                                                     "parameters": [
                                                         {
                                                             "type": "image",
                                                             "image": {
                                                                 "link": "https://gampay.org/logo.png"
                                                             }
                                                         }
                                                     ]
                                                 },
                                                 {
                                                     "type": "body",
                                                     "parameters": [
                                                         {
                                                             "type": "text",
                                                             "text": "' . $message . '"
                                                         }
                                                     ]
                                                 },
                                                 {
                                                     "type": "button",
                                                     "sub_type": "URL",
                                                     "index": "0",
                                                     "parameters": [
                                                         {
                                                             "type": "text",
                                                             "text": "' . $key . '"
                                                         }
                                                     ]
                                                 },
                                                 {
                                                     "type": "button",
                                                     "sub_type": "URL",
                                                     "index": "1",
                                                     "parameters": [
                                                         {
                                                             "type": "text",
                                                             "text": "' . $key2 . '"
                                                         }
                                                     ]
                                                 }
                                             ]
                                         }
                                     }',
                                         CURLOPT_HTTPHEADER => array(
                                             'Authorization: Bearer EAAVx2scjZBYIBQn6qQ17uGPCuQOkTKr7oZAVYSTjn13FF9dBhzlUVimJWZAc4CzhZCDX2tcD8PGVv7Oz7oeZCZBer3oTDDE1UZAYsIpWqNXv8iOl91tada9Atgel4wC8Y8k3Ygxzmrl3wzrZC10ywh48QwcEvlQZB0qCcRRGPcSGmxZCOk2oN1cGQbOPxcIun9Di1dcQZDZD',
                                             'Content-Type: application/json',
                                             'Accept: application/json'
                                         ),
                                     ));
                                     $result = curl_exec($curl);
                                     //return $result;
                                     if (!curl_errno($curl)) {
                                         $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                                         if ( $http_code == 200 ||  $http_code == 201) {
                                             $response = json_decode($result, true);
                                             if ($response['messages'][0]['message_status'] == 'accepted') {
                                                 $id = $response['messages'][0]['id'];
                                                 Http::get("https://gampay.org/gamclients/public/api/createFlowBackupRequest?messageId=$id&accoundId=$$key&phone=$phone&parrain=");
                                                 $response = 1;
                                             }
                                         }
                                     }
                                     curl_close($curl);
                                 }catch (Exception $e){
                                     $response = 0;
                                 }*/

                                // Close handle
                                if($response == 1){
                                    Customer::where('id', $customerVeri->id)->update([
                                        'otp'=> $key,
                                        'reset_password_token' => $request->tokenPhone
                                    ]);
                                    $action = 'validation';
                                    $message = "Nous vous avons envoyé un message de validation sur votre compte whatsapp";
                                }else{
                                    if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed') && $customerVeri->type != 'developper'){
                                        $action = 'mdp';
                                        $message = "Entrez votre mot de passe GamPay";
                                    }else{
                                        $action = 'help';
                                        $message = "Une erreur est survenue lors de la validation de votre compte";
                                    }
                                }
                            }else{
                                if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed') && $customerVeri->type != 'developper'){
                                    $action = 'mdp';
                                    $message = "Entrez votre mot de passe GamPay";
                                }else{
                                    $action = 'help';
                                    $message = "Une erreur est survenue lors de la validation de votre compte";
                                }
                            }
                        }
                    }

                }else{
                    if($customerVeri->failed_attempts >=3 && empty($customerVeri->otp)){
                        return response()->json([
                            'statut'=> false,
                            'action'=> 'lock',
                            'message'=> 'Compte bloqué, trop de tentatives de connexion infructueuses'
                        ]);
                    }
                    // utilisation du mot de passe
                    if($customerVeri->mdpclient == $request->passCustomer){
                        $action = 'go';
                        $message = "conexion en cours";
                        $level = '2';

                    }else{
                        if(!empty($customerVeri->confirmation_token)){
                            if(str_contains(strval($customerVeri->confirmation_token), strval($server))){
                                $listIp = $customerVeri->confirmation_token;
                            }else{
                                $listIp = $customerVeri->confirmation_token.','. $server;
                            }
                        }else{
                            $listIp = $server;
                        }
                        Customer::where('id',$customerVeri->id)->update([
                            'failed_attempts' => empty($customerVeri->failed_attempts)? 1 : $customerVeri->failed_attempts + 1,
                            'confirmation_token' => $listIp
                        ]);

                        if($customerVeri->failed_attempts>2 && $customerVeri->failed_attempts<4){
                            $message = "Le compte GamPay". $customerVeri->phoneclient." à été bloqué";
                            $this->alertes->sendWhatsAppGroupMessage($message,'GAM Service client (4)', 'other');
                        }

                        return response()->json([
                            'statut'=> false,
                            'action' => 'stop',
                            'message'=> 'Mot de passe incorrecte',
                        ]);
                    }
                }

                if($action == 'go'){ //Si la connexion est réussi
                    // Verification parrainage
                    $ActivationParrainage = 0;
                    if(empty($customerVeri->expirationparrainage) && $customerVeri->id > 9948621 && !empty($customerVeri->phoneparent) && $customerVeri->phoneparent != 'GAM'){
                        $verifToken = Customer::where('unlock_token', $request->tokenPhone)->get();
                        if(count($verifToken)>0){
                            $messageNotif = 'Échec de l\'activation du parrainage sur le '.$request->numCustomer. '';
                        }else{
                            $ActivationParrainage = 1;
                            $messageNotif = 'Réussite de l\'activation du parrainage sur le '.$request->numCustomer. '';
                        }
                        $this->alertes->sendNotificationInprogram($customerVeri->phoneparent, $customerVeri->phoneparent,'info', '',$messageNotif,null, '' );
                    }
                    if($ActivationParrainage == 1){
                        $myParrain =  Customer::where('phoneclient', $customerVeri->phoneparent)->first();
                        if($myParrain){
                            Customer::where('id', $myParrain->id)->update([
                                'Fonction2'=> $myParrain->Fonction2 + 1,
                                //'challenge'=> $pointChallenge
                            ]);
                        }
                    }
                    //Fin

                    // token pour Google
                    if($customerVeri->username == 'test_gam_12%'){
                        $myToken = $request->tokenPhoneNull;
                    }else{
                        $myToken = $request->tokenPhone;
                    }
                    // Fin

                    // New status
                    if(!empty($customerVeri->unlock_token) && $customerVeri->unlock_token != $request->tokenPhone ){
                        $newStatus = 1;
                    }else{
                        $newStatus = $customerVeri->status;
                    }
                    // Fin

                    // Liste Ip de connexion au compte
                    if(!empty($customer->confirmation_token)){
                        if(str_contains(strval($customer->confirmation_token), strval($server))){
                            $listIp = $customer->confirmation_token;
                        }else{
                            $listIp = $customer->confirmation_token.','. $server;
                        }
                    }else{
                        $listIp = $server;
                    }
                    //Fin

                    // mise à jour du compte
                    $now = date("Y-m-d H:i:s");
                    if($request->tokenPhone != 'service'){
                        Customer::find($customerVeri->id)->update([
                            'unlock_token'=> $request->tokenPhone ,
                            'param8'=> $request->devicePhone,
                            'otp'=> $request->devicePhoneNULL,
                            'pays' => $request->pays,
                            'failed_attempts' => 0,
                            'flp_account' => 'gam_conversationnel',
                            'status' => $newStatus,
                            'confirmation_token' => $listIp,
                            'last_connexion_at' => ($request->app == null || $request->app == 'lien' || $request->tokenPhone =='lien' )?$customerVeri->last_connexion_at: strval($now),
                            'code_confirmed_at' =>  ($customerVeri->code_confirmed_at==null ||  $customerVeri->code_confirmed_at=='code_confirmed_at' ||  $customerVeri->code_confirmed_at=='CODE_CONFIRMED_AT')? strval($now) : $customerVeri->code_confirmed_at,
                            'flp_created_at' => strval($now),
                            'avatar'=> $request->app == null? $customerVeri->avatar:$request->app,
                            'expirationparrainage'=>  $ActivationParrainage == 0? $customerVeri->expirationparrainage : date('Y-m-d', strtotime('+30 days')),
                            'versionApp' => $request->versionApp == null? $customerVeri->versionApp:$request->versionApp,
                        ]);
                    }
                    //Fin

                    // pour les clients réveillés
                    $client2 = Client2::where('numclient',$customerVeri->phoneclient)->where('grade', 'like', '%rc#%')->first();
                    if(!empty($client2)){
                        $backup = WhatsAppBackUp::where('messageId', $client2->grade)->first();
                        if(!empty($backup)){
                            WhatsAppBackUp::where('messageId', $client2->grade)->update([
                                'app' => 'ok'
                            ]);
                        }
                    }
                    // Fin

                    $myAccount = Customer::where('id', $customerVeri->id)->first();
                    $nombre = $customerVeri->filleuls;
                    $marchands = $request->marchandsNull;
                    if($customerVeri->flp_account != 'gam_conversationnel'){
                        if(!empty($customerVeri->username) || $customerVeri->type == 'project_emotion'){
                            $marchands = Customer::where('type', 'default_marchand')->orderBy('option3', 'asc')->get();
                        }else{
                            $marchands = Customer::where('type', 'default_marchand')->whereNotIn('code_confirm',['rm','emotion'])->orderBy('option3', 'asc')->get();
                        }
                    }else{
                        if($customerVeri->type == 'project_emotion' && !str_contains($customerVeri->gam_card_ceated_at, "emotion")){
                            $marchands = Customer::where('type', 'default_marchand')->where('nom', 'EMOTION')->get();
                            $customerVeri->update(['gam_card_ceated_at' => strval($customerVeri->gam_card_ceated_at) . 'emotion']);
                        }
                    }


                    return response()->json([
                        'statut'=> true,
                        'token' => $customerVeri->id,
                        'customer'=> $myAccount,
                        'marchands'=> $marchands,
                        'filleul'=> $nombre== null? 0:$nombre,
                        'team'=> empty($customerVeri->option1)? 'gam' : $customerVeri->option1,
                        'compteurs'=>0,
                        'message'=> $message,
                        'am'=> '*150*3*10*746*',
                        'moov'=> '*555*5*7*746*',
                        'level' => $level
                    ]);
                }else{
                    return response()->json([
                        'statut'=> false,
                        'action'=> $action,
                        'message'=> $message
                    ]);
                }
            }else{
                return response()->json([
                    'statut'=> false,
                    'action'=> 'create',
                    'message'=> "Le $request->numCustomer ne possède pas de compte GamPay"
                ]);
            }

        }catch (Exception $e){
            return response()->json([
                'statut'=> false,
                'action'=> 'stop',
                'message'=> 'Une erreur s\'est produite lors du traitement de vos données'
            ]);
        }
    }
    public function newRegisterCustomer(Request $request){
        $server =  $request->ip();
        $myIp = $request->ip();
        $contryIp = $this->general->getContryIp($myIp);
        // verification du pays
        if( in_array($contryIp, ['CM', 'CMR', 'BY'])){
            return response()->json([
                'statut'=> false,
                'action'=> 'stop',
                'message'=> 'Service indisponible, contacter le service client'
            ]);
        }
        $num='0'.$request->numCustomer;
        $customerVeri = Customer::whereIn('phoneclient',[$request->numCustomer, $num])->get();

        if (count($customerVeri)>0){
            return response()->json([
                'statut'=> false,
                'action' => 'stop',
                'message'=>'Ce numéro est déjà utiliser',
            ]);
        }else{

            $phone = $request->numCustomer;
            $nom = 'Client';
            $prenom = 'GAM';
            if(in_array($request->pays, ['+241', '241'])){

                if($phone[0] != '0'){
                    $phone = $num;
                }
                $verifNumber = $this->general->getInfoNumberIn($phone,$num,$request->numCustomer, '');
                if($verifNumber[0]!=''){
                    $nom = $verifNumber[0];
                    $prenom = '';
                }
            }
            //return $verifNumber;
            $customer = new Customer([
                'nom'=> $nom,
                'prenom'=> $prenom,
                'pays'=> $request->pays,
                'phoneclient'=> $phone,
                'mdpclient' => '1234',
                'otp' => 'go',
                'reset_password_token' => $request->tokenPhone,
                'status'=>1
            ]);
            $customer->save();
            $defaultsMarchands = Customer::where('type','default_marchand')->get();
            /*$action = 'validation';
            $message = "Votre compte est cours de validation";*/
            $action = 'config';
            $message = "Votre compte est cours de Configuration";
            return response()->json([
                'statut'=> true,
                'action'=> $action,
                'message'=>$message,
                'defaultsMarchands' => $defaultsMarchands
            ]);
        }
    }




    /******************* Users status ********************/
    public function getMarchandsActus(Request $request){
        //$marchandsActus = Customer::whereNotNull('actu_current')->get();
        $marchandsActus = Customer::whereIn('type', ['marchand'])->get();
        $marchandsSubscribes = $request->subscribe;
        if(!empty($marchandsSubscribes)){
            $marchandsSubscribes = Customer::whereIn('id', $request->subscribe)->get();
        }
        return response()->json([
            'statut'=> true,
            'current_actu'=> $marchandsActus,
            'subscribes'=>$marchandsSubscribes,
        ]);
    }


    public function getUserData(Request $request){
        $user = Customer::where('id', $request->id)->first();
        $payments = Payment::where('status', 1)->get();
        if(count($payments) > 0){
            if($user->payments != null){
                $userPayment = json_decode($user->payments);
                if(count($userPayment) != count($payments)){
                    foreach ($payments as $payment) {
                        $in = false;
                        for ($i=0; $i<count($userPayment); $i++){
                            if($userPayment[$i]['operateur'] == $payment->code){
                                $in = true;
                            }
                        }
                        if($in == false){
                            array_push($userPayment, ['name'=> null,'number'=> null,'validity'=> null,'operateur'=> $payment->code,'code'=> null,'start'=> $payment->param1,'use'=> 0]);
                        }
                    }
                }
            }else{
                $userPayment = [];
                foreach ($payments as $payment) {
                    array_push($userPayment, ['name'=> null,'number'=> null,'validity'=> null,'operateur'=> $payment->code,'code'=> null,'start'=> $payment->param1,'use'=> 0]);
                }
            }
            $user->update(['payments' => json_encode($userPayment),'versionApp' => $request->versionApp == null? $user->versionApp:$request->versionApp]);
        }else{
            $payments = null;
        }
        return response()->json([
            'statut'=> true,
            'body'=> $user,
            'payments' => $payments
        ]);
    }


    public function getEntreprisesLocalInterantional(Request $request){

        $gabonOnly = filter_var($request->gabonOnly, FILTER_VALIDATE_BOOLEAN);

        $query = Customer::whereIn('type', ['marchand', 'default_marchand']);

        if ($gabonOnly) {
            $query->where(function($q) {
                $q->where('pays', 'LIKE', '%241%');
            });
        } else {
            $query->where(function($q) {
                $q->where('pays', 'NOT LIKE', '%241%');
            });
        }

        $entreprises = $query->get();

        return response()->json([
            "success" => true,
            "gabonOnly" => $gabonOnly,
            "count" => $entreprises->count(),
            "data" => $entreprises
        ]);
    }


    public function editAccount(Request $request){
        try {
            Customer::where('id', intval($request->id))->update([
                'nom' => $request->nom,
                'prenom'=> $request->prenom,
                'email'=> $request->email,
                'profession' => $request->profession,
                'avatar' => $request->avatar,
                'sexe' => $request->genre,
                'whatsapp'=> $request->whatsapp,
                'mdpclient'=> $request->pass,
                'naissance'=> $request->naiss,
                'pseudo'=> $request->pseudo,
            ]);
            $client = Customer::where('id',intval($request->id))->first();
            return response()->json([
                'statut'=> true,
                'customer'=> $client,
                'message'=> 'Modification éffectuée'
            ]);
        }catch (Exception $e){
            return response()->json([
                'statut'=> false,
                'message'=> 'Une erreur s\'est produite'
            ]);
        }
    }

    public function updateUser(Request $request){
        try {
            Customer::where('id', intval($request->id))->update($request->data);
            $client = Customer::where('id',intval($request->id))->first();
            return response()->json([
                'statut'=> true,
                'user'=> $client
            ]);
        }catch (\Exception $e) {
            return response()->json([
                'statut'=> false,
                'message'=> 'Une erreur s\'est produite'
            ]);
        }
    }

    public function getUsers(Request $request){
        Http::get('https://gampay.org/gamclients/public/api/recupIdCustomer');
        if(!empty($request->search)){
            $search = $request->search;
            $hisrory = Customer::where(function($q) use( $search) {
                $q->where('phoneclient', 'like', '%'.$search.'%')->orWhere('nom', 'like', '%'.$search.'%')->orWhere('prenom', 'like', '%'.$search.'%')->orWhere('pseudo', 'like', '%'.$search.'%')->orWhere('whatsapp', 'like', '%'.$search.'%')->orWhere('created_at', 'like', '%'.$search.'%');
            })->where('phoneparent', $request->parrain)->orderBy('created_at', 'desc')->take(20)->get();
        }else if($request->type == 'filleuls'){
            $users = Customer::where('phoneparent', $request->parrain)->whereNotNull('expirationparrainage')->orderBy('created_at', 'desc')->get();
        }else if($request->type == 'default_marchand_actif'){
            $users = Customer::where('type', 'default_marchand')->where('etat', 'CONFIRME')->where('credit', '>', 0)->orderBy('credit', 'desc')->get();
        } else if($request->type == 'default_marchand'){
            $users = Customer::where('type', 'default_marchand')->orderBy('credit', 'desc')->get();
        }else if(!empty($request->id)){
            $users = Customer::where('id',$request->id)->get();
        } else if($request->type == 'marchand'){
            $users = Customer::where('type', 'marchand')->orderBy('credit', 'desc')->get();
        }else if(!empty($request->user_app)){
            $sansZero = substr($request->user_app, 1);
            $users = Customer::whereIn('phoneclient',[$request->user_app, '0'.$request->user_app, $sansZero])->where('flp_account', 'gam_conversationnel')->whereNotNull('unlock_token')->get();
        }else{
            $users = Customer::whereIn('phoneclient', $request->users)->get();
        }
        if(count($users)>0){
            return response()->json([
                'statut'=> true,
                'body'=> $users
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'message'=> 'Users not fund'
            ]);
        }
    }

    public function editLastMessageCustomer(request $request){
        $customer = Customer::where('id', $request->id)->first();
        if($customer){
            if($request->type == 'open'){
                Customer::where('id', $customer->id)->update([
                    'no_read' => 0
                ]);
            }else{
                Customer::where('id', $customer->id)->update([
                    'last_message' => $request->message,
                    'last_message_time' => date("Y-m-d H:i:s"),
                    'no_read' => empty($request->type )? $customer->no_read +1 : 0
                ]);

                $name = $customer->nom;
                $message = $request->message;
                $agents = DB::select("SELECT * from customer where username like '%gamSC%'");
                foreach ($agents as $agents){
                    Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$agents->unlock_token&app=android&message=$message&titre=$name");
                }
            }

        }
        return $customer;
    }

    public function getNumByWhatsApp($whatsapp){
        $phoneSansIndicatif = substr($whatsapp, 3);
        $debuNum = $phoneSansIndicatif[0] . '' . $phoneSansIndicatif[1];
        if (strlen($phoneSansIndicatif) == 8) {
            if (in_array($debuNum, ['74', '04'])) {
                $phone = '074' . substr($phoneSansIndicatif, 2);
            } elseif (in_array($debuNum, ['77', '07'])) {
                $phone = '077' . substr($phoneSansIndicatif, 2);
            } elseif ($debuNum == '76') {
                $phone = '076' . substr($phoneSansIndicatif, 2);
            } elseif (in_array($debuNum, ['66', '06'])) {
                $phone = '066' . substr($phoneSansIndicatif, 2);
            } elseif (in_array($debuNum, ['62', '02'])) {
                $phone = '062' . substr($phoneSansIndicatif, 2);
            } elseif (in_array($debuNum, ['65', '05'])) {
                $phone = '065' . substr($phoneSansIndicatif, 2);
            } elseif ($debuNum == '60') {
                $phone = '060' . substr($phoneSansIndicatif, 2);
            } else {
                $phone = $phoneSansIndicatif;
            }
        } else {
            $phone = $phoneSansIndicatif;
        }
        return $phone;
    }

    public function parrainagePackInApp(Request $request){
        $msg = "";
        $status = 'error';
        $nameText = '';
        if($request->name != 'inconnu' || !empty($request->name)){
            $nameText = "de $request->name";
        }

        $messageId = 'com_image_flow' . time();
        $attente = date("Y-m-d H:i:s",  strtotime('+30 day'));
        $phone = $this->getNumByWhatsApp($request->phone);
        $customer = Customer::where('phoneclient', $phone)->first();
        if ($customer) {
            if (!empty($customer->last_transaction_at)) {
                if ($customer->last_transaction_at <  date("Y-m-d H:i:s",  strtotime('-30 day'))) {
                    // send message Le numéro 07071340 est déjà actif sur notre produit
                    $msg = "Le numéro $request->phone $nameText est déjà actif sur nos produits";
                }
            }

            if (!empty($customer->phoneparent)) {
                // send message Le numéro 07071340 a déjà été parrainé
                $msg = "Le numéro $request->phone $nameText a déjà été parrainé";
                if($customer->phoneparent == $request->parrain){
                    $status = 'already_exist';
                }
            }

            if (empty($msg)) {
                Customer::where('id', $customer->id)->update([
                    'phoneparent' => $request->parrain,
                    'expirationparrainage' => $attente,
                ]);
                $status = 'enable';
                $msg = "Parrainage effectué avec succès sur le $request->phone $nameText";
            }
        } else {
            $customer =  new Customer([
                'nom' => 'GAM',
                'prenom' => 'client',
                'phoneclient' => $phone,
                'mdpclient' => '1234',
                'pseudo' => $request->name,
                'phoneparent' => $request->parrain,
                'expirationparrainage' => $attente,
                'otp' => 'open',
                'status' => 1
            ]);
            $customer->save();
            $status = 'enable';
            $msg = "Parrainage effectué avec succès sur le $request->phone $nameText";
        }
        if($status == 'enable'){
            /*$mess = "Vous avez été parrainé par le $parrain";
            $mess1 = "Gagnez du temps avec les packs *GAM* et recevez, en une seule opération, votre crédit et votre forfait Libertis. Testez l’offre dès maintenant.";*/
            $mess = "Un de vos proches, le $request->parrain de $request->parrain_name,  vous recommande GAM et vous invite à découvrir les produits de GAM.";
            $this->sendTemplateFilleul($request->phone,$mess,$phone,$messageId);
        }

        return response()->json([
            'status' => $status,
            'message' => $msg,
        ]);
    }

    public function sendTemplateFilleul($whatsapp,$mess,$phone,$messageId){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://graph.facebook.com/v24.0/908295725690586/messages',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
              "messaging_product": "whatsapp",
              "to": "' . $whatsapp . '",
              "type": "template",
              "template": {
                  "name": "com_gam",
                  "language": {
                      "code": "fr"
                  },
                  "components": [
                      {
                          "type": "header",
                          "parameters": [
                              {
                                  "type": "image",
                                  "image": {
                                      "link": "https://gampay.org/image_flow/communication_pocket_wifi_for_ussd.jpg"
                                  }
                              }
                          ]
                      },
                      {
                          "type": "body",
                          "parameters": [
                              {
                                  "type": "text",
                                  "text": "'.$mess.'"
                              }
                          ]
                      },
                      {
                          "type": "button",
                          "sub_type": "flow",
                          "index": "0",
                          "parameters": [
                            {
                                "type": "action",
                                "action": {
                                    "flow_token": "' . $phone . '",
                                    "flow_action_data": {
                                        "<CUSTOM_KEY>": "<CUSTOM_VALUE>"
                                    }
                                }
                            }
                        ]
                      }
                  ]
              }
            }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer EAAVx2scjZBYIBQn6qQ17uGPCuQOkTKr7oZAVYSTjn13FF9dBhzlUVimJWZAc4CzhZCDX2tcD8PGVv7Oz7oeZCZBer3oTDDE1UZAYsIpWqNXv8iOl91tada9Atgel4wC8Y8k3Ygxzmrl3wzrZC10ywh48QwcEvlQZB0qCcRRGPcSGmxZCOk2oN1cGQbOPxcIun9Di1dcQZDZD',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $result = curl_exec($curl);
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if ($response['messages'][0]['message_status'] == 'accepted') {
                    $id = $response['messages'][0]['id'];
                    Http::get("https://gampay.org/gamclients/public/api/createFlowBackupRequest?accoundId=$messageId&messageId=$id&phone=$phone");
                    return 1;
                } else {
                    return [0, $response];
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

    public function registerConfigSims(Request $request){
        $customer = Customer::where('id', $request->id)->first();
        if($customer){
            $this->resetConfig($request->data);
            $customer->update(['config_sims' => json_encode($request->data)]);
            return response()->json([
                'statut'=> true,
                'body'=> $customer
            ]);
        }
        return response()->json([
            'statut'=> false,
            'message'=> 'Users not fund'
        ]);
    }

    public function resetConfig($data){
        try {
            for ($i = 0; $i < count($data); $i++) {
                $customers = Customer::where('config_sims', 'like', "%{$data['$i']['number']}%")->get();
                if (count($customers) > 0) {
                    foreach ($customers as $customer) {
                        Customer::where('id', $customer->id)->update(['config_sims' => null]);
                    }
                }
            }
        } catch (\Exception $th) {
            //throw $th;
        }
    }


    public function getDataUssd(Request $request){
        $customer = Client2::where('gampay', $request->id)->first();
        if($customer){
            $rang = DB::SELECT("SELECT rang
                    FROM (
                        SELECT
                            num,
                            RANK() OVER (
                                ORDER BY CAST(challenge AS INTEGER) DESC
                            ) AS rang
                        FROM client2
                        WHERE deleted_at IS NULL
                          AND classement IS NOT NULL
                    ) t
                    WHERE num = $customer->num");
            return response()->json([
                 'rang' => $rang,
                'statut'=> true,
                'body'=> $customer
            ]);
        }
        return response()->json([
            'statut'=> false,
            'message'=> 'Pas de données dans client 2'
        ]);
    }

    public function linkGamPayUssd(Request $request){
        try {
            $customer = Customer::whereIn('phoneclient', [$request->phone, '0'.$request->phone])->first();
            if(empty($customer)){
                $nom = 'Client';
                $prenom = 'GAM';
                $phone = $request->phone;
                if($phone[0] != '0'){
                    $phone = '0'.$phone;
                }
                $verifNumber = $this->general->getInfoNumberIn($phone,$request->phone,$request->phone, '');
                if($verifNumber[0]!=''){
                    $nom = $verifNumber[0];
                    $prenom = '';
                }
                $customer = new Customer([
                    'nom'=> $nom,
                    'prenom'=> $prenom,
                    'pays'=> '241',
                    'phoneclient'=> $phone,
                    'mdpclient' => '1234',
                    'otp' => 'go',
                    'status'=>1
                ]);
                $customer->save();
            }

            if(intval($customer->solde) < 500 && $customer->username == null){
                $customer->update([
                    'otp' => 'go',
                    'state' => $request->zone,
                ]);
            }
            Client2::where('num', $request->id)->update([
                'gampay' => $customer->id,
                'zone' => $request->zone
            ]);

            return response()->json([
                'statut' => true,
                'account' => $customer->phoneclient,
                'message' => 'Connectez-vous à votre compte GamPay et contactez notre service client pour toute assistance'
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'statut' => false,
                'message' => 'une erreur s\'est produite '. $th->getMessage()
            ]);
        }

    }


}
