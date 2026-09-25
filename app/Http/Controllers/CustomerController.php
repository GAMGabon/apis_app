<?php

namespace App\Http\Controllers;
use App\Models\CarteAnnuaire;
use App\Models\Cadeau;
use App\Models\Compteur;
use App\Models\Contact;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerCommande;
use App\Models\CustomerComplement;
use App\Models\Device;
use App\Models\DeviceInternal;
use App\Models\ForfaitInternational;
use App\Models\GamElectriciteHist;
use App\Models\GamRechargeHist;
use App\Models\Historiquetrans;
use App\Models\ErrorApp;
use App\Models\Kyc;
use App\Models\Lot;
use App\Models\Menu;
use App\Models\MenuCustomer;
use App\Models\Notif;
use App\Models\Orders;
use App\Models\Payment;
use App\Models\PaymentCustomer;
use App\Models\ProgramSubscription;
use App\Models\Reclammation;
use App\Models\SimInternal;
use App\Models\Solde;
use App\Models\Client2;
use App\Models\Carte;
use App\Models\Status;
use App\Models\Partage;
use App\Models\WhatsAppVerify;
use App\Models\WhatsAppBackUp;
use App\Models\Vue;
use App\Models\Epreuve;
use App\Models\Examen;
use App\Models\UserExamen;
use App\Models\Participation;
use App\Models\Question;
use App\Models\Pronostic;
use App\Models\ProgressionCustomer;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;
use App\Models\Comment;
use Illuminate\Support\Facades\Redirect;
use Exception;


class CustomerController extends Controller
{

    public function getNumByWhatsApp($whatsapp){
        $phoneSansIndicatif = substr($whatsapp, 3);
        $debuNum = $phoneSansIndicatif[0].''.$phoneSansIndicatif[1];
        if(strlen($phoneSansIndicatif) == 8){
            if(in_array($debuNum , ['74', '04'])){
                $phone = '074'. substr($phoneSansIndicatif, 2);
            }elseif (in_array($debuNum , ['77', '07'])){
                $phone = '077'. substr($phoneSansIndicatif, 2);
            }elseif ($debuNum == '76'){
                $phone = '076'. substr($phoneSansIndicatif, 2);
            }elseif (in_array($debuNum , ['66', '06'])){
                $phone = '066'. substr($phoneSansIndicatif, 2);
            }elseif (in_array($debuNum , ['62', '02'])){
                $phone = '062'. substr($phoneSansIndicatif, 2);
            }elseif (in_array($debuNum , ['65', '05'])){
                $phone = '065'. substr($phoneSansIndicatif, 2);
            }elseif ($debuNum =='60'){
                $phone = '060'. substr($phoneSansIndicatif, 2);
            }else{
                $phone = $phoneSansIndicatif;
            }
        }else{
            $phone = $phoneSansIndicatif;
        }
        return $phone;
    }

    public function registerCustomer(Request $request){
        $num='0'.$request->numCustomer;
        $verifNumber = $this->getInfoNumberIn($request->numCustomer,$num, $request->numCustomer, 'register');
        $myIp = $request->ip();
        $contryIp = $this->getContryIp($myIp);
        if(in_array($contryIp, ['CM', 'CMR', 'BY'])){
            return response()->json([
                'statut'=> false,
                'code' => 0,
                'phone' => "lock",
                'message'=> 'Service indisponible, contacter le service client'
            ]);
        }

        $customerVeri = Customer::whereIn('phoneclient',[$request->numCustomer, $num] )->get();
        $phoneCaract = strval($request->numCustomer);
        $contryCode = $request->DataNull;
        $zone = $request->DataNull;
        if($request->teamCustomer != 'cmr'){
            if(strlen($phoneCaract)==8 && $phoneCaract[0] != '0' && (strval($request->paysCustomer) == '241' || strval($request->paysCustomer) == '+241')){
                $phone = '0'.$request->numCustomer;
                $paysClient = "241";
                $contryCode = 'GA';
                $zone = 'Gabon';
            }else{
                $phone = $request->numCustomer;
                $paysClient = $request->paysCustomer;
            }

        }else{
            $phone = $request->numCustomer;
            $paysClient = "237";
            $contryCode = 'CM';
            $zone = 'Cameroon';
        }

        if (count($customerVeri)>0){
            if(!empty($request->priority)){
                foreach($customerVeri as $customer){
                    Customer::where('id', $customer['id'])->update(['priority'=>$request->priority]);
                }
            }
            return response()->json([
                'statut'=> false,
                'message'=>'Ce numéro est déjà utiliser',
            ]);
        }else{

            if(!empty($request->parrain)){
                $parrain =  Customer::where('id',intval($request->parrain))->first();
                $team = $parrain->code_confirm;
                $phoneParrain = $parrain->phoneclient;
                $phoneOldParrain =$parrain->phoneparent;

                Customer::where('id', $parrain->id)->update([
                    'filleuls'=> $parrain->filleuls + 1
                ]);

                if(!empty($parrain->phoneparent)){
                    $parrainOld = Customer::where('phoneclient', $parrain->phoneparent)->first();
                    if($parrainOld){
                        Customer::where('id', $parrainOld->id)->update([
                            //'petit_fils'=> $parrainOld->petit_fils +1
                        ]);
                    }
                }

            }else{
                $team = $request->teamCustomer;
                $phoneParrain = $request->teamCustomerNull;
                $phoneOldParrain =$request->teamCustomerNull;
            }

            $customer = new Customer([
                'nom'=> 'client',
                'prenom'=> 'GAM',
                'pseudo'=> $request->pseudoCustomer,
                'pays'=> $request->paysCustomer,
                'phoneclient'=> $phone,
                'whatsapp'=> $request->numWhatsappCustomer,
                'mdpclient'=> $request->passCustomer,
                'phoneparent'=> $phoneParrain,
                'phonegrandparent'=> $phoneOldParrain,
                'option1'=> $team,
                'code_confirm'=> $team,
                'pays'=>$paysClient,
                'avatar'=> $request->app,
                'status'=>1,
                'priority' => $request->priority,
                'contry_code'=>$contryCode,
                'zone'=> $zone
            ]);
            $customer->save();

            $teamClient = new ProgramSubscription([
                'phoneclient'=>$phone,
                'param2'=> $team
            ]);
            $teamClient ->save();

            return response()->json([
                'statut'=> true,
                'message'=>'Inscription réussie',
                'customer'=>$customer
            ]);
        }
    }

    public function loginCustomer(Request $request){
        $access = '';
        $server = $request->ip();
        $num='0'.$request->numCustomer;
        $verifNumber = $this->getInfoNumberIn($request->numCustomer,$num,$request->numCustomer, 'login');
        $listeNoire = ['077641174', '066202122', '074405969', '077641174', '066666666', '066303132', '066323334', '066333435', '077251198', '076346949'];
        $myIp = $request->ip();
        $contryIp = $this->getContryIp($myIp);
        if(in_array($request->numCustomer, $listeNoire) || in_array($num, $listeNoire ) || in_array($contryIp, ['CM', 'CMR', 'BY'])){
            return response()->json([
                'statut'=> false,
                'code' => 0,
                'phone' => "lock",
                'message'=> 'Service indisponible, contacter le service client'
            ]);
        }

        if($request->passCustomer == 'rm_what'){
            $customerVeri = Customer::where('id',$request->numCustomer)->get();
        }elseif($request->passCustomer == 'visa'){
            $customerVeri = [];
            $carte = Carte::whereIn('id_client', [$request->numCustomer,  '00'. $request->numCustomer, substr($request->numCustomer, 2)])->first();
            //return $carte;
            if($carte){
                $customerVeri = Customer::where('id', intval($carte->id_customer))->get();
            }
        }elseif(!empty($request->otp)){
            $customerVeri = Customer::whereIn('phoneclient',[$request->numCustomer, $num] )->whereIn('otp',['go', 'open'])->get();
        }elseif($request->tokenPhone == 'service'){
            $customerVeri = Customer::whereIn('phoneclient',[$request->numCustomer, $num] )->get();
        }else{
            if(strlen($request->passCustomer)>4){
                return response()->json([
                    'statut'=> false,
                    'code' => 0,
                    'phone' => "lock",
                    'message'=> 'Contacter le service client'
                ]);
            }
            $customerVeri = Customer::whereIn('phoneclient',[$request->numCustomer, $num] )->get();
        }


        if (count($customerVeri)>0){
            foreach ($customerVeri as $customer){

                if($customer['statut'] == 'closed'){
                    return response()->json([
                        'statut'=> false,
                        'action' => 'stop',
                        'message'=> 'Contactez le service client',
                    ]);
                }

                if(!empty($customer['confirmation_token'])){
                    if(str_contains(strval($customer['confirmation_token']), strval($server))){
                        $listIp = $customer['confirmation_token'];
                    }else{
                        $listIp = $customer['confirmation_token'].','. $server;
                    }
                }else{
                    $listIp = $server;
                }

                // add ip
                Customer::where('id',$customer['id'])->update([
                    'confirmation_token' => $listIp
                ]);

                if ($customer['status'] == 555) {
                    return response()->json([
                        'statut' => false,
                        'code' => 0,
                        'message' => 'Compte en attente de validation',
                    ]);
                }

                if($customer['failed_attempts']>=3 && empty($request->otp)){
                    return response()->json([
                        'statut'=> false,
                        'code' => 0,
                        'phone' => "lock",
                        'message'=> 'Compte bloqué, trop de tentatives de connexion infructueuses'
                    ]);
                }

                if($request->passCustomer != 'rm_what' &&  $request->passCustomer != 'visa' && empty($request->otp) && $request->tokenPhone != 'service'){
                    if( $customer['otp'] =='open' || $customer['otp'] =='go' || $customer['option2'] == '99999999999' || (empty($customer['unlock_token']) && $customer['solde'] =='0') || (!empty($request->tokenPhone) && $request->tokenPhone == $customer['unlock_token'])){
                        $access = 'ok';
                    }else{
                        if($customer['mdpclient'] == $request->passCustomer){
                            $access = 'ok';
                        }else{
                            $access = 'ko';
                            $mess = '';

                            if(!empty($customer['confirmation_token'])){
                                if(str_contains(strval($customer['confirmation_token']), strval($server))){
                                    $listIp = $customer['confirmation_token'];
                                }else{
                                    $listIp = $customer['confirmation_token'].','. $server;
                                }
                            }else{
                                $listIp = $server;
                            }
                            Customer::where('id',$customer['id'])->update([
                                'failed_attempts' => empty($customer['failed_attempts'])? 1 : $customer['failed_attempts'] + 1,
                                'confirmation_token' => $listIp
                            ]);

                            if($customer['failed_attempts']>2 && $customer['failed_attempts']<4){
                                $mess = "lock";
                                $message = "Le compte GamPay". $customer['phoneclient']." à été bloqué";
                                Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=" . $message);
                                //$this->sendWhatsAppGroupMessage($message,'GAM Service client (4)', 'other');
                            }

                            return response()->json([
                                'statut'=> false,
                                'code' => 0,
                                'phone' => $mess,
                                'message'=> 'Mot de passe incorrecte',
                            ]);

                        }
                    }
                }

                //if(!empty($customer['unlock_token']) && !empty($request->tokenPhone) && $customer['username'] != 'partenaire'){
                if($customer['otp'] != 'go' && !empty($customer['unlock_token']) && !empty($request->tokenPhone) && $customer['username'] != 'test_gam_12%' && !empty($customer['last_transaction_at'])){
                    if($customer['unlock_token'] != $request->tokenPhone && ($customer['username'] == 'partenaire' || intval($customer['solde'])> 1000 )){
                        if(!empty($customer['whatsapp']) && $customer['otp'] != 'device'){

                            $id_customer = $customer['id'];
                            $phone_customer = $customer['phoneclient'];
                            $messageService = "Tentative de connexion avec un nouvel appareil sur le compte *$phone_customer*";
                            /* $messageValidation = "Nouvelle connexion à votre compte *GamPay*, permettez à cet appareil d'accéder à votre compte via ce lien

 https://gampay.org/gamclients/public/account?agent=gam1ghghTTU262199bisbust&key=$id_customer&type=device&validation=x23KHZDGHZ2472692GBD276VS6923C6329CX";
                             $this->sendWhatsAppMessage($customer['whatsapp'],$messageValidation, 'other');
                             Customer::where('id', $id_customer)->update([
                                 'otp' => 'device'
                             ]);*/
                            //$this->sendWhatsAppGroupMessage($messageService, 'GAM Service client (4)', 'other');
                            Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=" . $messageService);
                        }

                        return response()->json([
                            'statut'=> false,
                            'code' => 0,
                            'message'=> 'Connexion non autorisée'
                        ]);
                    }
                }

                $ActivationParrainage = 0;
                if(empty($customer['expirationparrainage']) && $customer['id'] > 9948621 && !empty($customer['phoneparent']) && $customer['phoneparent'] != 'GAM'){
                    $verifToken = Customer::where('unlock_token', $request->tokenPhone)->get();
                    if(count($verifToken)>0){
                        $messageNotif = 'Échec de l\'activation du parrainage sur le '.$request->numCustomer. '';
                    }else{
                        $ActivationParrainage = 1;
                        $messageNotif = 'Réussite de l\'activation du parrainage sur le '.$request->numCustomer. '';
                    }
                    $this->sendNotificationInprogram($customer['phoneparent'], $customer['phoneparent'],'info', '',$messageNotif,null, '' );
                }

                if($customer['username'] == 'test_gam_12%'){
                    $myToken = $request->tokenPhoneNull;
                }else{
                    $myToken = $request->tokenPhone;
                }

                $now = date("Y-m-d H:i:s");
                if($request->tokenPhone != 'service'){
                    Http::get("https://gampay.org/gamclients/public/api/alerteNewCustomer?phoneclient=".$customer['phoneclient']);
                    Customer::find($customer['id'])->update([
                        'unlock_token'=> strlen(strval($request->tokenPhone))<15? $customer['unlock_token']: $myToken,
                        'param8'=> $request->devicePhone,
                        'otp'=> $request->devicePhoneNULL,
                        'pays' =>  $request->pays,
                        'failed_attempts' => 0,
                        'confirmation_token' => $listIp,
                        'last_connexion_at' => ($request->app == null || $request->app == 'lien' || $request->tokenPhone =='lien' )?$customer['last_connexion_at']: strval($now),
                        'code_confirmed_at' =>  ($customer['code_confirmed_at']==null ||  $customer['code_confirmed_at']=='code_confirmed_at' ||  $customer['code_confirmed_at']=='CODE_CONFIRMED_AT')? strval($now) : $customer['code_confirmed_at'],
                        'flp_created_at' => strval($now),
                        'avatar'=> $request->app == null? $customer['avatar']:$request->app,
                        'expirationparrainage'=>  $ActivationParrainage == 0? $customer['expirationparrainage'] : date('Y-m-d', strtotime('+30 days'))
                    ]);
                }

                if($request->deviceInfo == 'GAB'){
                    $nowDay = date("Y-m-d");
                    $hisroryWait = Historiquetrans::where('phonevendeur', $customer['phoneclient'])->whereIn('etat',['attend', 'en_agence_attend'])->where('created_at','like', ''.strval($nowDay).'%')->get();
                    $historyPayment = count($hisroryWait);
                }else{
                    $historyPayment = 0;
                }

                $client2 = Client2::where('numclient',$customer['phoneclient'])->where('grade', 'like', '%rc#%')->first();
                if(!empty($client2)){
                    $backup = WhatsAppBackUp::where('messageId', $client2->grade)->first();
                    if(!empty($backup)){
                        WhatsAppBackUp::where('messageId', $client2->grade)->update([
                            'app' => 'ok'
                        ]);
                    }
                }


                /*if(str_contains( $customer['gam_card_ceated_at'], '2024-') && !empty($customer['phoneparent']) && empty($customer['unlock_token']) && $customer['phoneparent'] != 'GAM' ){
                    $myParrain =  Customer::where('phoneclient', $customer['phoneparent'])->where('statut', 'challenger')->first();
                    if($myParrain){
                        $VerifChallendEnd = date("Y-m-d H:i:s");
                        $d = mktime(23, 59, 59, 7, 30, 2024);
                        $EndChallenge = date("Y-m-d H:i:s", $d);
                        if($VerifChallendEnd > $EndChallenge){
                            $pointChallenge = $myParrain->challenge;
                        }else{
                            $pointChallenge = $this->calculPointChallenge($myParrain->Fonction4, empty($myParrain->Fonction2)? 1 : $myParrain->Fonction2 + 1, $myParrain->Fonction6, $myParrain->Fonction8);
                        }
                        Customer::where('id', $myParrain->id)->update([
                            'Fonction2'=> $myParrain->Fonction2 + 1,
                            //'challenge'=> $pointChallenge
                        ]);
                    }
                }*/

                $nombre = $customer['filleuls'];
                return response()->json([
                    'statut'=> true,
                    'token' => $customer['id'],
                    'customer'=> $customerVeri,
                    'filleul'=> $nombre== null? 0:$nombre,
                    'team'=> empty($customer['option1'])? 'gam' : $customer['option1'] ,
                    'compteurs'=>0,
                    'paymentWait'=> $historyPayment,
                    'localisation' => $this->datasApp(),
                    'am'=> '*150*3*10*746*',
                    'moov'=> '*555*5*7*746*'
                ]);
            }
        }else{

            return response()->json([
                'statut'=> false,
                'code' => 0,
                'phone' => '',
                'message'=> 'Identifiant incorrecte',
                'body'=> [$customerVeri,$request->passCustomer, $request->numCustomer ]
            ]);
        }
    }

    public function newloginCustomer(Request $request){
        $server =  $request->ip();
        $num='0'.$request->numCustomer;
        $verifNumber = $this->getInfoNumberIn($request->numCustomer,$num,$request->numCustomer, 'register');
        $myIp = $request->ip();
        $contryIp = $this->getContryIp($myIp);
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
                //if(($customerVeri->otp == 'go' && $customerVeri->reset_password_token == $request->tokenPhone)){
                if($customerVeri->otp == 'go' || $customerVeri->otp == 'open'){
                    $action = 'go';
                    $message = "Conexion en cours";
                }else{
                    return response()->json([
                        'statut'=> false,
                        'action'=> 'stop',
                        'message'=> 'En attente de validation'
                    ]);
                }
            }elseif($request->methode == 'login'){

                // conditions connexion réussie
                if($customerVeri->otp == 'open' || $customerVeri->otp == 'go'){
                    $action = 'go';
                    $message = "Connexion en cours";
                }elseif( $customerVeri->status != 555 && ((empty($customerVeri->unlock_token) && $customerVeri->solde =='0' && empty($customerVeri->last_transaction_at)) || $customerVeri->option2 == '99999999999' || $customerVeri->otp == 'open' || (!empty($request->tokenPhone) && $request->tokenPhone == $customerVeri->unlock_token))){
                    $action = 'go';
                    $message = "Connexion en cours";
                }else{
                    // récuperation du numero whatsapp
                    if(!empty($customerVeri->whatsapp)){
                        $whatsAppNum = $customerVeri->whatsapp;
                    }else{
                        $whatsAppNum = $this->getWhatsAppNumber($customerVeri->phoneclient, $customerVeri->pays);
                    }
                    // verification du compte whatsapp
                    $verify = $this->checkNumberUseWhatsapp($whatsAppNum, 'whatsapp');
                    $key = 'login'.rand(1000,9999).''.time();
                    $key2 = $key.'&option=lock';
                    // Pour le service client
                    $messageService = "Tentative de connexion avec un nouvel appareil sur le compte *$customerVeri->phoneclient*";
                    //$this->sendWhatsAppGroupMessage($messageService, 'GAM Service client (4)', 'other');
                    // fin

                    if($verify == 1) { //mplate de validation de compte
                        $response = 0;
                        $curl = curl_init();
                        curl_setopt_array($curl, array(
                            CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'POST',
                            CURLOPT_POSTFIELDS => '{
                       "messages": [
                        {
                          "from": "24102535748",
                          "to": "'.$whatsAppNum.'",
                          "messageId": "'.$key.'",
                          "content": {
                            "templateName": "login",
                            "templateData": {
                              "body": {
                                "placeholders": []
                              },
                             "header": {
                                "type": "IMAGE",
                                "mediaUrl": "https://gampay.org/app.PNG"
                              },
                              "buttons": [
                                {"type": "URL", "parameter": "'.$key.'"},
                                {"type": "URL", "parameter": "'.$key2.'"},
                                {"type": "QUICK_REPLY", "parameter": "assister"}
                              ]
                            },
                            "language": "fr"
                          },
                          "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                        }
                      ]
                    }',
                            CURLOPT_HTTPHEADER => array(
                                'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
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
                                if($response['messages'][0]['status']['groupName'] == 'PENDING'){
                                    $response = 1;
                                }
                            }
                        }
                        // Close handle
                        curl_close($curl);
                        if($response == 1){
                            Customer::where('id', $customerVeri->id)->update([
                                'otp'=> $key,
                                'reset_password_token' => $request->tokenPhone
                            ]);
                            $action = 'validation';
                            $message = "Nous vous avons envoyé un message de validation sur votre compte whatsapp";
                        }else{
                            //if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed') && $customerVeri->type != 'developper'){
                            if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed')){
                                $action = 'mdp';
                                $message = "Entrez votre mot de passe GamPay";
                            }else{
                                $action = 'help';
                                $message = "Une erreur est survenue lors de la validation de votre compte";
                            }
                        }
                    }else{
                        //if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed') && $customerVeri->type != 'developper'){
                        if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed')){
                            $action = 'mdp';
                            $message = "Entrez votre mot de passe GamPay";
                        }else{
                            $action = 'help';
                            $message = "Une erreur est survenue lors de la validation de votre compte";
                        }
                    }

                    /*if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed') && $customerVeri->type != 'developper'){
                        $action = 'mdp';
                        $message = "Entrez votre mot de passe GamPay";
                    }else{
                        $action = 'help';
                        $message = "Une erreur est survenue lors de la validation de votre compte";
                    }*/
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
                        $this->sendWhatsAppGroupMessage($message,'GAM Service client (4)', 'other');
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
                    $this->sendNotificationInprogram($customerVeri->phoneparent, $customerVeri->phoneparent,'info', '',$messageNotif,null, '' );
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
                        'unlock_token'=> strlen(strval($request->tokenPhone))<15? $customerVeri->unlock_token: $myToken,
                        'param8'=> $request->devicePhone,
                        'otp'=> $request->devicePhoneNULL,
                        'pays' =>  $request->pays,
                        'failed_attempts' => 0,
                        'status' => $newStatus,
                        'confirmation_token' => $listIp,
                        'last_connexion_at' => ($request->app == null || $request->app == 'lien' || $request->tokenPhone =='lien' )?$customerVeri->last_connexion_at: strval($now),
                        'code_confirmed_at' =>  ($customerVeri->code_confirmed_at==null ||  $customerVeri->code_confirmed_at=='code_confirmed_at' ||  $customerVeri->code_confirmed_at=='CODE_CONFIRMED_AT')? strval($now) : $customerVeri->code_confirmed_at,
                        'flp_created_at' => strval($now),
                        'avatar'=> $request->app == null? $customerVeri->avatar:$request->app,
                        'expirationparrainage'=>  $ActivationParrainage == 0? $customerVeri->expirationparrainage : date('Y-m-d', strtotime('+30 days'))
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

                // Ajout des points installation aux challenger
                /*if(str_contains( $customerVeri->gam_card_ceated_at, '2024-') && !empty($customerVeri->phoneparent) && empty($customerVeri->unlock_token) ){
                    $myParrain =  Customer::where('phoneclient', $customerVeri->phoneparent)->where('statut', 'challenger')->first();
                    if($myParrain){
                        $VerifChallendEnd = date("Y-m-d H:i:s");
                        $d = mktime(23, 59, 59, 7, 30, 2024);
                        $EndChallenge = date("Y-m-d H:i:s", $d);
                        if($VerifChallendEnd > $EndChallenge){
                            $pointChallenge = $myParrain->challenge;
                        }else{
                            $pointChallenge = $this->calculPointChallenge($myParrain->Fonction4, empty($myParrain->Fonction2)? 1 : $myParrain->Fonction2 + 1, $myParrain->Fonction6, $myParrain->Fonction8);
                        }
                        Customer::where('id', $myParrain->id)->update([
                            'Fonction2'=> $myParrain->Fonction2 + 1,
                            //'challenge'=> $pointChallenge
                        ]);
                    }
                }*/
                // Fin
                $myAccount = Customer::where('id', $customerVeri->id)->get();
                $nombre = $customerVeri->filleuls;
                return response()->json([
                    'statut'=> true,
                    'token' => $customerVeri->id,
                    'customer'=> $myAccount,
                    'filleul'=> $nombre== null? 0:$nombre,
                    'team'=> empty($customerVeri->option1)? 'gam' : $customerVeri->option1,
                    'compteurs'=>0,
                    'message'=> $message,
                    'localisation' => $this->datasApp(),
                    'am'=> '*150*3*10*746*',
                    'moov'=> '*555*5*7*746*'
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
                'message'=> $request->version == "new"? "Le $request->numCustomer ne possède pas de compte GamPay": "Le $request->numCustomer ne possède pas de compte GamPay, Appuyez sur continuer pour créer un compte",
            ]);
        }
    }

    public function newRegisterCustomer(Request $request){
        $server =  $request->ip();
        $myIp = $request->ip();
        $contryIp = $this->getContryIp($myIp);
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
                $verifNumber = $this->getInfoNumberIn($phone,$num,$request->numCustomer, '');
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

            /*$teamClient = new ProgramSubscription([
                'phoneclient'=>$phone,
                'param2'=> 'gam'
            ]);
            $teamClient ->save();*/

            // $whatsAppNum = $this->getWhatsAppNumber($phone, $request->pays);
            // // verification du compte whatsapp
            // $verify = $this->checkNumberUseWhatsapp($whatsAppNum, 'whatsapp');
            // $key = 'register'.rand(1000,9999).''.time();
            // if($verify == 1) { //mplate de validation de compte
            //     $response = 0;
            //     $curl = curl_init();
            //     curl_setopt_array($curl, array(
            //         CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
            //         CURLOPT_RETURNTRANSFER => true,
            //         CURLOPT_ENCODING => '',
            //         CURLOPT_MAXREDIRS => 10,
            //         CURLOPT_TIMEOUT => 0,
            //         CURLOPT_FOLLOWLOCATION => true,
            //         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //         CURLOPT_CUSTOMREQUEST => 'POST',
            //         CURLOPT_POSTFIELDS => '{
            //           "messages": [
            //             {
            //               "from": "24102535748",
            //               "to": "'.$whatsAppNum.'",
            //               "messageId": "'.$key.'",
            //               "content": {
            //                 "templateName": "register",
            //                 "templateData": {
            //                   "body": {
            //                     "placeholders": []
            //                   },
            //                  "header": {
            //                     "type": "IMAGE",
            //                     "mediaUrl": "https://gampay.org/app.PNG"
            //                   },
            //                   "buttons": [
            //                     {"type": "URL", "parameter": "'.$key.'"},
            //                     {"type": "QUICK_REPLY", "parameter": "assister"}
            //                   ]
            //                 },
            //                 "language": "fr"
            //               },
            //               "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
            //             }
            //           ]
            //         }',
            //         CURLOPT_HTTPHEADER => array(
            //             'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
            //             'Content-Type: application/json',
            //             'Accept: application/json'
            //         ),
            //     ));

            //     $result = curl_exec($curl);
            //     //return $result;
            //     if (!curl_errno($curl)) {
            //         $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            //         if ( $http_code == 200 ||  $http_code == 201) {
            //             $response = json_decode($result, true);
            //             if($response['messages'][0]['status']['groupName'] == 'PENDING'){
            //                 $response = 1;
            //             }
            //         }
            //     }
            //     // Close handle
            //     curl_close($curl);
            //     if($response == 1){
            //         Customer::where('id', $customer->id)->update([
            //             'otp'=> $key,
            //             'reset_password_token' => $request->tokenPhone
            //         ]);
            //         $action = 'validation';
            //         $message = "Nous vous avons envoyé un message de validation sur votre compte whatsapp";
            //     }else {
            //         $action = 'help';
            //         $message = "Une erreur est survenue lors de la validation de votre compte";
            //     }
            // }else{
            //     $action = 'help';
            //     $message = "Une erreur est survenue lors de la validation de votre compte";
            // }

            $action = 'validation';
            $message = "Votre compte est cours de validation";
            if(!empty($request->new)){
                $action = 'config';
                $message = "Votre compte est cours de Configuration";
            }

            return response()->json([
                'statut'=> true,
                'action'=> $action,
                'message'=>$message
            ]);
        }
    }

    public function loginCustomerLevel1(Request $request){
        $server =  $request->ip();
        $num='0'.$request->numCustomer;
        $verifNumber = $this->getInfoNumberIn($request->numCustomer,$num,$request->numCustomer, 'register');
        $myIp = $request->ip();
        $contryIp = $this->getContryIp($myIp);
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
                        $activity = date('Y-m-d', strtotime("$customerVeri->last_connexion_at +10 day"));
                        //return $activity;
                        if ($now >= $activity) {
                            $action = 'go';
                            $message = "Connexion en cours";
                            $level = '1';
                        }else{
                            $action = 'auth';
                        }
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
                            $whatsAppNum = $this->getWhatsAppNumber($customerVeri->phoneclient, $customerVeri->pays);
                        }
                        // verification du compte whatsapp
                        $verify = $this->checkNumberUseWhatsapp($whatsAppNum, 'whatsapp');
                        $key = 'login'.rand(1000,9999).''.time();
                        $key2 = $key.'&option=lock';
                        // Pour le service client
                        $messageService = "Tentative de connexion avec un nouvel appareil sur le compte *$customerVeri->phoneclient*";
                        //$this->sendWhatsAppGroupMessage($messageService, 'GAM Service client (4)', 'other');
                        // fin

                        if($verify == 1){ //mplate de validation de compte
                            $response = 0;
                            $curl = curl_init();
                            curl_setopt_array($curl, array(
                                CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_POSTFIELDS => '{
                                   "messages": [
                                    {
                                      "from": "24102535748",
                                      "to": "'.$whatsAppNum.'",
                                      "messageId": "'.$key.'",
                                      "content": {
                                        "templateName": "login",
                                        "templateData": {
                                          "body": {
                                            "placeholders": []
                                          },
                                         "header": {
                                            "type": "IMAGE",
                                            "mediaUrl": "https://gampay.org/app.PNG"
                                          },
                                          "buttons": [
                                            {"type": "URL", "parameter": "'.$key.'"},
                                            {"type": "URL", "parameter": "'.$key2.'"},
                                            {"type": "QUICK_REPLY", "parameter": "assister"}
                                          ]
                                        },
                                        "language": "fr"
                                      },
                                      "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                                    }
                                  ]
                                }',
                                CURLOPT_HTTPHEADER => array(
                                    'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
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
                                    if($response['messages'][0]['status']['groupName'] == 'PENDING'){
                                        $response = 1;
                                    }
                                }
                            }
                            // Close handle
                            curl_close($curl);
                            if($response == 1){
                                Customer::where('id', $customerVeri->id)->update([
                                    'otp'=> $key,
                                    'reset_password_token' => $request->tokenPhone
                                ]);
                                $action = 'validation';
                                $message = "Nous vous avons envoyé un message de validation sur votre compte whatsapp";
                            }else{
                                //if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed') && $customerVeri->type != 'developper'){
                                if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed')){
                                    $action = 'mdp';
                                    $message = "Entrez votre mot de passe GamPay";
                                }else{
                                    $action = 'help';
                                    $message = "Une erreur est survenue lors de la validation de votre compte";
                                }
                            }
                        }else{
                            //if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed') && $customerVeri->type != 'developper'){
                            if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed')){
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
                        $this->sendWhatsAppGroupMessage($message,'GAM Service client (4)', 'other');
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
                    $this->sendNotificationInprogram($customerVeri->phoneparent, $customerVeri->phoneparent,'info', '',$messageNotif,null, '' );
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
                        'unlock_token'=> (strlen(strval($request->tokenPhone))<15 || $level =='1')? $customerVeri->unlock_token: $myToken,
                        'param8'=> $request->devicePhone,
                        'otp'=> $request->devicePhoneNULL,
                        'pays' =>  $request->pays,
                        'failed_attempts' => 0,
                        'status' => $newStatus,
                        'confirmation_token' => $listIp,
                        'last_connexion_at' => ($request->app == null || $request->app == 'lien' || $request->tokenPhone =='lien' )?$customerVeri->last_connexion_at: strval($now),
                        'code_confirmed_at' =>  ($customerVeri->code_confirmed_at==null ||  $customerVeri->code_confirmed_at=='code_confirmed_at' ||  $customerVeri->code_confirmed_at=='CODE_CONFIRMED_AT')? strval($now) : $customerVeri->code_confirmed_at,
                        'flp_created_at' => strval($now),
                        'avatar'=> $request->app == null? $customerVeri->avatar:$request->app,
                        'expirationparrainage'=>  $ActivationParrainage == 0? $customerVeri->expirationparrainage : date('Y-m-d', strtotime('+30 days')),
                        //'flp_account' => ($customerVeri->flp_account != 'gam_conversationnel' || $customerVeri->flp_account == null) ? 'gam_conversationnel' : $customerVeri->flp_account
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

                // Ajout des points installation aux challenger
                /*if(str_contains( $customerVeri->gam_card_ceated_at, '2024-') && !empty($customerVeri->phoneparent) && empty($customerVeri->unlock_token) ){
                    $myParrain =  Customer::where('phoneclient', $customerVeri->phoneparent)->where('statut', 'challenger')->first();
                    if($myParrain){
                        $VerifChallendEnd = date("Y-m-d H:i:s");
                        $d = mktime(23, 59, 59, 7, 30, 2024);
                        $EndChallenge = date("Y-m-d H:i:s", $d);
                        if($VerifChallendEnd > $EndChallenge){
                            $pointChallenge = $myParrain->challenge;
                        }else{
                            $pointChallenge = $this->calculPointChallenge($myParrain->Fonction4, empty($myParrain->Fonction2)? 1 : $myParrain->Fonction2 + 1, $myParrain->Fonction6, $myParrain->Fonction8);
                        }
                        Customer::where('id', $myParrain->id)->update([
                            'Fonction2'=> $myParrain->Fonction2 + 1,
                            //'challenge'=> $pointChallenge
                        ]);
                    }
                }*/
                // Fin
                $myAccount = Customer::where('id', $customerVeri->id)->get();
                $nombre = $customerVeri->filleuls;

                $customerCommande = CustomerCommande::where('customer', $customerVeri->id)->first();
                return response()->json([
                    'statut'=> true,
                    'token' => $customerVeri->id,
                    'customer'=> $myAccount,
                    'filleul'=> $nombre== null? 0:$nombre,
                    'team'=> empty($customerVeri->option1)? 'gam' : $customerVeri->option1,
                    'compteurs'=>0,
                    'commande'=> $customerCommande,
                    'message'=> $message,
                    'localisation' => $this->datasApp(),
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
    }

    public function loginCustomerLevel2(Request $request){
        $server =  $request->ip();
        $num='0'.$request->numCustomer;
        $verifNumber = $this->getInfoNumberIn($request->numCustomer,$num,$request->numCustomer, 'register');
        $myIp = $request->ip();

        // recherche du compte
        $customerVeri = Customer::whereIn('phoneclient',[$request->numCustomer, $num] )->first();
        if($customerVeri){
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
                //if($customerVeri->otp == 'go' && $customerVeri->reset_password_token == $request->tokenPhone){
                if($customerVeri->otp == 'go'){
                    $action = 'go';
                    $message = "Conexion en cours";
                }else{
                    return response()->json([
                        'statut'=> false,
                        'action'=> 'stop',
                        'message'=> 'En attente de validation'
                    ]);
                }
            }elseif($request->methode == 'login'){

                // récuperation du numero whatsapp
                if(!empty($customerVeri->whatsapp)){
                    $whatsAppNum = $customerVeri->whatsapp;
                }else{
                    $whatsAppNum = $this->getWhatsAppNumber($customerVeri->phoneclient, $customerVeri->pays);
                }
                // verification du compte whatsapp
                $verify = $this->checkNumberUseWhatsapp($whatsAppNum, 'whatsapp');
                $key = 'login'.rand(1000,9999).''.time();
                $key2 = $key.'&option=lock';
                // Pour le service client
                $messageService = "Tentative de connexion avec un nouvel appareil sur le compte *$customerVeri->phoneclient*";
                //$this->sendWhatsAppGroupMessage($messageService, 'GAM Service client (4)', 'other');
                // fin

                if($verify == 1){ //mplate de validation de compte
                    $response = 0;
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => '{
                           "messages": [
                            {
                              "from": "24102535748",
                              "to": "'.$whatsAppNum.'",
                              "messageId": "'.$key.'",
                              "content": {
                                "templateName": "login",
                                "templateData": {
                                  "body": {
                                    "placeholders": []
                                  },
                                 "header": {
                                    "type": "IMAGE",
                                    "mediaUrl": "https://gampay.org/app.PNG"
                                  },
                                  "buttons": [
                                    {"type": "URL", "parameter": "'.$key.'"},
                                    {"type": "URL", "parameter": "'.$key2.'"},
                                    {"type": "QUICK_REPLY", "parameter": "assister"}
                                  ]
                                },
                                "language": "fr"
                              },
                              "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                            }
                          ]
                        }',
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
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
                            if($response['messages'][0]['status']['groupName'] == 'PENDING'){
                                $response = 1;
                            }
                        }
                    }
                    // Close handle
                    curl_close($curl);
                    if($response == 1){
                        Customer::where('id', $customerVeri->id)->update([
                            'otp'=> $key,
                            'reset_password_token' => $request->tokenPhone
                        ]);
                        $action = 'validation';
                        $message = "Nous vous avons envoyé un message de validation sur votre compte whatsapp";
                    }else{
                        //if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed') && $customerVeri->type != 'developper'){
                        if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed')){
                            $action = 'mdp';
                            $message = "Entrez votre mot de passe GamPay";
                        }else{
                            $action = 'help';
                            $message = "Une erreur est survenue lors de la validation de votre compte";
                        }
                    }
                }else{
                    //if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed') && $customerVeri->type != 'developper'){
                    if(($customerVeri->status == 1111 || $customerVeri->status != 555 || $customerVeri->statut !='closed')){
                        $action = 'mdp';
                        $message = "Entrez votre mot de passe GamPay";
                    }else{
                        $action = 'help';
                        $message = "Une erreur est survenue lors de la validation de votre compte";
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
                        $this->sendWhatsAppGroupMessage($message,'GAM Service client (4)', 'other');
                    }

                    return response()->json([
                        'statut'=> false,
                        'action' => 'error',
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
                    $this->sendNotificationInprogram($customerVeri->phoneparent, $customerVeri->phoneparent,'info', '',$messageNotif,null, '' );
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
                        'unlock_token'=> (strlen(strval($request->tokenPhone))<15)? $customerVeri->unlock_token: $myToken,
                        'param8'=> $request->devicePhone,
                        'otp'=> $request->devicePhoneNULL,
                        'pays' =>  $request->pays,
                        'failed_attempts' => 0,
                        'status' => $newStatus,
                        'confirmation_token' => $listIp,
                        'last_connexion_at' => ($request->app == null || $request->app == 'lien' || $request->tokenPhone =='lien' )?$customerVeri->last_connexion_at: strval($now),
                        'code_confirmed_at' =>  ($customerVeri->code_confirmed_at==null ||  $customerVeri->code_confirmed_at=='code_confirmed_at' ||  $customerVeri->code_confirmed_at=='CODE_CONFIRMED_AT')? strval($now) : $customerVeri->code_confirmed_at,
                        'flp_created_at' => strval($now),
                        'avatar'=> $request->app == null? $customerVeri->avatar:$request->app,
                        'expirationparrainage'=>  $ActivationParrainage == 0? $customerVeri->expirationparrainage : date('Y-m-d', strtotime('+30 days'))
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

                // Ajout des points installation aux challenger
                /*if(str_contains( $customerVeri->gam_card_ceated_at, '2024-') && !empty($customerVeri->phoneparent) && empty($customerVeri->unlock_token) ){
                    $myParrain =  Customer::where('phoneclient', $customerVeri->phoneparent)->where('statut', 'challenger')->first();
                    if($myParrain){
                        $VerifChallendEnd = date("Y-m-d H:i:s");
                        $d = mktime(23, 59, 59, 7, 30, 2024);
                        $EndChallenge = date("Y-m-d H:i:s", $d);
                        if($VerifChallendEnd > $EndChallenge){
                            $pointChallenge = $myParrain->challenge;
                        }else{
                            $pointChallenge = $this->calculPointChallenge($myParrain->Fonction4, empty($myParrain->Fonction2)? 1 : $myParrain->Fonction2 + 1, $myParrain->Fonction6, $myParrain->Fonction8);
                        }
                        Customer::where('id', $myParrain->id)->update([
                            'Fonction2'=> $myParrain->Fonction2 + 1,
                            //'challenge'=> $pointChallenge
                        ]);
                    }
                }*/
                // Fin
                $myAccount = Customer::where('id', $customerVeri->id)->get();
                $nombre = $customerVeri->filleuls;
                $customerCommande = CustomerCommande::where('customer',$customerVeri->id)->first();

                return response()->json([
                    'statut'=> true,
                    'token' => $customerVeri->id,
                    'customer'=> $myAccount,
                    'filleul'=> $nombre== null? 0:$nombre,
                    'team'=> empty($customerVeri->option1)? 'gam' : $customerVeri->option1,
                    'compteurs'=>0,
                    'commande'=> $customerCommande,
                    'message'=> $message,
                    'localisation' => $this->datasApp(),
                    'am'=> '*150*3*10*746*',
                    'moov'=> '*555*5*7*746*',

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
    }

    public function getCodeLogin(Request $request){

        $whatsappService = "24102535748";
        $num='0'.$request->numCustomer;
        $listeNoire = ['077641174', '066202122', '074405969', '077641174', '066666666', '066303132', '066323334', '066333435', '077251198', '076346949'];
        $myIp = $request->ip();
        $server = $request->ip();

        $contryIp = $this->getContryIp($myIp);

        if(in_array($request->numCustomer, $listeNoire ) || in_array($num, $listeNoire ) || in_array($contryIp, ['CM', 'CMR', 'BY'])){
            return response()->json([
                'statut'=> false,
                'message'=> 'Service indisponible, contacter le service client'
            ]);
        }
        $customerVeri = Customer::whereIn('phoneclient',[$request->numCustomer, $num] )->first();

        if($customerVeri){
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



            if((empty($customerVeri->unlock_token) && $customerVeri->solde ='0') || (!empty($request->tokenPhone) && $request->tokenPhone == $customerVeri->unlock_token) || $customerVeri->otp == 'open' || $customerVeri->otp == 'go' ){
                Customer::where('id', $customerVeri->id)->update([
                    'otp'=> 'go'
                ]);
                $action = 'go';
                $message = 'Connexion en cours';
            }else{
                if(!empty($customerVeri->whatsapp)){
                    $whatsAppNum = $customerVeri->whatsapp;
                }else{
                    $whatsAppNum = $this->getWhatsAppNumber($customerVeri->phoneclient, $customerVeri->pays);
                }

                $verify = $this->checkNumberUseWhatsapp($whatsAppNum, 'whatsapp');
                $key = $customerVeri->id;
                $key2 = $key.'&option=lock';
                Customer::where('id', $customerVeri->id)->update([
                    'otp'=> $key
                ]);
                $messageId = 'login'.time();
                if($verify == 1){
                    $response = 0;
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => '{
                       "messages": [
                        {
                          "from": "24102535748",
                          "to": "'.$whatsAppNum.'",
                          "messageId": "'.$messageId.'",
                          "content": {
                            "templateName": "login",
                            "templateData": {
                              "body": {
                                "placeholders": []
                              },
                             "header": {
                                "type": "IMAGE",
                                "mediaUrl": "https://gampay.org/app.PNG"
                              },
                              "buttons": [
                                {"type": "URL", "parameter": "'.$key.'"},
                                {"type": "URL", "parameter": "'.$key2.'"},
                                {"type": "QUICK_REPLY", "parameter": "assister"}
                              ]
                            },
                            "language": "fr"
                          },
                          "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                        }
                      ]
                    }',
                        CURLOPT_HTTPHEADER => array(
                            'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                            'Content-Type: application/json',
                            'Accept: application/json'
                        ),
                    ));
                    $result = curl_exec($curl);
                    if (!curl_errno($curl)) {
                        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                        if ( $http_code == 200 ||  $http_code == 201) {
                            $response = json_decode($result, true);
                            if($response['messages'][0]['status']['groupName'] == 'PENDING'){
                                $response = 1;
                            }
                        }
                    }
                    // Close handle
                    curl_close($curl);
                    if($response == 1){
                        Customer::where('id', $customerVeri->id)->update([
                            'otp'=> $key
                        ]);
                        $action = 'scyd';
                        $message = 'Validez la connexion à votre compte sur votre WhatsApp';
                    }else{
                        $action = 'password';
                        $message = "Utilisez votre mot de passe";
                    }
                }else{
                    $action = 'password';
                    $message = "Utilisez votre mot de passe";
                }
            }

            return response()->json([
                'statut'=> true,
                'code' => 0,
                'phone' => "",
                'message'=> $message,
                'action' => $action,
                'num'=>$whatsappService
            ]);

        }else{
            return response()->json([
                'statut'=> false,
                'code' => 0,
                'phone' => "",
                'message'=> "Pas de compte correspondant au $request->numCustomer",
                'action' => 'stop',
                'num'=>$whatsappService

            ]);
        }
    }

    public function valideConnexion(Request $request){
        if(!empty($request->key)){
            $reponse = 'ok';
            $customer = Customer::where('otp', $request->key)->first();
            if($customer){
                if(!empty($request->option) && $request->option == 'lock'){
                    Customer::where('id', $customer->id)->update(['otp'=> $request->optionNull ]);
                    $this->sendNotificationInprogram($customer->phoneclient,$customer->phoneclient,'info', '',"Connexion refusée",null, "$request->key");
                }else{
                    if($customer->status == 1111){
                        Customer::where('id', $customer->id)->update(['otp'=> 'go']);
                    }else{
                        Customer::where('id', $customer->id)->update(['otp'=> 'go','status'=>1]);
                    }
                    $this->sendNotificationInprogram($customer->phoneclient,$customer->phoneclient,'info', '',"Connexion valide",null, "$request->key");
                }
            }else{
                $reponse = 'ko';
            }
        }else{
            $customer = '0';
            $reponse = 'ko';
        }
        return view('challenge.connexion', compact('reponse', 'customer'));
    }

    public function valideConnexionApp(Request $request){
        $num='0'.$request->numCustomer;
        $customer = Customer::whereIn('phoneclient',[$request->numCustomer, $num])->where('otp','go')->first();
        if($customer){
            return response()->json([
                'statut'=> true,
            ]);
        }else{
            return response()->json([
                'statut'=> false,
            ]);
        }
    }

    public function codeConfirmAuth(Request $request){
        $num='0'.$request->numCustomer;
        $customerVeri = Customer::whereIn('phoneclient',[$request->numCustomer, $num] )->where('option3',intval($request->code))->get();
        if(count($customerVeri)==0){
            return response()->json([
                'statut'=> false,
                'code' => 0,
                'message'=> 'Ce code ne correspond à aucun compte',
            ]);
        }else{
            foreach ($customerVeri as $customer){
                $programs = ProgramSubscription::where('phoneclient', $request->numCustomer)->get();

                $myDevice= new Device([// on enregistre le phone collecté
                    'marque' => $request->devicePhone,
                    'id_device' => $request->deviceId,
                    'id_customer' => $customer['id'],
                    'phoneclient' => $customer['phoneclient'],
                    'status' => 1
                ]);
                $myDevice->save();

                if (count($programs )>0) {

                    foreach ($programs as $program){
                        ProgramSubscription::find($program['id'])->update([
                            'program_name'=> $request->program,
                        ]);
                    }

                    if($program['param2'] == null || empty($program['param2'])){
                        $team = 'gampay';
                    }else{
                        $team = $program['param2'];
                    }
                }else{

                    if($customer['nom'] == null && $customer['prenom'] == null && $customer['pseudo']== null ){
                        $teamClient = new ProgramSubscription([
                            'phoneclient'=> $request->numCustomer,
                            'program_name'=> $request->program,
                            'param2'=> 'winners'
                        ]);
                    }else{
                        $teamClient = new ProgramSubscription([
                            'phoneclient'=> $request->numCustomer,
                            'program_name'=> $request->program
                        ]);
                    }

                    $team ='winners';

                    $teamClient->save();
                }
                //$filleul = Customer::where('phoneparent', $request->numCustomer)->where('status', 1)->get();
                $compt = Compteur::where('num_client', $customer['phoneclient'])->get();
                $devices = Device::where('id_customer', $customer['id'])->get();// on recupère les appareils
                $forfaitInternational = ForfaitInternational::all();
                $deviceInternationalRequest = DeviceInternal::all();
                $sim = SimInternal::where('customerId',$customer['id'])->orWhere('CustomerNum', $customer['phoneclient'])->get();

                if(count($compt)>0){
                    $compteur = $compt;
                }else{
                    $compteur = 0;
                }

                $now = date("Y-m-d H:i:s");
                Customer::find($customer['id'])->update([
                    'unlock_token'=> strlen(strval($request->tokenPhone))<15? $customer['unlock_token']: $request->tokenPhone,
                    'param8'=> $request->devicePhone,
                    'pays' =>  $request->pays,
                    'has_flp_account' => $customer['statut'] == 'free'? 'true':'false',
                    'last_connexion_at' =>  strval($now),
                    'code_confirmed_at' =>  $customer['code_confirmed_at']==null? strval($now) : $customer['code_confirmed_at'],
                    'avatar'=> $request->app == null? $customer['avatar']:$request->app
                ]);

                $nombre = $customer['filleuls'];
                return response()->json([
                    'statut'=> true,
                    'token' => $customer['id'],
                    'customer'=> $customerVeri,
                    'filleul'=> $nombre== null? 0:$nombre,
                    'team'=> $team,
                    'compteurs'=>$compteur,
                    'devices'=>$devices,
                    'devicesInternationaux'=>$deviceInternationalRequest,
                    'forfaitsInternationaux'=> $forfaitInternational,
                    'sim'=> $sim,
                    'localisation' => $this->datasApp(),
                    'am'=> '*150*3*10*746*',
                    'moov'=> '*555*5*7*746*'
                ]);
            }
        }
    }

    public function regiterCustomerWebform(Request $request){
        $uri = $request->path();
        $team = $request->team;
        $parrain = $request->parrain;
        $org = $request->org;
        if($parrain == 'mbaya'){
            return view('clientMbaya', compact('team','parrain', 'uri','org'));
        }elseif ($parrain == 'auto'){
            return view('clientAuto', compact('team','parrain', 'uri','org'));
        }

        $customer = Customer::where('phoneclient', $parrain)->first();

        return Redirect::to('http://app.gampay.org/?p='.$customer->id.'&v=vue');

        $value = Cookie::get('view');
        if(empty($value)){
            $id = md5(microtime().rand());
            Cookie::queue('view', $id, 176680000);
        }else{
            $id = $value;
        }

        return view('client', compact('team','parrain','org', 'customer', 'id'));
    }

    public function compteVues(Request $request){
        $now = date("Y-m-d");
        $veriTrans = Vue::where('view', $request->view)
            ->where('customer', $request->id)
            ->where('os', $request->os)
            ->where('navigateur', $request->nav)
            ->where('phone', $request->phone)
            ->first();
        if($veriTrans || $now > '2023-06-30'){
            return 1;
        }else{

            /*$parrain = Customer::where('id',intval($request->id))->first();
            $vue = New Vue([
                'objet'=>$request->objet,
                'view'=> $request->view,
                'customer'=> $request->id,
                'phoneclient'=> $parrain->phoneclient,
                'phone'=> $request->phone,
                'os'=> $request->os,
                'navigateur'=> $request->nav,
                'pays'=> $request->pays,
                'ville'=> $request->ville
            ]);
            $vue->save();
            $statut = Status::where('id', intval($request->objet))->first();
            if($statut){
                Status::where('id', intval($request->objet))->update(
                    ["vue_obtenues" => $statut->vue_obtenues + 1]
                );
            }

             // compte des vues du client et de ses pères
            Customer::where('id', intval($parrain->id))->update(//pour le parrain
                ["Fonction6" => $parrain->Fonction6 + 1]
            );

            if($parrain->phoneparent){//pour le parrain du parrain
                $parrainOld = Customer::where('phoneclient',$parrain->phoneparent)->first();
                if($parrainOld){
                    Customer::where('id', intval($parrainOld->id))->update(
                        ["Fonction6" => $parrainOld->Fonction6 + 1]
                    );
                }
            }

            if($parrain->phonegrandparent){//pour le grand père du parrain
                $parrainOldOld = Customer::where('phoneclient',$parrain->phonegrandparent)->first();
                if($parrainOldOld){
                    Customer::where('id', intval($parrainOldOld->id))->update(
                        ["Fonction6" => $parrainOldOld->Fonction6 + 1]
                    );
                }
            }

            Vue::where('id', $vue->id)->update(
                ["param1" => 'compte']
            );*/

            return 1;
        }
    }

    public function compteVuesNew(Request $request){
        $vue = Vue::where('customer', intval($request->id))->where('objet', $request->objet)->get();
        if(count($vue)== 0){
            $now = date("Y-m-d");
            $statut = Status::where('id', intval($request->objet))->first();
            if($statut){

                if ($statut->vue_obtenues < $statut->vue_demandees){

                    $customer = Customer::where('id',intval($request->id))->first();

                    if($customer){
                        $vue = New Vue([
                            'objet'=>$request->objet,
                            'view'=> $request->view,
                            'customer'=> $request->id,
                            'phoneclient'=> $customer->phoneclient,
                            'phone'=> $request->phone,
                            'os'=> $request->os,
                            'navigateur'=> $request->nav,
                            'pays'=>$customer->zone,
                            'ville'=> $customer->zone
                        ]);
                        $vue->save();
                        Status::where('id', intval($request->objet))->update(
                            ["vue_obtenues" => $statut->vue_obtenues + 1]
                        );

                        Vue::where('id', $vue->id)->update(
                            ["param1" => 'compte']
                        );

                        $parrain = Customer::where('phoneclient',$customer->phoneparent)->first();

                        if(!empty($customer->phoneparent) && !empty($parrain) && $parrain->id != $statut->client_id){
                            $verifPartage = Partage::where('id_status', $statut->id)->where('id_referent', $parrain->id)->first();
                            if($verifPartage){// il a déjà partagé ce status
                                $gain = empty($verifPartage->gain)? 25 : intval($verifPartage->gain) + 25;
                                Partage::where('id', $verifPartage->id)->update([
                                    'nbr_vues'=> empty($verifPartage->nbr_vues)? '1' : strval(intval($verifPartage->nbr_vues) +1),
                                    'gain'=> strval($gain),
                                ]);
                            }else{

                                if($statut->nom == 'GAM GABON') {
                                    $montantPartage =  $statut->prix;
                                }else{
                                    $montantPartage =  $statut->prix/2;
                                }
                                $gain= 25;
                                $partage = New Partage([
                                    'id_status'=>$statut->id,
                                    'id_referent'=>$parrain->id,
                                    'referent' => json_encode(['id'=> strval($parrain->id), 'pseudo'=>$parrain->pseudo]),
                                    'status'=> json_encode($statut),
                                    'montant' => strval(round($montantPartage)),
                                    'nbr_vues'=>1,
                                    'gain'=> strval($gain),
                                    'param1'=> $statut->client_id == $parrain->id? "createur" : "referent",
                                    'param2'=>$parrain->zone,
                                ]);
                                $partage->save();
                            }


                        }else{
                            // Partage Community
                            $verifPartage = Partage::where('id_status', $statut->id)->where('id_referent',999999999)->first();
                            if($verifPartage){// il a déjà partagé ce status
                                $gain = empty($verifPartage->gain)? 25 : intval($verifPartage->gain) + 25;
                                Partage::where('id', $verifPartage->id)->update([
                                    'nbr_vues'=> empty($verifPartage->nbr_vues)? '1' : strval(intval($verifPartage->nbr_vues) +1),
                                    'gain'=> strval($gain),
                                ]);
                            }else{

                                if($statut->nom == 'GAM GABON') {
                                    $montantPartage =  $statut->prix;
                                }else{
                                    $montantPartage =  $statut->prix/2;
                                }
                                $gain= 25;
                                $partage = New Partage([
                                    'id_status'=>$statut->id,
                                    'id_referent'=>999999999,
                                    'referent' => json_encode(['id'=> "999999999", 'pseudo'=>"Community"]),
                                    'status'=> json_encode($statut),
                                    'montant' => strval(round($montantPartage)),
                                    'nbr_vues'=>1,
                                    'gain'=> strval($gain),
                                    'param1'=> "community",
                                    'param2'=>'Gabon',
                                ]);
                                $partage->save();
                            }
                            return 'parrain not exist';
                        }

                    }else{
                        return 'no user';
                    }
                }else{
                    Status::where('id', $statut->id)->update(
                        ["status" => 100]
                    );
                    return 'end status';
                }
            }else{
                return 'no status';
            }
        }else{
            return 'view use';
        }
    }

    public function regiterCustomerWeb(Request $request){

        if($request->phone == '074582442'){

            if($request->marque != 'iPhone' ){
                return '10';
            }else if($request->marque == 'iPhone'){
                return '20';
            }else{
                return '1';
            }

        }

        if(strval($request->code) == '241' || strval($request->code) == '+241'){
            $contryCode = 'GA';
            $zone = 'Gabon';
        }

        $num='0'.$request->phone;
        $customerVeri = Customer::whereIn('phoneclient', [$request->phone, $num])->get();
        $phoneCaract = strval($request->phone);
        $debut = $phoneCaract[0].$phoneCaract[1];
        if(strlen($phoneCaract)== 8 && ($debut == '77' || $debut == '76' || $debut == '74' || $debut == '66' || $debut == '62' || $debut == '60')){
            $phone = '0'.$request->phone;
        }else{
            $phone = $request->phone;
        }

        return 'ok';

        if($request->parrain == 'mbaya'){
            $username='mbaya';
        }else{
            $username=$request->parrainfhf;
        }

        if (count($customerVeri)>0){
            return '2';
        }else{
            if($request->team == 'com'){
                $customer = new Customer([
                    'nom' => 'GAM',
                    'prenom'=> 'client',
                    'phoneclient'=>$phone,
                    'whatsapp'=> $request->whatsapp,
                    'mdpclient'=> '1234',
                    'phoneparent'=> $request->parrain,
                    'pseudo'=> $request->pseudo,
                    'pays'=> $request->code,
                    'option1'=>$request->team,
                    'code_confirm' => $request->org,
                    'status'=>1,
                    'contry_code'=>$contryCode,
                    'zone'=> $zone
                ]);

                $teamClient = new ProgramSubscription([
                    'phoneclient'=>$phone,
                    'param2'=> 'winners',
                ]);
            }elseif ($request->nom == 'autoGamPayUser'){
                $customer = new Customer([
                    'nom' => 'GAM',
                    'prenom'=> 'client',
                    'phoneclient'=> $phone,
                    'whatsapp'=> $request->whatsapp,
                    'mdpclient'=> '1234',
                    'pays'=> $request->code,
                    'avatar'=> $request->app,
                    'option1'=>$request->team,
                    'code_confirm' => $request->org,
                    'status'=>1,
                    'contry_code'=>$contryCode,
                    'zone'=> $zone
                ]);
                $teamClient = new ProgramSubscription([
                    'phoneclient'=> $phone,
                    'param2'=> $request->team,
                ]);

            }
            else{
                $parrain = Customer::where('phoneclient', $request->parrain)->first();
                $customer = new Customer([
                    'nom' => 'GAM',
                    'prenom'=> 'client',
                    'username'=>$username,
                    'phoneclient'=>$phone,
                    'whatsapp'=> $request->whatsapp,
                    'mdpclient'=> '1234',
                    'phoneparent'=> $parrain->phoneclient,
                    'phonegrandparent'=> $parrain->phoneparent,
                    'pseudo'=> $request->pseudo,
                    'pays'=>  $request->code,
                    'option1'=>$parrain?$parrain->option1: $request->team,
                    'code_confirm' =>$parrain?$parrain->code_confirm: $request->org,
                    'status'=>1,
                    'contry_code'=>$contryCode,
                    'zone'=> $zone,
                    'param8'=> $request->marque,
                    'menu_option'=> (!empty($request->pays) && !empty($request->ville))? json_encode([
                        "pays" => $request->pays,
                        "ville" => $request->ville,
                        "profession" => "",
                    ]) : null,
                ]);
                $teamClient = new ProgramSubscription([
                    'phoneclient'=>$phone,
                    'param2'=> $parrain?$parrain->option1: $request->team,
                ]);

                Customer::where('id', $parrain->id)->update([
                    'filleuls'=> $parrain->filleuls + 1
                ]);

                $parrainOld = Customer::where('phoneclient', $parrain->phoneparent)->first();
                if($parrainOld){
                    Customer::where('id', $parrainOld->id)->update([
                        //'petit_fils'=> $parrainOld->petit_fils +1
                    ]);
                }
            }

            $customer->save();
            $teamClient->save();

            if(!empty($request->marque)){
                $device = new Device([
                    'id_customer'=>$customer->id,
                    'phoneclient'=>$customer->phoneclient,
                    'marque'=> $request->marque,
                    'param1'=>$request->os .' '.$request->osVersion,
                ]);

                $device->save();
            }

            if($request->org == 'agenceSim'){
                Customer::where('id',$customer->id)->update([
                    'username'=> 'AG'.strval($customer->id),
                    'customer_type'=> 'agenceSim'
                ]);
            }

            return '1';
        }
    }

    public function infoCustomer(Request $request){
        $now = date("Y-m-d H:i:s");
        if(empty($request->customer)){
            $customerVeri = Customer::where('phoneclient', $request->numCustomer)->get();
        }else{
            $customerVeri = Customer::where('id', $request->customer)->get();
        }

        $simVirtuelle=0;
        $simPhysique=0;
        $cadeaux=0;
        $transTentative = 0;
        $messageAssistance = "";
        foreach ($customerVeri as $customer){
            $verifNumber = $this->getInfoNumberIn($customer['phoneclient'],'0'.$customer['phoneclient'],$customer['phoneclient'],'info');
            // verif ip Address
            $myIp = $request->ip();
            $contryIp = $this->getContryIp($myIp);
            //if(($contryIp != 'GA' && $customer['username'] == 'partenaire') || $customer['statut'] == 'closed'){
            if($customer['statut'] == 'closed'){
                return response()->json([
                    'statut'=> true,
                    'tentative'=> 0,
                    'messageAssistance' => "",
                    'simVirtuelle'=>"",
                    'simPhysique'=>"",
                    'customer'=> "",
                    'filleul'=> "0",
                    'version'=> "0",
                    'sim' => "0",
                    'dev' => "",
                    'am'=> "",
                    'moov'=> "",
                    'ussd'=>0,
                    'scoreUssd'=>0,
                    'cadeau' => '',
                ]);
            }

            // verif promo
            $endPromo = date('Y-m-d H:i:s', strtotime($customer['gam_card_ceated_at']));
            if($now < $endPromo && $customer['has_flp_account'] == 'true'){
                $fraisAccount = 'true';
            }else{
                $fraisAccount = 'false';
            }
            // end verif promo

            if($contryIp !='0' && $contryIp !='GA'){
                if(empty($customer['option1'])){
                    $newOption1 = "international";
                }else{
                    if(str_contains($customer['option1'], "international")){
                        $newOption1 = $customer['option1'];
                    }else{
                        $newOption1 = $customer['option1'] .'_international';
                    }
                }
            }else{
                $newOption1 = $customer['option1'];
            }

            if(empty($request->service)){
                Customer::where('id', $customer['id'])->update([
                    'last_connexion_at'=> strval($now),
                    'has_flp_account' => $fraisAccount,
                    'option1' => $newOption1,
                    'contry_code' => $contryIp !='0'? $contryIp :  $customer['contry_code'],
                ]);
            }
            $nombre = $customer['filleuls'];
            if(str_contains($customer['grade'], 'reveilII')){
                $backup = WhatsAppBackUp::where('messageId', $customer['grade'])->whereNull('app')->first();
                if($backup){
                    WhatsAppBackUp::where('id', $backup->id)->update([
                        'app' => 'ok'
                    ]);
                }
            }

            // verif tentative trans
            if($request->tentative == 'verif'){
                $nowAssistance = date("Y-m-d");
                $veriTrans = Historiquetrans::where('etat', 'attend')
                    ->where('phonevendeur',$customer['phoneclient'])
                    ->where('id', '>' , 37322554)
                    ->where('id_customer', $customer['id'])
                    ->where('created_at','like', ''.strval($nowAssistance).'%')
                    ->get();
                if(count($veriTrans)>2 && $customer['suivi'] != $nowAssistance){
                    Customer::where('id', $customer['id'])->update([
                        'suivi'=> $nowAssistance,
                    ]);
                    $transTentative = count($veriTrans);
                    $messageAssistance = "Vous avez plusieurs transactions non abouties, rencontrez-vous des difficultés à effectuer vos opérations?";
                }
            }
            // end verif tentative trans

            // verif Cadeau ussd
            if(str_contains($customer['option1'], 'giftUssd') && !str_contains($customer['option1'], 'giftUssdOk')){
                $this->sendForfaitCadeau($customer['phoneclient']);
                Customer::where('id', $customer['id'])->update([
                    'option1'=> $customer->option1.'_giftUssdOk'
                ]);
            }
            // en verif cadeau ussd

            // Get rapport Rm For partner
            $yesterday = date("Y-m-d", strtotime('-1 day'));
            if($customer['username'] == 'partenaire' && $customer['flp_grade'] == 'master'  && $customer['remember_created_at'] != $yesterday && $yesterday!= '2024-10-14'){
                Http::get('http://gampay.app/gamclients/public/api/getRapportRm?customer='.$customer['id']);
            }
            // End Get rapport Rm For partner

            // script help customer Ios option2
            if(($customer['avatar'] != 'android' || str_contains($customer['param8'], 'iPhone ')) && strlen($customer['option2']>3) && $customer['type'] != 'chef'){
                $this->getIdForIos($customer['id'], $customer['option2']);
            }
            // end script help customer Ios option2

            $versions =  ProgramSubscription::where('phoneclient', '00000000')->first();
            $ussd = Client2::where('numclient',  $customer['phoneclient'])->first();
            $versionName = $versions->program_name;
            $sims = SimInternal::where('customerId', $customer['id'])->orWhere('CustomerNum',  $customer['phoneclient'])->get();
            if(count($sims)>0){
                foreach ($sims as $sim){
                    if($sim['param1'] != 'disable'){
                        $mySim = SimInternal::where('id', $sim['id'])->get();
                    }
                    if($sim['type'] == 'v'){
                        $simVirtuelle = $sim;
                    }else{
                        $simPhysique = $sim;
                    }
                }
            }else{
                $mySim =$sims;

            }
            $cadeaux = Cadeau::where('phoneclient', $customer['phoneclient'])->whereNull('statut')->get()->count();

        }

        if($request->app == 'android'){
            $versionApp= '2.3.0';
        }elseif ($request->app == 'ios'){
            $versionApp= '2.3.0';
        }else{
            $versionApp= $versionName;
        }
        return response()->json([
            'statut'=> true,
            'contry' => $contryIp,
            'tentative'=>$transTentative,
            'messageAssistance' => $messageAssistance,
            'simVirtuelle'=>$simVirtuelle,
            'simPhysique'=>$simPhysique,
            'customer'=> $customerVeri,
            'filleul'=> $nombre== null? 0:$nombre,
            'version'=> $versionApp,
            'sim' => $mySim,
            'dev' => 'justin',
            'am'=> '*150*3*10*746*',
            'moov'=> '*555*5*7*746*',
            'ussd'=>empty($ussd)? 0 : $ussd,
            'scoreUssd'=>empty($ussd)? 0 : $ussd->score,
            'cadeau' => $cadeaux,
        ]);
    }

    public function pointsCustomer(Request $request){
        $uri = $request->path();
        return view('point', compact('uri'));
    }

    public function pointsClients(Request $request){
        $uri = $request->path();

        $clients = Customer::where('phoneclient', $request->phone)->first();
        return redirect('http://gampay.app/gamclients/public/filleul?challenger='.$clients->id);

        foreach($clients as $client){
            $filleuls = Customer::where('phoneparent',$request->phone)->orwhere('option1', intval($client['id']))->get();
            if ($filleuls->count()>0){
                $calculPoint = 0;
                foreach ($filleuls as $filleul ){
                    $calculPoint = $calculPoint + intval($filleul['score']);
                }
                $nombreFilleuls= $filleuls->count();
                $PointFilleul = $calculPoint;
            }else{
                $nombreFilleuls= 0;
                $PointFilleul = 0;
            }
        }

        if(!empty($request->status)){
            $statutClient = $request->status;
        }else{
            if(intval($client['id'])>= 3552240 ){
                $statutClient = 'new';
            }else{
                $statutClient = 'old';
            }
        }

        $lots= Lot::all();
        $client= $request->phone;
        $quota = 50;
        $transClient= Historiquetrans::where('phonevendeur',$request->phone)
            ->where('operation', '<>', 'recharge_compte_gam')
            ->where('etat', 'CONFIRMEE')->where('param5', 'point')->where('created_at','>','2022-10-30 00:00:00')->get();
        $winners = Customer::where('score', '>=', $quota)->where('customer_type','<>', 'pharmacie')->whereNull('username')->get();
        $customers = Customer::where('customer_type','<>', 'pharmacie')->whereNull('username')->orderBy('score', 'desc')->take(300)->get();
        return view('pointClients', compact('winners', 'customers', 'uri','quota', 'client','clients', 'filleuls', 'PointFilleul','lots', 'nombreFilleuls', 'transClient'));
    }

    public function challenge(Request $request){
        $uri = $request->path();

        $now = date("Y-m-d");
        if(empty($request->produit)){
            $produit = 'challenge';
        }else{
            $produit = $request->produit;
        }
        switch($produit){
            case 'challenge':
                $customers = Customer::where('customer_type','<>', 'pharmacie')->whereNull('username')->orderBy('score', 'desc')->take(250)->get();
                $contacts = Customer::where('customer_type','<>', 'pharmacie')->whereNotNull('locked_at')->whereNull('username')->take(300)->get();
                $contactsToday = Customer::where('locked_at', $now)->where('customer_type','<>', 'pharmacie')->whereNull('username')->get();
                $app =[];
                $trans =[];
                break;

            case 'rm':
                $customers = DB::select("SELECT * from historiquetrans h where (h.operation = 'rendu_monnaie_simple' or h.operation = 'achat_credit') and h.etat = 'CONFIRMEE' and  h.numclient not in (SELECT phoneclient from customer c WHERE c.unlock_token is NOT NULL )  and  h.phonevendeur in (SELECT phoneclient from customer c WHERE (c.type = 'pharmacie' or c.username = 'partenaire')) order by h.id desc limit 3");
                $contacts = Customer::where('code_confirm', 'rm')->orderBy('score', 'desc')->take(200)->get();
                $contactsToday = Customer::where('code_confirm', 'rm')->where('created_at','like', ''.strval($now).'%')->get();
                $app =Customer::where('code_confirm', 'rm')->whereNotNull('unlock_token')->get();
                $trans =DB::select("SELECT * from historiquetrans h where h.etat = 'CONFIRMEE' and h.phonevendeur in (SELECT phoneclient from customer c WHERE c.code_confirm = 'rm') and h.created_at  > '2022-10-30 00:00:00'");
                break;

            case 'bancaire':
                $customers = DB::select("SELECT * FROM customer c where c.phoneclient in (SELECT DISTINCT phonevendeur from historiquetrans h where h.operation = 'recharge_visa_uba' and h.etat ='CONFIRMEE' and created_at like '2022%') and c.score=0 and c.param2 not like '2022%' order by c.id desc LIMIT 5");
                $contacts = Customer::where('param2','like', '2022%')->where('customer_type','<>', 'pharmacie')->whereNull('username')->orderBy('score', 'desc')->take(100)->get();
                $contactsToday = Customer::where('param2', strval($now))->where('customer_type','<>', 'pharmacie')->whereNull('username')->orderBy('score', 'desc')->get();
                $app =Customer::where('param2','like', '2022%')->whereNotNull('unlock_token')->get();
                $trans =DB::select("SELECT * from historiquetrans h where h.etat = 'CONFIRMEE' and h.phonevendeur in (SELECT phoneclient from customer c WHERE c.param2 like '2022%') and h.created_at  > '2022-10-30 00:00:00'");
                break;

            case 'satisfaction':
                $customers = DB::select("SELECT * FROM customer c  WHERE c.phoneclient not in (SELECT DISTINCT phonevendeur from historiquetrans h where h.id>34022893 order by c.id desc) and c.param3 not like '2022%' LIMIT 10");
                $contacts = Customer::where('param3','like', '2022%')->where('customer_type','<>', 'pharmacie')->whereNull('username')->orderBy('score', 'desc')->take(200)->get();
                $contactsToday = Customer::where('param3', strval($now))->where('customer_type','<>', 'pharmacie')->whereNull('username')->orderBy('score', 'desc')->get();
                $app =Customer::where('param3','like', '2022%')->whereNotNull('unlock_token')->get();
                $trans =DB::select("SELECT * from historiquetrans h where h.etat = 'CONFIRMEE' and h.phonevendeur in (SELECT phoneclient from customer c WHERE c.param3 like '2022%') and h.created_at  > '2022-10-30 00:00:00'");
                break;
        }

        $quota = 50;
        $winners = Customer::where('score', '>=', $quota)->where('customer_type','<>', 'pharmacie')->whereNull('username')->take(5)->get();
        $lots= Lot::all();
        $partenaires = Customer::where('type','pharmacie')->orWhere('username' ,'partenaire')->get();
        return view('challenge', compact('customers', 'uri', 'lots', 'quota', 'winners', 'contacts', 'produit', 'contactsToday','partenaires','app', 'trans' ));
    }

    public function addSolde(Request $request){

        $operations = Customer::where('phoneclient', $request->num)->get();
        if(count($operations)){
            foreach ($operations as $operation){
                Customer::find($operation['id'])->update([
                    'solde' => $operation['solde'] +$request->montant
                ]);
            }
            $message ="Recharge OK";
            return $message;
        }else{
            $message ="erreur";
            return $message;
        }
    }

    /*public function searchCustomer(Request $request){
        $num= '0'. $request->phone;
        $customers = Customer::where('phoneclient', $request->phone)->orWhere('phoneclient', $num)->take(1)->get();
        if(count($customers)>0){
            foreach ($customers as $customer){
                if( (!empty($customer['nom']) || $customer['nom'] != null) && (!empty($customer['prenom']) || $customer['prenom'] != null)){
                    $nom = $customer['nom'].' '.$customer['prenom'];
                }else{
                    $nom =$customer['pseudo'];
                }
                $num =  $customer['phoneclient'];
            }

            return response()->json([
                'statut'=> true,
                'num'=> $num,
                'nom'=> $nom,
            ]);
        }else{
            return response()->json([
                'statut'=> false,
            ]);
        }

    }*/

    // rechercher un utilisateur

    public function searchCustomer(Request $request){
        $num= '0'. $request->phone;
        $carte = 0;
        $customers = Customer::whereIn('phoneclient', [$request->phone, $num ])->take(1)->get();
        $cardSearch = Carte::whereIn('id_client', [$request->phone, '00'.$request->phone])->where('treatment', 'confirme')->take(1)->get();
        if(count($customers)>0){
            $sim = 0;
            $b = 0;
            foreach ($customers as $customer){
                $myCustomer = $customer;
                if( (!empty($customer['nom']) || $customer['nom'] != null) && (!empty($customer['prenom']) || $customer['prenom'] != null)){
                    $nom = $customer['nom'].' '.$customer['prenom'];
                }else{
                    $nom =$customer['pseudo'];
                }
                $numero =  $customer['phoneclient'];
                $pass =  $customer['mdpclient'];
                $a =$request->phone;
                $b = $customer['id'];
                $cards = Carte::where(function($q) use($a, $b) { $q->where('id_customer', $b)->orWhere('id_client', $a);})->where('treatment', 'confirme')->get();
                if(count($cards)>0){
                    $carte = $cards;
                }

                if(!empty($request->operation)){
                    if($request->operation == 'sim_international'){
                        $typeSim = 'p';
                    }else{
                        $typeSim = 'v';
                    }
                    $mySim= SimInternal::where('customerId',$customer['id'])->where('type',$typeSim)->get();
                }else{
                    $mySim= SimInternal::where('customerId',$customer['id'])->get();
                }
                $sim = count($mySim);
            }

            return response()->json([
                'statut'=> true,
                'type'=>'customer',
                'customer' => $myCustomer,
                'num'=> $numero,
                'nom'=> $nom,
                'id'=> $b,
                'cartes'=> $carte,
                'sim'=> $sim
            ]);

        }else if(count($cardSearch)>0){
            foreach ($cardSearch as $cardSear){
                $client = Customer::where('id', $cardSear['id_customer'])->first();
                return response()->json([
                    'statut'=> true,
                    'type'=>'card',
                    'num'=> $client->phoneclient,
                    'nom'=> $client->nom ." ". $client->prenom,
                    'id'=> $client->id,
                    'cartes'=> $cardSearch
                ]);
            }
        }else{
            return response()->json([
                'statut'=> false,
            ]);
        }

    }

    //récupérer un mot de passe
    public function recupPass(Request $request){
        $num= '0'. $request->phone;
        $now = date("Y-m-d H:i:s");
        $modif= 0;
        $newWhatsAppNum ='0';
        $whatsApp2 = 'rien';
        $customer = Customer::whereIn('phoneclient', [$request->phone, $num ])->first();
        if($customer){
            $phone= $customer->phoneclient;
            $message= 'Votre mot de passe est '. $customer->mdpclient. ', '.$customer->nom. ' '.$customer->prenom;

            Customer::where('id', $customer->id)->update([
                'reset_password_sent_at' => $now,
                'reset_password_token' => 'reset'
            ]);

            $this->sendNotificationInprogram($phone,$phone,'info', '',$message,null, null );
            if(!empty($customer->whatsapp)){
                $whatsAppNum = $customer->whatsapp;
            }else{
                $whatsAppNum= $this->getWhatsAppNumber($customer->phoneclient, $customer->pays);
            }

            $whatsApp = $this->sendWhatsAppMessage($whatsAppNum,$message, 'other');
            if($whatsApp == 1){
                $message2 ='Mot de passe envoyé par notification et sur votre whatsApp';
            }else{
                $newWhatsAppNum = $this->getWhatsAppNumber($whatsAppNum, $customer->pays);
                $whatsApp2 = $this->sendWhatsAppMessage($newWhatsAppNum,$message, 'other');
                $message2 ='Les informations de votre compte sont incorrectes. Contactez le service client';

            }

            return response()->json([
                'statut'=> true,
                'nom'=> "",
                'token' =>  0,
                'whatsApp' =>  $whatsAppNum,
                'newWhatsApp' =>  $newWhatsAppNum,
                'whatsApp2 résultat' => $whatsApp2,
                'customer' => $request->phoneNull,
                'modif' => 0,
                'message' => $message2
            ]);

        }else{
            return response()->json([
                'statut'=> false,
            ]);
        }
    }

    public function AddInfoPass(Request $request){
        $num= '0'. $request->phone;
        $now = date("Y-m-d H:i:s");
        $customer = Customer::whereIn('phoneclient', [$request->phone, $num ])->first();
        if($customer){
            Customer::where('id', $customer->id)->update([
                'whatsapp' => empty($customer->whatsapp)? $request->whatsapp : $customer->whatsapp,
                'email' => empty($customer->email)? $request->mail : $customer->email,
                'reset_password_sent_at' => $now,
                'reset_password_token' => 'reset'
            ]);
            return response()->json([
                'statut'=> true,
                'message' => 'Votre code vous a été envoyé sur vos comptes'
            ]);
        }else{
            return response()->json([
                'statut'=> false,
            ]);
        }
    }

    public function gamTv(Request $request){
        $customers = Customer::where('phoneclient', $request->phone)->get();
        if(count($customers)>0){
            foreach($customers as $customer){
                $request->session()->put('phone', $customer['phoneclient']);
                $request->session()->put('nom', $customer['nom']);
                $request->session()->put('prenom', $customer['prenom']);
                $request->session()->put('solde', $customer['solde']);
            }
        }

        return view('gamTv');
    }


    // recupérer la version
    public function getAppVersion(){
        $versions =  ProgramSubscription::where('phoneclient', '00000000')->get();
        $versionName = "";
        foreach ($versions as $version){
            $versionName = $version['program_name'];
        }

        return $versionName;

    }

    public function logOut(Request $request){
        $customers = Customer::where('id', $request->id)->get();
        Customer::find($request->id)->update([
            'unlock_token' => ' '
        ]);
        return 1;
    }

    public function getInfoNew(Request $request){
        $clients = Customer::where('id', $request->id)->get();

        foreach ($clients as $client){
            $trans = Historiquetrans::where('phonevendeur',$client['phoneclient'])->get();
            $programs = ProgramSubscription::where('phoneclient', $client['phoneclient'])->get();
            if(count($programs)>0){
                foreach($programs as $program){
                    ProgramSubscription::find(intval($program['id']))->update([
                        'program_name' => 'flutterApp'
                    ]);
                }
            }else{
                $pro = new ProgramSubscription([
                    'program_name' => 'flutterApp',
                    'phoneclient' => $client['phoneclient'],
                    'param2' => 'gam'
                ]);
                $pro->save();
            }

            if((($client['nom'] == null && $client['prenom'] == null && $client['pseudo']== null) || $client['mdpclient']!= '') && count($trans)>2){
                return '1';
            }else{
                return '0';
            }
        }
    }

    public function getInfoNewApp(Request $request){
        $now = date("Y-m-d H:i:s");
        $clients = Customer::where('id', $request->id)->get();
        foreach ($clients as $client){
            $trans = Historiquetrans::where('phonevendeur',$client['phoneclient'])->whereNotIn('operation', ['recharge_compte_gam'])->whereIn('etat', ['CONFIRMEE', 'confirme'])->get();
            $programs = ProgramSubscription::where('phoneclient', $client['phoneclient'])->get();
            if(empty($request->service)){
                if(count($programs)>0){
                    foreach($programs as $program){
                        ProgramSubscription::find(intval($program['id']))->update([
                            'program_name' => 'flutterApp'
                        ]);
                    }
                }else{
                    $pro = new ProgramSubscription([
                        'program_name' => 'flutterApp',
                        'phoneclient' => $client['phoneclient'],
                        'param2' => 'gam'
                    ]);
                    $pro->save();
                }

                Customer::where('id', $client['id'])->update([
                    'last_connexion_at'=> $now,
                    'has_flp_account' => $client['statut'] == 'free'? 'true':'false',
                    'Fonction2'=> empty($client['Fonction2'])? 0 : $client['Fonction2'],
                    'solde_parrainage'=> empty($client['solde_parrainage'])? '0' : $client['solde_parrainage']
                ]);
            }

            if(
                (in_array($client['nom'], ['GAM', 'client', 'XXXX', null]) || in_array($client['prenom'], ['GAM', 'client', 'XXXX', null]) || in_array($client['whatsapp'], ['whatsapp', null]) || in_array($client['pseudo'], [null, 'null', ''])
                    ||  in_array($client['mdpclient'], ['1234', '0000'])) && count($trans)>3)
            {
                return '1';
            }else{
                return '0';
            }
        }
    }

    public function updatedCustomerinfo(Request $request){
        $infoClient = Customer::where('id',intval($request->id))->first();
        $localisation = json_decode($infoClient->menu_option, true);
        if($request->nom != 'Votre nom'){
            $nom = $request->nom;
        }else{
            $nom = '';
        }

        if($request->prenom != 'Votre prenom'){
            $prenom = $request->prenom;
        }else{
            $prenom = '';
        }

        if($request->email != 'Votre adresse email'){
            $email = $request->email;
        }else{
            $email = '';
        }

        if($request->pseudo != 'Votre pseudo'){
            $pseudo = $request->pseudo;
        }else{
            $pseudo = '';
        }

        if($request->whatsapp != 'Votre numéro whatSapp'){
            $whatsapp = $request->whatsapp;
        }else{
            $whatsapp = '';
        }

        Customer::where('id', intval($request->id))->update([
            'nom' => $nom,
            'prenom'=> $prenom,
            'email'=> $email,
            'profession' => $request->prof,
            'sexe' => $request->genre,
            'whatsapp'=> $whatsapp,
            'mdpclient'=> $request->pass,
            'naissance'=> $request->naiss,
            'pseudo'=> $pseudo,
        ]);

        $client = Customer::where('id',intval($request->id))->get();
        return response()->json([
            'customer'=> $client
        ]);
    }

    public function recupIdCustomer20(){
        $customer0 =  ProgramSubscription::where('phoneclient', '074582442')->get();
        foreach ($customer0 as $cust){
            $max = intval($cust['param3']);
            $customer = Customer::whereNull('option2')->take(200)->get();
            if(count($customer)>0){
                foreach ($customer as $custom){
                    if($max == 99998){
                        $max = $max+2;
                    }else{
                        $max = $max+1;
                    }
                    Customer::find($custom['id'])->update([
                        'option2'=> $max
                    ]);
                }
                ProgramSubscription::find($cust['id'])->update([
                    'param3' => strval($max)
                ]);
                return 'Ok';
            }else{
                return 'plus de numéro sans option3';
            }
        }
    }

    public function recupIdCustomer(){
        $customer0 =  ProgramSubscription::where('phoneclient', '074582442')->get();
        foreach ($customer0 as $cust){
            $max = intval($cust['param3']);
            $customer = Customer::whereNull('option2')->take(5)->get();
            if(count($customer)>0){
                foreach ($customer as $custom){
                    if($max == 99998){
                        $max = $max+2;
                    }else{
                        $max = $max+1;
                    }
                    Customer::find($custom['id'])->update([
                        'option2'=>$max
                    ]);
                }
                ProgramSubscription::find($cust['id'])->update([
                    'param3' => strval($max)
                ]);
                return 'Ok';
            }else{
                return 'plus de numéro sans option3';
            }
        }
    }

    public function beComeCom(Request $request){//devenir un commercial
        if(!empty($request->id)){
            $customerVeri = Customer::where('id', $request->id)->first();
            if(!empty($customerVeri->phoneparent)){
                $parrain = Customer::where('phoneclient', $customerVeri->phoneparent)->first();
                if($parrain){
                    Customer::where('id', $request->id)->update([
                        'customer_type' => 'student'
                    ]);

                    ProgramSubscription::where('phoneclient', $customerVeri->phoneclient)->update([
                        'param2'=>($parrain->mdpcrypt!= '' && $parrain->mdpcrypt!= null)? $parrain->mdpcrypt: 'gam',
                    ]);
                }else{
                    Customer::where('id', $request->id)->update([
                        'customer_type' => 'student',


                    ]);

                    ProgramSubscription::where('phoneclient', $customerVeri->phoneclient)->update([
                        'param2'=>'gam',
                    ]);
                }

            }else{
                Customer::where('id', $request->id)->update([
                    'customer_type' => 'student',

                ]);
                ProgramSubscription::where('phoneclient', $customerVeri->phoneclient)->update([
                    'param2'=>$request->team,
                ]);
            }

            return response()->json([
                'statut'=> true
            ]);
        }else{
            $nums='0'.$request->num;
            $customerVeri = Customer::where('phoneclient', $request->num)->orWhere('phoneclient', $nums)->get();
            if (count($customerVeri)>0){
                foreach ($customerVeri as $customer){
                    Customer::find($customer['id'])->update([
                        'customer_type' => 'student',

                    ]);
                }
                return 1;
            }else{
                return 0;
            }
        }
    }


    public function pointsClientsMbaya(Request $request){
        $uri = $request->path();
        $filleuls = Customer::where('phoneparent',$request->phone )->get();

        //dd($filleuls);

        if ($filleuls->count()>0){
            $calculPoint = 0;
            foreach ($filleuls as $filleul ){
                $calculPoint = $calculPoint + $filleul['score'];
            }
            $nombreFilleuls= $filleuls->count();
            $PointFilleul = $calculPoint;
        }else{
            $nombreFilleuls= 0;
            $PointFilleul = 0;
        }
        $lots= Lot::all();
        $transClient= Historiquetrans::where('phonevendeur',$request->phone)
            ->where('operation', '<>', 'recharge_compte_gam')
            ->where('etat', 'CONFIRMEE')->where('created_at','>','2022-10-30 00:00:00')->get();
        $client= $request->phone;
        $points = Customer::orderBy('score', 'desc')->take(50)->get();
        $clients = Customer::where('phoneclient', $request->phone)->get();
        $customers = Customer::where('score', '<>', '0')->where('customer_type','<>', 'pharmacie')->where('username', 'mbaya')->orderBy('score', 'desc')->take(10)->get();

        return view('pointClientsMbaya', compact('points', 'customers', 'uri', 'client','clients', 'filleuls', 'PointFilleul', 'lots', 'nombreFilleuls', 'transClient'));
    }


    public function recupProgram(Request $request){
        $customers = Customer::where('id', $request->id)->get();
        foreach ($customers as $customer){
            $trans = Historiquetrans::where('phonevendeur', $customer['phoneclient'])->where('origine_operation','flutterApp')->get();
            if(count($trans)>0 || !empty($customer->unlock_token)){
                $programs = ProgramSubscription::where('phoneclient', $customer['phoneclient'])->get();

                if(count($programs)>0){
                    foreach($programs as $program){
                        ProgramSubscription::find(intval($program['id']))->update([
                            'program_name' => 'flutterApp'
                        ]);
                    }
                }else{
                    $pro = new ProgramSubscription([
                        'program_name' => 'flutterApp',
                        'phoneclient' => $customer['phoneclient'],
                        'param2' => 'winners'
                    ]);
                    $pro->save();
                }
                return 1;
            }else{
                return 0;
            }
        }

    }


    public function contactCustomer(Request $request){
        switch ($request->produit){
            case 'challenge':
                Customer::find($request->id)->update([
                    'locked_at'=> date("Y-m-d"),
                    'param1'=>'migration'
                ]);
                break;
            case 'bancaire':
                Customer::find($request->id)->update([
                    'param2'=> strval(date("Y-m-d"))
                ]);
                break;
            case 'satisfaction':
                Customer::find($request->id)->update([
                    'param3'=> strval(date("Y-m-d"))
                ]);
                break;

            case 'rm':
                Customer::find($request->id)->update([
                    'param4'=> strval(date("Y-m-d"))
                ]);
                break;
            case 'rm_3.0':
                Historiquetrans::where('id',$request->id_trans)->update([
                    'id_grand_parent'=> 'rm_3.0 '.now()
                ]);
                Customer::find($request->id)->update([
                    'param4'=> 'rm_3.0'
                ]);
                break;
            case 'migration':
                Customer::find($request->id)->update([
                    'param5'=> strval(date("Y-m-d"))
                ]);
                break;
            case 'migrations':
                Customer::find($request->id)->update([
                    'param5'=>'migration '.strval(date("Y-m-d"))
                ]);
                break;
            case 'migrate':
                //Customer::where('id',$request->id)->update([
                //'confirmation_sent_at'=> now()
                //]);
                break;
            case 'international':
                Customer::where('id',$request->id)->update([
                    'phonenumber_assoc_3'=> 'international '.now()
                ]);
                break;
            case 'new':
                Customer::where('id',$request->id)->update([
                    'phonenumber_assoc_3'=> now()
                ]);
                break;
            case 'internationalTrans':
                Historiquetrans::where('id_customer',$request->id)->update([
                    'id_grand_parent'=> 'international '.now()
                ]);
                Customer::where('id',$request->id)->update([
                    'phonenumber_assoc_3'=> 'international '.now()
                ]);

                break;
        }
        return 1;
    }

    public function clientApp(Request $request){
        $uri = $request->path();
        return view('client', compact('uri'));
    }

    public function updatedWhatsApp(Request$request){
        Customer::where('id',$request->id)->update([
            'whatsapp'=> $request->phone
        ]);
        return '1';
    }

    public function getNotifNotRead(Request$request){
        $notifications = count(Notif::where('receiver', $request->receiver)->where('notified', 0)->get());
        return response()->json([
            'statut'=> true,
            'notif'=> $notifications,
        ]);
    }

    public function readNotif(Request$request){
        $notifications = Notif::where('receiver', $request->receiver)->where('notified', 0)->get();
        foreach ($notifications as $notif){
            Notif::where('id',$notif['id'])->update([
                'notified'=> 1
            ]);
        }
        return response()->json([
            'statut'=> true,
        ]);

    }


    // Reclamatios apis
    public function getReclamation(Request $request){
        $reclamatios = Reclammation::where('phoneclient', $request->phone)->orderBy('created_at', 'desc')->get();

        if(!empty($reclamatios)){

            if(count($reclamatios)>0){
                return response()->json([
                    'statut'=> true,
                    'body'=> $reclamatios,
                ]);
            }else{
                return response()->json([
                    'statut'=> false,
                    'body'=> 0,
                    'message'=> $reclamatios
                ]);
            }

        }else{
            return response()->json([
                'statut'=> false,
                'body'=> 0,
                'message'=> $reclamatios
            ]);
        }
    }

    public function storReclamaion(Request $request){

        $getReclamation = Reclammation::where('type_reclammation',$request->type)->where('operation',$request->operation)->where('phonebeneficiaire',$request->phoneReceiver)->where('param4',$request->montant)->first();
        if($getReclamation){
            return response()->json([
                'statut'=> true,
                'body'=> $getReclamation,
            ]);
        }

        $reclamation = new Reclammation([
            'type_reclammation' => $request->type,
            'objet' => $request->objet,
            'operation'=> $request->operation,
            'phoneclient'=> $request->phone,
            'phonebeneficiaire'=> $request->phoneReceiver,
            'message' => $request->message,
            'piece_jointe'=> $request->piece,
            'statut'=> 'new',
            'param4'=> $request->montant,
            'preuve_file' =>$request->preuvefile,
        ]);
        $reclamation->save();
        if(empty($reclamation->id)){
            return response()->json([
                'statut'=> true,
                'body'=> $reclamation,
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'body'=> $reclamation,
            ]);
        }

    }

    public function likeReclamation(Request $request){
        $reclamation = Reclammation::where('id',$request->id)->update([
            'param1'=>$request->liked,
            'param2'=>$request->comment
        ]);

        if($reclamation){
            return response()->json([
                'statut'=> true,
                'body'=> $reclamation,
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'body'=> $reclamation,
            ]);
        }

    }

    public function datasApp(){
        $contry = [
            ["name"=> "Afghanistan", "code"=> "AF"],
            ["name"=> "Åland Islands", "code"=> "AX"],
            ["name"=> "Albania", "code"=> "AL"],
            ["name"=> "Algeria", "code"=> "DZ"],
            ["name"=> "American Samoa", "code"=> "AS"],
            ["name"=> "AndorrA", "code"=> "AD"],
            ["name"=> "Angola", "code"=> "AO"],
            ["name"=> "Anguilla", "code"=> "AI"],
            ["name"=> "Antarctica", "code"=> "AQ"],
            ["name"=> "Antigua and Barbuda", "code"=> "AG"],
            ["name"=> "Argentina", "code"=> "AR"],
            ["name"=> "Armenia", "code"=> "AM"],
            ["name"=> "Aruba", "code"=> "AW"],
            ["name"=> "Australia", "code"=> "AU"],
            ["name"=> "Austria", "code"=> "AT"],
            ["name"=> "Azerbaijan", "code"=> "AZ"],
            ["name"=> "Bahamas", "code"=> "BS"],
            ["name"=> "Bahrain", "code"=> "BH"],
            ["name"=> "Bangladesh", "code"=> "BD"],
            ["name"=> "Barbados", "code"=> "BB"],
            ["name"=> "Belarus", "code"=> "BY"],
            ["name"=> "Belgium", "code"=> "BE"],
            ["name"=> "Belize", "code"=> "BZ"],
            ["name"=> "Benin", "code"=> "BJ"],
            ["name"=> "Bermuda", "code"=> "BM"],
            ["name"=> "Bhutan", "code"=> "BT"],
            ["name"=> "Bolivia", "code"=> "BO"],
            ["name"=> "Bosnia and Herzegovina", "code"=> "BA"],
            ["name"=> "Botswana", "code"=> "BW"],
            ["name"=> "Bouvet Island", "code"=> "BV"],
            ["name"=> "Brazil", "code"=> "BR"],
            ["name"=> "British Indian Ocean Territory", "code"=> "IO"],
            ["name"=> "Brunei Darussalam", "code"=> "BN"],
            ["name"=> "Bulgaria", "code"=> "BG"],
            ["name"=> "Burkina Faso", "code"=> "BF"],
            ["name"=> "Burundi", "code"=> "BI"],
            ["name"=> "Cambodia", "code"=> "KH"],
            ["name"=> "Cameroon", "code"=> "CM"],
            ["name"=> "Canada", "code"=> "CA"],
            ["name"=> "Cape Verde", "code"=> "CV"],
            ["name"=> "Cayman Islands", "code"=> "KY"],
            ["name"=> "Central African Republic", "code"=> "CF"],
            ["name"=> "Chad", "code"=> "TD"],
            ["name"=> "Chile", "code"=> "CL"],
            ["name"=> "China", "code"=> "CN"],
            ["name"=> "Christmas Island", "code"=> "CX"],
            ["name"=> "Cocos (Keeling) Islands", "code"=> "CC"],
            ["name"=> "Colombia", "code"=> "CO"],
            ["name"=> "Comoros", "code"=> "KM"],
            ["name"=> "Congo", "code"=> "CG"],
            ["name"=> "Congo, The Democratic Republic of the", "code"=> "CD"],
            ["name"=> "Cook Islands", "code"=> "CK"],
            ["name"=> "Costa Rica", "code"=> "CR"],
            ["name"=> "Croatia", "code"=> "HR"],
            ["name"=> "Cuba", "code"=> "CU"],
            ["name"=> "Cyprus", "code"=> "CY"],
            ["name"=> "Czech Republic", "code"=> "CZ"],
            ["name"=> "Denmark", "code"=> "DK"],
            ["name"=> "Djibouti", "code"=> "DJ"],
            ["name"=> "Dominica", "code"=> "DM"],
            ["name"=> "Dominican Republic", "code"=> "DO"],
            ["name"=> "Ecuador", "code"=> "EC"],
            ["name"=> "Egypt", "code"=> "EG"],
            ["name"=> "El Salvador", "code"=> "SV"],
            ["name"=> "Equatorial Guinea", "code"=> "GQ"],
            ["name"=> "Eritrea", "code"=> "ER"],
            ["name"=> "Estonia", "code"=> "EE"],
            ["name"=> "Ethiopia", "code"=> "ET"],
            ["name"=> "Falkland Islands (Malvinas)", "code"=> "FK"],
            ["name"=> "Faroe Islands", "code"=> "FO"],
            ["name"=> "Fiji", "code"=> "FJ"],
            ["name"=> "Finland", "code"=> "FI"],
            ["name"=> "France", "code"=> "FR"],
            ["name"=> "French Guiana", "code"=> "GF"],
            ["name"=> "French Polynesia", "code"=> "PF"],
            ["name"=> "French Southern Territories", "code"=> "TF"],
            ["name"=> "Gabon", "code"=> "GA"],
            ["name"=> "Gambia", "code"=> "GM"],
            ["name"=> "Georgia", "code"=> "GE"],
            ["name"=> "Germany", "code"=> "DE"],
            ["name"=> "Ghana", "code"=> "GH"],
            ["name"=> "Gibraltar", "code"=> "GI"],
            ["name"=> "Greece", "code"=> "GR"],
            ["name"=> "Greenland", "code"=> "GL"],
            ["name"=> "Grenada", "code"=> "GD"],
            ["name"=> "Guadeloupe", "code"=> "GP"],
            ["name"=> "Guam", "code"=> "GU"],
            ["name"=> "Guatemala", "code"=> "GT"],
            ["name"=> "Guernsey", "code"=> "GG"],
            ["name"=> "Guinea", "code"=> "GN"],
            ["name"=> "Guinea-Bissau", "code"=> "GW"],
            ["name"=> "Guyana", "code"=> "GY"],
            ["name"=> "Haiti", "code"=> "HT"],
            ["name"=> "Heard Island and Mcdonald Islands", "code"=> "HM"],
            ["name"=> "Holy See (Vatican City State)", "code"=> "VA"],
            ["name"=> "Honduras", "code"=> "HN"],
            ["name"=> "Hong Kong", "code"=> "HK"],
            ["name"=> "Hungary", "code"=> "HU"],
            ["name"=> "Iceland", "code"=> "IS"],
            ["name"=> "India", "code"=> "IN"],
            ["name"=> "Indonesia", "code"=> "ID"],
            ["name"=> "Iran, Islamic Republic Of", "code"=> "IR"],
            ["name"=> "Iraq", "code"=> "IQ"],
            ["name"=> "Ireland", "code"=> "IE"],
            ["name"=> "Isle of Man", "code"=> "IM"],
            ["name"=> "Israel", "code"=> "IL"],
            ["name"=> "Italy", "code"=> "IT"],
            ["name"=> "Jamaica", "code"=> "JM"],
            ["name"=> "Japan", "code"=> "JP"],
            ["name"=> "Jersey", "code"=> "JE"],
            ["name"=> "Jordan", "code"=> "JO"],
            ["name"=> "Kazakhstan", "code"=> "KZ"],
            ["name"=> "Kenya", "code"=> "KE"],
            ["name"=> "Kiribati", "code"=> "KI"],
            ["name"=> "Korea, Republic of", "code"=> "KR"],
            ["name"=> "Kuwait", "code"=> "KW"],
            ["name"=> "Kyrgyzstan", "code"=> "KG"],
            ["name"=> "Latvia", "code"=> "LV"],
            ["name"=> "Lebanon", "code"=> "LB"],
            ["name"=> "Lesotho", "code"=> "LS"],
            ["name"=> "Liberia", "code"=> "LR"],
            ["name"=> "Libyan Arab Jamahiriya", "code"=> "LY"],
            ["name"=> "Liechtenstein", "code"=> "LI"],
            ["name"=> "Lithuania", "code"=> "LT"],
            ["name"=> "Luxembourg", "code"=> "LU"],
            ["name"=> "Macao", "code"=> "MO"],
            ["name"=> "Macedonia, The Former Yugoslav Republic of", "code"=> "MK"],
            ["name"=> "Madagascar", "code"=> "MG"],
            ["name"=> "Malawi", "code"=> "MW"],
            ["name"=> "Malaysia", "code"=> "MY"],
            ["name"=> "Maldives", "code"=> "MV"],
            ["name"=> "Mali", "code"=> "ML"],
            ["name"=> "Malta", "code"=> "MT"],
            ["name"=> "Marshall Islands", "code"=> "MH"],
            ["name"=> "Martinique", "code"=> "MQ"],
            ["name"=> "Mauritania", "code"=> "MR"],
            ["name"=> "Mauritius", "code"=> "MU"],
            ["name"=> "Mayotte", "code"=> "YT"],
            ["name"=> "Mexico", "code"=> "MX"],
            ["name"=> "Micronesia, Federated States of", "code"=> "FM"],
            ["name"=> "Moldova, Republic of", "code"=> "MD"],
            ["name"=> "Monaco", "code"=> "MC"],
            ["name"=> "Mongolia", "code"=> "MN"],
            ["name"=> "Montserrat", "code"=> "MS"],
            ["name"=> "Morocco", "code"=> "MA"],
            ["name"=> "Mozambique", "code"=> "MZ"],
            ["name"=> "Myanmar", "code"=> "MM"],
            ["name"=> "Namibia", "code"=> "NA"],
            ["name"=> "Nauru", "code"=> "NR"],
            ["name"=> "Nepal", "code"=> "NP"],
            ["name"=> "Netherlands", "code"=> "NL"],
            ["name"=> "Netherlands Antilles", "code"=> "AN"],
            ["name"=> "New Caledonia", "code"=> "NC"],
            ["name"=> "New Zealand", "code"=> "NZ"],
            ["name"=> "Nicaragua", "code"=> "NI"],
            ["name"=> "Niger", "code"=> "NE"],
            ["name"=> "Nigeria", "code"=> "NG"],
            ["name"=> "Niue", "code"=> "NU"],
            ["name"=> "Norfolk Island", "code"=> "NF"],
            ["name"=> "Northern Mariana Islands", "code"=> "MP"],
            ["name"=> "Norway", "code"=> "NO"],
            ["name"=> "Oman", "code"=> "OM"],
            ["name"=> "Pakistan", "code"=> "PK"],
            ["name"=> "Palau", "code"=> "PW"],
            ["name"=> "Palestinian Territory, Occupied", "code"=> "PS"],
            ["name"=> "Panama", "code"=> "PA"],
            ["name"=> "Papua New Guinea", "code"=> "PG"],
            ["name"=> "Paraguay", "code"=> "PY"],
            ["name"=> "Peru", "code"=> "PE"],
            ["name"=> "Philippines", "code"=> "PH"],
            ["name"=> "Pitcairn", "code"=> "PN"],
            ["name"=> "Poland", "code"=> "PL"],
            ["name"=> "Portugal", "code"=> "PT"],
            ["name"=> "Puerto Rico", "code"=> "PR"],
            ["name"=> "Qatar", "code"=> "QA"],
            ["name"=> "Reunion", "code"=> "RE"],
            ["name"=> "Romania", "code"=> "RO"],
            ["name"=> "Russian Federation", "code"=> "RU"],
            ["name"=> "RWANDA", "code"=> "RW"],
            ["name"=> "Saint Helena", "code"=> "SH"],
            ["name"=> "Saint Kitts and Nevis", "code"=> "KN"],
            ["name"=> "Saint Lucia", "code"=> "LC"],
            ["name"=> "Saint Pierre and Miquelon", "code"=> "PM"],
            ["name"=> "Saint Vincent and the Grenadines", "code"=> "VC"],
            ["name"=> "Samoa", "code"=> "WS"],
            ["name"=> "San Marino", "code"=> "SM"],
            ["name"=> "Sao Tome and Principe", "code"=> "ST"],
            ["name"=> "Saudi Arabia", "code"=> "SA"],
            ["name"=> "Senegal", "code"=> "SN"],
            ["name"=> "Serbia and Montenegro", "code"=> "CS"],
            ["name"=> "Seychelles", "code"=> "SC"],
            ["name"=> "Sierra Leone", "code"=> "SL"],
            ["name"=> "Singapore", "code"=> "SG"],
            ["name"=> "Slovakia", "code"=> "SK"],
            ["name"=> "Slovenia", "code"=> "SI"],
            ["name"=> "Solomon Islands", "code"=> "SB"],
            ["name"=> "Somalia", "code"=> "SO"],
            ["name"=> "South Africa", "code"=> "ZA"],
            ["name"=> "South Georgia and the South Sandwich Islands", "code"=> "GS"],
            ["name"=> "Spain", "code"=> "ES"],
            ["name"=> "Sri Lanka", "code"=> "LK"],
            ["name"=> "Sudan", "code"=> "SD"],
            ["name"=> "Suriname", "code"=> "SR"],
            ["name"=> "Svalbard and Jan Mayen", "code"=> "SJ"],
            ["name"=> "Swaziland", "code"=> "SZ"],
            ["name"=> "Sweden", "code"=> "SE"],
            ["name"=> "Switzerland", "code"=> "CH"],
            ["name"=> "Syrian Arab Republic", "code"=> "SY"],
            ["name"=> "Taiwan, Province of China", "code"=> "TW"],
            ["name"=> "Tajikistan", "code"=> "TJ"],
            ["name"=> "Tanzania, United Republic of", "code"=> "TZ"],
            ["name"=> "Thailand", "code"=> "TH"],
            ["name"=> "Timor-Leste", "code"=> "TL"],
            ["name"=> "Togo", "code"=> "TG"],
            ["name"=> "Tokelau", "code"=> "TK"],
            ["name"=> "Tonga", "code"=> "TO"],
            ["name"=> "Trinidad and Tobago", "code"=> "TT"],
            ["name"=> "Tunisia", "code"=> "TN"],
            ["name"=> "Turkey", "code"=> "TR"],
            ["name"=> "Turkmenistan", "code"=> "TM"],
            ["name"=> "Turks and Caicos Islands", "code"=> "TC"],
            ["name"=> "Tuvalu", "code"=> "TV"],
            ["name"=> "Uganda", "code"=> "UG"],
            ["name"=> "Ukraine", "code"=> "UA"],
            ["name"=> "United Arab Emirates", "code"=> "AE"],
            ["name"=> "United Kingdom", "code"=> "GB"],
            ["name"=> "United States", "code"=> "US"],
            ["name"=> "United States Minor Outlying Islands", "code"=> "UM"],
            ["name"=> "Uruguay", "code"=> "UY"],
            ["name"=> "Uzbekistan", "code"=> "UZ"],
            ["name"=> "Vanuatu", "code"=> "VU"],
            ["name"=> "Venezuela", "code"=> "VE"],
            ["name"=> "Viet Nam", "code"=> "VN"],
            ["name"=> "Virgin Islands, British", "code"=> "VG"],
            ["name"=> "Virgin Islands, U.S.", "code"=> "VI"],
            ["name"=> "Wallis and Futuna", "code"=> "WF"],
            ["name"=> "Western Sahara", "code"=> "EH"],
            ["name"=> "Yemen", "code"=> "YE"],
            ["name"=> "Zambia", "code"=> "ZM"],
            ["name"=> "Zimbabw", "code"=> "ZN"]
        ];

        $city = [
            ["name"=> "Akanda"],
            ["name"=> "Abidjan"],
            ["name"=> "Bitam"],
            ["name"=> "Booué"],
            ["name"=> "Cocobeach"],
            ["name"=> "Dakar"],
            ["name"=> "Douala"],
            ["name"=> "Brazaville"],
            ["name"=> "New York"],
            ["name"=> "Akanda"],
            ["name"=> "Fougamou"],
            ["name"=> "Franceville"],
            ["name"=> "Gamba"],
            ["name"=> "Kango"],
            ["name"=> "Koulamoutou"],
            ["name"=> "Lambaréné"],
            ["name"=> "Lastoursville"],
            ["name"=> "Libreville"],
            ["name"=> "Lékoni"],
            ["name"=> "Makokou"],
            ["name"=> "Mayumba"],
            ["name"=> "Mbigou"],
            ["name"=> "Medouneu"],
            ["name"=> "Minvoul"],
            ["name"=> "Mimongo"],
            ["name"=> "Mitzic"],
            ["name"=> "Moanda"],
            ["name"=> "Mouila"],
            ["name"=> "Mounana"],
            ["name"=> "Mayumba"],
            ["name"=> "Ndendé"],
            ["name"=> "Ndjolé"],
            ["name"=> "Nkan"],
            ["name"=> "Ntoum"],
            ["name"=> "Okondja"],
            ["name"=> "Omboué"],
            ["name"=> "Owendo"],
            ["name"=> "Oyem"],
            ["name"=> "Port-Gentil"],
            ["name"=> "Paris"],
            ["name"=> "Tchibanga"],
            ["name"=> "Omboué"],
            ["name"=> "Tsogni"],
            ["name"=> "Yaoundé"],
            ["name"=> "Tunis"],
            ["name"=> "Barcelone"],
            ["name"=> "Madrid"],
            ["name"=> "Marseille"],
        ];

        return [
            'contry'=> $contry,
            'city'=>$city
        ];
    }

    public function sendSondage(Request $request){
        Reclammation::where('id', $request->id)->update([
            'comment'=>$request->sondage,
            'avis'=>$request->avis
        ]);
        return true;
    }

    public function remove(Request $request){
        $customerVeri = Customer::where('id',$request->id )->where('otp',$request->code)->first();
        if($customerVeri){
            Customer::where('id', $customerVeri->id)->delete();
            return 1;
        }else{
            return 0;
        }
    }


    public function resendSms(Request $request){
        $numNotif= $request->phone;
        $messageNotif = $request->message;
        // On envoie le code par notification et par sms au propiétaire
        Http::get("http://gampay.app/gamclients/public/api/sendNotificationget?phonevendeur=$numNotif&operation=info&message=$messageNotif&numclient=$numNotif");
        Http::get("http://gampay.app/gamclients/public/api/sendSmsGet?phone=$numNotif&message=$messageNotif");
        return 1;
    }

    public function removecompte(){
        return view('removeAccount');
    }

    public function getDataForfait(Request $request){
        $forfaitInternational = ForfaitInternational::orderby('id', 'desc')->get();
        $deviceInternationalRequest = DeviceInternal::all();
        $defaultUser = Customer::where('option2','99999')->get();
        $agences =  [];
        $parrain = Customer::where('id',$request->parrain)->first();
        $nomMobile = [];
        if($request->type == 'partenaire'){
            $nomMobile = DB::select("select * from customer_complement where nom is not null and nom not like '%trer PIN%' and nom <> '' and deleted_at is null");
        }
        if($parrain){
            $dataParrain = $parrain;
        }else{
            $dataParrain = 0;
        }
        $info = Comment::where('id', 223)->first();
        //$tabCode = ['jleo', 'campagnard2', 'Jleo','dragage' ,'frais'];
        $tabCode = ['frais'];
        $tabWifi = ['day' =>'500', 'week' =>'2000', 'mounth'=>'5000'];

        $menus = Menu::all();
        $payments = Payment::all();

        return response()->json([
            'nomMobile' => $nomMobile,
            'forfaitsInternationaux'=> $forfaitInternational,
            'devicesInternationaux'=>$deviceInternationalRequest,
            'defaultUser'=> $defaultUser,
            'dataParrain'=> $dataParrain,
            'info'=> $info,
            'link'=> 'https://localhost:7295',
            'am'=> '*150*3*10*746*',
            'moov'=> '*555*5*7*746*',
            'travelAgency'=>$agences,
            'codePrixImport'=>$tabCode,
            'priceWifi'=>$tabWifi,
            'menus'=>$menus,
            'payments'=>$payments,
            'priceEsDubai'=> $this->ProgramPaieESdUBAI(),
            'linkApp' => $this->linkApp(),
            'houses' => $this->houses(),
            'voyageTarif' => $this->voyageTarif(),
            'price_sim'=>'5000'
        ]);
    }

    public function getInfoTransfert(Request $request){
        try {
            $numero = $request->phone;
            if (strlen($request->phone) == 8) {
                $numero = '0'.$request->phone;
            }
            $name = "";

            if($request->type == 'rm'){
                $info = CustomerComplement::where('phoneclient','like',"$request->phone%")->first();
            }else{
                $debut = $numero[0].''.$numero[1];
                if(in_array($debut, ['06'])){
                    $info = Http::get("https://gampay.org/clients/public/api/getInfoCustomerPvit?phone=$numero")->body();
                    $name = $info;
                }else{
                    $info = CustomerComplement::whereIn('phoneclient', [$request->phone, '0'.$request->phone])->first();
                }
            }
            if($info){
                if(!empty($info->nom) && !str_contains($info->nom, 'trer PIN') && $info->nom!=''){
                    return response()->json([
                        'statut'=>true,
                        'body'=> $info->nom,
                        'phone'=> $info->phoneclient,
                        'id'=> $info->id

                    ]);
                }elseif(!empty($name)){
                    return response()->json([
                        'statut'=>true,
                        'body'=> $name,
                        'phone'=> $numero

                    ]);
                }else{
                    return response()->json([
                        'statut'=>true,
                        'body'=> 'Inconnu',
                        'phone'=> $info->phoneclient
                    ]);
                }
            }else{
                if($request->type != 'rm' && !empty($request->phone) && !str_contains( strval($request->phone), '+')){
                    $newInfo = new CustomerComplement([
                        'id_customer'=>12354,
                        'phoneclient'=> $request->phone
                    ]);
                    $newInfo->save();
                }else{
                    return response()->json([
                        'statut'=>true,
                        'body'=> '',
                        'phone'=> ''
                    ]);
                }
                return response()->json([
                    'statut'=>false,
                ]);
            }

        }catch (\Exception $e){
            return 0;
        }
    }


    public function getInfoNumberIn($num, $num1, $num2, $type){
        $info = CustomerComplement::whereIn('phoneclient', [$num, $num1, $num2, '0'.$num])->first();
        if($info){
            if(!empty($info->nom) && !str_contains($info->nom, 'entrer PIN') && $info->nom!=''){
                if($type != ''){
                    $customer = Customer::where('phoneclient', $info->phoneclient)->first();
                    if($customer){
                        if(in_array($customer->nom, ['GAM', 'client', 'XXXX', null]) || in_array($customer->prenom, ['GAM', 'client', 'XXXX', null])
                        ){
                            Customer::where('id', $customer->id)->update([
                                'nom' => $info->nom,
                                'prenom'=> ','
                            ]);
                        }
                    }
                }
                return [$info->nom,$info->phoneclient];
            }else{
                return['',$info->phoneclient];
            }
        }else{
            if(!empty($num) && !str_contains( strval($num), '+')){
                $newInfo = new CustomerComplement([
                    'id_customer'=>12354,
                    'phoneclient'=> $num
                ]);
                $newInfo->save();
            }

            return [ '', ''];
        }
    }

    public function sendNotificationInprogram($phonevendeur, $numclient,$operation,$montant, $messageNotif, $contentHist, $challenge){

        $customerVendeurs = Customer::where('phoneclient',$phonevendeur)->get();
        $customerReceives = Customer::whereIn('phoneclient',['0'.$numclient,$numclient])->take(1)->get();

        if($customerVendeurs){
            foreach ($customerVendeurs as $customer){
                if(!empty($challenge)){
                    Customer::find($customer['id'])->update([
                        'locked_at'=> date("Y-m-d")
                    ]);
                }
                $tokenVendeur = $messageNotif == "Connexion valide"? $challenge : $customer['unlock_token'];
                $nomVendeur= $customer['prenom'].' '. $customer['nom'];
                $appVendeur = $customer['avatar'];
            }
        }else{
            $tokenVendeur = 0;
            $nomVendeur= 0;
            $appVendeur = '';
        }

        if($customerReceives){
            foreach ($customerReceives as $customer){
                $tokenReceive = $messageNotif == "Connexion valide"? $challenge : $customer['unlock_token'];
                $nomReceive= $customer['prenom'].' '. $customer['nom'];
                $pass =  $customer['mdpclient'];
                $appReceive = $customer['avatar'];
            }
        }else{
            $tokenReceive = 0;
            $nomReceive= 0;
            $pass =  0;
            $appReceive = '';
        }


        if($phonevendeur == $numclient || $phonevendeur == '0'.$numclient ){
            $nom = "";
            $nom2 = "";
            $nomVisa = "";
        }else{
            if($nomVendeur!=""){
                $nom = "de  $nomVendeur";
                $nomVisa = "par $nomVendeur";
            }else{
                $nom = "du ".$phonevendeur;
                $nomVisa = "par le $nomVendeur";
            }

            if($nomReceive!=""){
                $nom2 = " pour $nomReceive ";
            }else{
                $nom2 = " pour le $numclient ";
            }
        }

        switch ($operation){
            case "achat_credit":
                $titre = 'Achat crédit';
                $message= "Vous avez achetez $montant de crédit $nom2" ;
                $message2= "Vous avez reçu $montant FCFA de crédit $nom" ;
                break;
            case "achat_forfait" :
                $titre = 'Achat forfait';
                $message= "Vous avez achetez un forfait internet de $contentHist Mo$nom2" ;
                $message2= "Vous avez reçu un forfait internet de $contentHist Mo $nom" ;

                break;
            case "achat_unites_electrique":
                $titre = 'Achat edan';
                $message= "Vous avez achetez des unité edan de $montant FCFA pour le compteur $numclient" ;
                $message2= "vous avez reçu des unité edan de $montant FCFA pour le compteur $numclient $nom" ;

                break;
            case "recharge_visa_uba":
                $titre = 'Recharge visa';
                $message= "Votre recharge visa de $montant FCFA$nom2 à été réalisé avec succès.";
                $message2= "Votre carte visa a été rechargée de $montant FCFA $nomVisa" ;
                break;

            case "achat_visa_uba":
                $titre = 'Achat visa';
                $message= "Le paiement de votre carte visa a été réaliée avec succès";
                $message2= "Le paiement de votre carte visa a été réaliée avec succès" ;

                break;

            case "gam_transfert":
                $titre = 'Transfert';
                $message= "Vous avez éffectuez un transfert de $montant FCFA $nom2" ;
                $message2= "vous avez reçu $montant FCFA $nom" ;
                break;

            case "achat_status":
                $titre = 'Publication status';
                $message= "Le paiement de votre publication a été effectué avec succès" ;
                $message2= "Le paiement de votre publication a été effectué avec succès" ;
                break;

            case "rendu_monnaie_simple":
                $titre = 'Rendu monnaie';
                $message= "Vous avez éffectuez un rendu monnaie de $montant FCFA $nom2." ;
                $message2= "Vous avez reçu un rendu monnaie de $montant FCFA $nom" ;
                break;

            case "recharge_compte_gam":
                $titre = 'Recharge GamPay';
                $message= "Votre recharge GamPay de $montant FCFA$nom2 a été effectuée avec succès" ;
                $message2= "Votre compte GamPay a été rechargé de $montant FCFA $nomVisa " ;
                break;

            case "mdp":

                $titre = 'Récupération';
                $message2= "Votre mot de passe est $pass , $nomReceive" ;
                $message= "Votre mot de passe est $pass , $nomReceive" ;
                break;

            case "info":

                $titre = 'GamPay';
                $message= $messageNotif ;
                $message2= $messageNotif ;
                break;

            case "incident":
                $titre = 'Incident GamPay';
                $message= $messageNotif ;
                $message2= $messageNotif ;
                break;

            case "rm_3.0":
                $titre = 'Rendu monnaie';
                $message= "Vous avez éffectuez un rendu monnaie de $montant FCFA $nom2."  ;
                $message2= "Vous venez de recevoir un rendu monnaie de $montant FCFA sur votre compte GamPay $nom. Recevez 1Go après une recharge visa sur votre application." ;
                break;

            default :
                $titre = $operation;
                $message= $messageNotif ;
                $message2= $messageNotif ;

        }

        if($phonevendeur == $numclient || $phonevendeur == '0'.$numclient || $operation == 'incident' ){
            $notif2= $this->notifFirebase($tokenVendeur,$titre, $message, $appVendeur);
            $this->myNotification($titre, $message,$operation, $phonevendeur,$nomReceive, $phonevendeur,$montant, "" , "", ""  );
            $rep = $notif2;
        }else{
            $notifrecieve = $this->notifFirebase($tokenReceive,$titre, $message2, $appReceive);
            $notif2= $this->notifFirebase($tokenVendeur,$titre, $message, $appVendeur);
            $this->myNotification($titre, $message2,$operation, $numclient,$nomReceive, $phonevendeur,$montant, "" , "", ""  );
            $this->myNotification($titre, $message,$operation, $phonevendeur,$nomReceive, $numclient,$montant, "" , "", ""  );
            if(intval($notifrecieve) == 1 || intval($notif2) == 1){
                $rep = 1;
            }else{
                $rep = 0;
            }
        }

        return $rep;

    }


    // créer une notification
    public function myNotification($titre, $message, $operation, $receiver, $nom, $demandeur, $montant,$pays, $codepays, $operateur){
        $newNotification = new Notif([
            'titre' => $titre,
            'content' => $message,
            'icon' => "",
            'type' => 'info',
            'operation' => $operation,
            'receiver' => $receiver,
            'nom' => $nom,
            'demandeur' => $demandeur,
            'montant' => $montant,
            'pays' => $pays,
            'codepays' => $codepays,
            'operateur' => $operateur,
        ]);
        $newNotification->save();
        return '1';
    }

    public function notifFirebase($token,$titre, $message, $app){
        if($app == 'ios' || $app == 'android'){
            $tokenKey = Http::get('http://ios.gampay.org/')->body();
            $link = "https://fcm.googleapis.com/v1/projects/app-new-gampay-ios/messages:send";
            $tokenKey2 = Http::get('http://api.gampay.org/')->body();
            $link2 = "https://fcm.googleapis.com/v1/projects/app-gampay/messages:send";
        }else{
            return 1;
        }

        if(!empty($token)){
            // 1er envoi
            $data =[
                "message"=>[
                    "token"=> $token,
                    "notification"=>[
                        "body"=>$message,
                        "title"=>$titre
                    ]
                ]
            ];
            $dataString = json_encode($data);

            $headers = [
                'Authorization: Bearer '.$tokenKey,
                'Content-Type: application/json',
            ];

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $link);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

            $response = curl_exec($ch);

            $result = json_decode($response, true);

            // 2eme envoi
            $data2 =[
                "message"=>[
                    "token"=> $token,
                    "notification"=>[
                        "body"=>$message,
                        "title"=>$titre
                    ]
                ]
            ];
            $dataString2 = json_encode($data2);

            $headers2 = [
                'Authorization: Bearer '.$tokenKey2,
                'Content-Type: application/json',
            ];

            $ch2 = curl_init();

            curl_setopt($ch, CURLOPT_URL, $link2);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString2);

            $response2 = curl_exec($ch2);

            return [$response2 , $response];

            if(!empty($result['name']) || !empty($result2['name'])){
                return 1;
            }else{
                return  0;
            }
        }else{
            return 10;
        }
    }

    public function countScoreCustomer(Request $request){

        Http::get('http://gampay.app/gamclients/public/api/recupIdCustomer');
        $trans = DB::select("select * from historiquetrans h where h.operation NOT in ('recharge_compte_gam', 'recieve_gam_transfert', 'emit_gam_transfert', 'gam_transfert', 'rendu_monnaie_simple','demande_solde', 'debit') and h.reference <> 'bonus' and (h.param5 is NULL or (h.param5 !='point' || h.param5 !='point_wallet_charge' || h.param5 !='point_wallet_no_charge')) and (h.param8 is NULL or h.param8 !='partenaire') and h.etat in ('CONFIRMEE', 'confirme') and h.id>=38336879 limit 10");
        if(count($trans)>0){
            foreach ($trans as $tran){
                $customer = Customer::where('phoneclient',$tran->phonevendeur)->first();
                if($customer){
                    /****************  make customer option and add score ******************/
                    $option = $tran->operation;
                    if($option == 'transfert_visa'){
                        $oldTransfert = Historiquetrans::where('phonevendeur', $tran->phonevendeur)->where('id', '<', 37582482)->where('operation', 'transfert_visa')->whereIn('etat', ['attend', 'en_agence_attend'])->get();
                        if(count($oldTransfert)==0){
                            $option = 'new_transfert_visa';
                        }
                    }elseif( $option == 'recharge_visa_uba' && strtotime($tran->created_at) > strtotime('2025-04-07 00:00:00')){
                        $option = 'recharge_visa_uba_dynamic';
                    }

                    if(empty($customer->option1)){
                        $newOption1 = $option;
                    }else{
                        if(str_contains($customer->option1, $option)){
                            $newOption1 = $customer->option1;
                        }else{
                            $newOption1 = $customer->option1 .'_'.$option;
                        }
                    }

                    Customer::where('id', $customer->id)->update([
                        'option1' => $newOption1,
                        'score'=>$customer->score != null? $customer->score + 1 : 1,
                        'last_transaction_at' =>  $tran->created_at,
                        'last_transaction_amount' => $tran->montant_sans_frais,
                        'last_transaction_operation' => $tran->operation,
                        'flp_point_caisse' => in_array($tran->operation, ['transfert_mobile','recharge_visa_uba', 'achat_wifi'])? 'service_client' : $customer->flp_point_caisse
                    ]);

                    $getDataTrans = '50';
                    if(!in_array($tran->reference, ['AUTO', '1', '2', '3', '4', '5', '6', '7', '8', '9'])){
                        $getDataTrans = $this->getDataTransConfirm($tran->reference);
                    }

                    Historiquetrans::where('id', $tran->id)->update([
                        'param5'=>'point',
                        'timestamps' =>'make_option',
                        'tentative_autorise' => $getDataTrans
                    ]);

                    $client2 = Client2::where('numclient',$tran->phonevendeur)->first();
                    if($client2){
                        Client2::where('num', $client2->num)->update([
                            //'score'=>$client2->score != null? $client2->score + 1 : 1,
                            'transaction_Date' => $tran->created_at,
                            'transaction_Amount' => $tran->montant_sans_frais,
                        ]);
                    }

                    /**************** End make customer option and add score ******************/

                    if(in_array($tran->operation, ['paiement_partenaire', 'debit']) ){
                        if($tran->operation == 'debit'){
                            $customer = Customer::where('phoneclient', $tran->numclient)->first();
                        }

                        $send = $this->sendInstallationTemplate($customer, $tran);
                        return [$send, $customer];

                    }
                }else{
                    Historiquetrans::where('id', $tran->id)->update([
                        'param5'=>'point',
                        'timestamps' =>'make_option',
                    ]);
                }

                /****************** Recharge Call time or Data Assistance *****************/
                if(in_array($tran->operation, ['achat_credit', 'achat_forfait', 'data_credit'])){
                    $qte_credit = null; $qte_data= null; $amount = 0;
                    $agent = new AgentController();
                    if($tran->operation == 'achat_credit'){
                        $amount = $tran->montant_sans_frais;
                    }elseif ($tran->operation == 'achat_forfait'){
                        $qte_data= $tran->content;
                    }else{
                        $qte_credit = $tran->param8;
                        $qte_data = $tran->param9;
                    }
                    $agent->rechargeCallTimeOrDataIn($tran->id, $tran->numclient, $tran->operation, $qte_credit, $qte_data, $amount);
                }
                /****************** End Recharge Call time or Data Assistance *****************/
                // calcul call time
                //Http::get("https://gampay.app/gamclients/public/api/calculTimeCallUssd?numeroclient=$tran->numeroclient&price=$tran->montant");

                Http::get("https://gampay.org/gamclients/public/api/getCountTraficCustomerTaxi?phonevendeur=$tran->phonevendeur&montant=$tran->frais");
                Http::get('https://gampay.org/gamclients/public/api/getAlerteNewCommandCardVisaUBAWait');
                Http::get('https://gampay.org/gamclients/public/api/getAlerteNewCommandCardVisaUBA');
            }
            $this->countScoreCustomerUssd();
            return [$trans, $request->customer];
        }else{
            $this->countScoreCustomerUssd();
            return [$trans, $request->customer];
        }
    }


    public function countScoreCustomerUssd(){
        $trans = DB::select("select * from gamrechargehist h where h.deleted_at is null and port = '6A' and h.etat like '%succes%' and (h.param4 !='point' or h.param4 is NULL) and id>=109163857 order by id desc limit 5");


        /*if(empty($request->trans)){
            $trans = DB::select("select * from gamrechargehist h where h.deleted_at is null and port = '6A' and h.etat like '%succes%' and h.param4 ='param4' and id>=106849997 order by id desc limit 5");
        }else{
            $trans = GamRechargeHist::where('id', $request->trans)->get();
        }*/

        if(count($trans)>0){
            foreach ($trans as $tran){
                $customer = Client2::where('numclient',$tran->phonevendeur)->first();
                if($customer){
                    $pack = false; // mise à jour le 16-09-2026
                    $product = 'ussd';
                    $value = $customer->score;
                    if(in_array($tran->montant,[1200,2200,5200,10200])){
                        $product = 'pack';
                        $pack = true;
                    }
                    GamRechargeHist::where('id', $tran->id)->update([
                        'param4' =>'point'
                    ]);

                    Client2::where('num', $customer->num)->update([
                        'score' => (empty($customer->score) || $customer->score == 0) ? 1 : $customer->score + 1, // mise à jour le 16-05-2025 à 12h32
                        'etat' => 'actif',
                        'transaction_ID' => empty($customer->transaction_ID) ? 'com1' : $customer->transaction_ID,
                        'transaction_Date' => date("Y-m-d H:i:s"),
                        'transaction_Amount' => $tran->montant,
                        'last_reference' => $tran->reference,
                        'last_amount_pack' => $pack == false ? $customer->last_amount_pack : $tran->montant, // mise à jour le 16/09/2026
                        'num_receveur' => $tran->numeroclient, // mise à jour le 16/09/2026
                        'last_trans_pack' => $pack == false ? $customer->last_trans_pack : date("Y-m-d H:i:s"), // mise à jour le 16/09/2026
                        'count_pack' => $pack == false ? $customer->count_pack : $customer->count_pack + 1 // mise à jour le 16/09/2026
                    ]);
                    Http::get("https://gampay.app/gamclients/public/api/countProgression?phone=$tran->phonevendeur&product=$product&value=$value&id=$customer->gampay");
                }

                $customerApp = customer::where('phoneclient', $tran->phonevendeur)->orWhere('phonenumber_assoc_1', $tran->phonevendeur)->first();

                if($customerApp){
                    if(empty($customerApp->option1)){
                        $newOption1 = 'crediUssd';
                    }else{
                        if(str_contains($customerApp->option1, 'crediUssd')){
                            $newOption1 = $customerApp->option1;
                        }else{
                            $newOption1 = $customerApp->option1 .'_crediUssd';
                        }
                    }

                    Customer::where('id', $customerApp->id)->update([
                        'option1' => $newOption1,
                        'last_transaction_at' => date('Y-m-d H:i:s'),
                        'last_transaction_amount' => $tran->montant,
                        'last_transaction_operation' => 'creditUssd',
                        'code_confirm' => empty($customerApp->code_confirm)? 'crediUssd' : $customerApp->code_confirm,
                    ]);
                    $this->addCommissionParrain($tran->montant, $customerApp->id, 'ussd');

                }else{
                    $accountAssoc = Customer::whereIn('phoneclient',['0'.$tran->numeroclient, $tran->numeroclient])->first();
                    if($accountAssoc){
                        if(empty($accountAssoc->option1)){
                            $newOption1 = 'crediUssd';
                        }else{
                            if(str_contains($accountAssoc->option1, 'crediUssd')){
                                $newOption1 = $accountAssoc->option1;
                            }else{
                                $newOption1 = $accountAssoc->option1 .'_crediUssd';
                            }
                        }

                        Customer::where('id', $accountAssoc->id)->update([
                            'option1' => $newOption1,
                            'last_transaction_at' => date('Y-m-d H:i:s'),
                            'last_transaction_amount' => $tran->montant,
                            'last_transaction_operation' => 'creditUssd',
                            'code_confirm' => empty($accountAssoc->code_confirm)? 'crediUssd' : $accountAssoc->code_confirm,
                        ]);

                        if(empty($accountAssoc->phonenumber_assoc_1)){
                            Customer::where('id',$accountAssoc->id)->update([
                                'phonenumber_assoc_1' => $tran->phonevendeur
                            ]);
                            $this->addCommissionParrain($tran->montant, $accountAssoc->id, 'ussd');
                        }
                    }
                }

                //$pronostic = $this->validePronostic($tran->phonevendeur);

                GamRechargeHist::where('id', $tran->id)->update([
                    'param4' =>'point'
                ]);

                // challenge
                Http::get("https://gampay.app/gamclients/public/api/participationTombola?numclientChallenge=$tran->numeroclient&phonevendeurChallenge=$tran->phonevendeur&montant=$tran->montant");
                // calcul call time
                Http::get("https://gampay.app/gamclients/public/api/calculTimeCallUssd?numeroclient=$tran->numeroclient&price=$tran->montant");
                //$this->sendTemplateCustomerUssd($tran, $customerApp);
                Http::get("https://gampay.org/gamclients/public/api/getCountTraficCustomerProvince?phonevendeur=$tran->phonevendeur&numeroclient=$tran->numeroclient&montant=$tran->montant");
                Http::get("https://gampay.org/gamclients/public/api/newGetCustomerComm?phonevendeur=$tran->phonevendeur&montant=$tran->montant&numeroclient=$tran->numeroclient");
                Http::get("https://gampay.org/gamclients/public/api/updateTransCustomerOrFilleulHabituel?phone=$tran->phonevendeur");
                Http::get("https://gampay.org/gamclients/public/api/getAlerteCustomerCallingByAgent?numclient=$tran->phonevendeur");
                Http::get("https://gampay.org/gamclients/public/api/getCustomerHabituelUssdMessageVisa?phonevendeur=$tran->phonevendeur&numeroclient=$tran->numeroclient&montant=$tran->montant");
                Http::get("https://gampay.org/gamclients/public/api/getCountTraficCustomerTaxi?phonevendeur=$tran->phonevendeur&montant=100");
                Http::get("https://gampay.org/gamclients/public/api/sendDataCustomerCallingByAgentAfterTrafic?phonevendeur=$tran->phonevendeur");
                Http::get("https://gampay.org/gamclients/public/api/getCountSendCodeNewParrainageCustomerUssd?phonevendeur=$tran->phonevendeur&montant=$tran->montant");

            }
        }
        return $trans;
    }


    public function countScoreCustomerUssdRecup(){
        $trans = DB::select("select * from gamrechargehist h where h.deleted_at is null and port = '6A' and h.etat like '%succes%' and (h.param4 !='pointRecup' or h.param4 is NULL) and id > 108855940 and id < 109001449 order by id ASC limit 25");

        if(count($trans)>0){
            foreach ($trans as $tran){
                $customer = Client2::where('numclient',$tran->phonevendeur)->first();
                if($customer){
                    GamRechargeHist::where('id', $tran->id)->update([
                        'param4' =>'pointRecup'
                    ]);

                    Client2::where('num', $customer->num)->update([
                        'score' => (empty($customer->score) || $customer->score == 0) ? 1 : $customer->score + 1, // mise à jour le 16-05-2025 à 12h32

                    ]);
                }
            }
        }else{
            $message = "Fin récup mise à jour client 2";
            Http::get("https://gampay.org/gamclients/public/api/sendWhatshappRequest?numero=24104071340&message=".$message);
        }
        return $trans;
    }


    public function validePronostic($phone)
    {
        $pronostic = Pronostic::where('phone', $phone)->where('status', 'waiting')->first();
        if ($pronostic) {
            Pronostic::where('phone', $pronostic->phone)->update([
                'status' => 'valide'
            ]);
        }
    }

    public function getInfoCustomerSuper(Request $request){
        $simVirtuelle=0;
        $simPhysique=0;
        $numSansZero = substr($request->phone, 1);
        $numeroZero = $request->phone;
        $customer = Customer::whereIn('phoneclient', [$numSansZero, $numeroZero])->first();
        if($customer){
            $trans =  Historiquetrans::where(function($q) use($numeroZero,$numSansZero) {
                $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero);
            })->orderBy('id', 'desc')->take(50)->get();
            $cards = Carte::where('id_customer', $customer->id)->get();
            $compteurs = Compteur::where('num_client', $customer->phoneclient)->get();
            $tickets = GamElectriciteHist::where('num_client',$customer->phoneclient)->whereNotIn('etat',['attend'])->orderBy('id', 'desc')->take(10)->get();
            $sims = SimInternal::where('customerId', $customer->id)->orWhere('CustomerNum',$customer->phoneclient)->get();
            if(count($sims)>0){
                foreach ($sims as $sim){
                    if($sim['type'] == 'v'){
                        $simVirtuelle = $sim;
                    }else{
                        $simPhysique = $sim;
                    }
                }
            }else{
                $sims = 0;
                $simPhysique = 0;
                $simVirtuelle = 0;
            }
            return response()->json([
                'statut'=> true,
                'simVirtuelle'=>$simVirtuelle,
                'simPhysique'=>$simPhysique,
                'customer'=> $customer,
                'cards' => count($cards)>0? $cards: $request->phoneNull ,
                'compteurs' => count($compteurs)>0? $compteurs: $request->phoneNull,
                'tickets' => count($tickets)>0? $tickets: $request->phoneNull,
                'sims' => $sims,
                'countTrans' => count($trans),
                'trans' => $trans,
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'message'=> "Le $request->phone ne correspond à aucun compte GamPay",
            ]);
        }
    }

    public function cguPiege(Request $request){
        $customer = $request->customer;
        return view('cgu', compact('customer'));
    }

    public function getInfocustomerComplement(Request $request){
        if(!empty($request->customer)){
            $customerVerif = Customer::where('id',intval($request->customer))->first();
            if($customerVerif){
                Customer::where('id',$customerVerif->id)->update([
                    //'confirmation_token' => $request->position,
                    'contry_code' =>  $request->drap,
                    'zone' =>  $request->pays.'/'.$request->ville
                ]);
            }
            return $customerVerif;
        }else{
            return $request->customer;
        }
    }

    public function ProgramPaieESdUBAI(){
        $Tarification = [
            ["periode"=> "1-4", "priceintens"=> "465","pricesemi"=>"275"],
            ["periode"=> "5-8", "priceintens"=> "435","pricesemi"=>"255"],
            ["periode"=> "9-12", "priceintens"=> "410","pricesemi"=>"240"],
            ["periode"=> "14-16", "priceintens"=> "390","pricesemi"=>"230"],
            ["periode"=> "17-20", "priceintens"=> "370","pricesemi"=>"210"],
            ["periode"=> "21-24", "priceintens"=> "350","pricesemi"=>"205"],
            ["periode"=> "25-30", "priceintens"=> "330","pricesemi"=>"195"],
            ["periode"=> "31-44", "priceintens"=> "320","pricesemi"=>"190"],

        ];

        return [
            'xaf'=> 622,
            'Tarification'=> $Tarification,
        ];
    }


    public function linkApp(){
        return [
            "videoEs"=>["type"=> "video", "link"=> "https://www.youtube.com/watch?v=g8iVmqNU0wM", "source"=>"youtube", "extension"=> ""],
            "offreEsD"=>["type"=> "image", "link"=> "http://gampay.org/files/offre.png", "source"=> "server", "extension"=> "png"],
            "general"=>["type"=> "image", "link"=> "http://gampay.org/files/generale.jpg", "source"=> "server", "extension"=> "jpg"],
            "ielts"=>["type"=> "image", "link"=> "http://gampay.org/files/ielts.jpg", "source"=> "server", "extension"=> "jpg"],
            "affaire"=>["type"=> "image", "link"=> "http://gampay.org/files/bussi.jpg", "source"=> "server", "extension"=> "jpg"],
            "english"=>["type"=> "image", "link"=> "http://gampay.org/files/english.jpg", "source"=> "server", "extension"=> "jpg"],
            "maxresdefault"=>["type"=> "image", "link"=> "http://gampay.org/files/maxresdefault.jpg", "source"=> "server", "extension"=> "jpg"],
        ];
    }

    /*public function houses(){
        return [
            // appartement
            ["id"=>1,"room"=> "Chambre simple avec salle de bains privative", "capacity"=> 1, "bed"=>1, "price"=> "205260", "type"=>"apparte"],
            ["id"=>2,"room"=> "Chambre Lits Jumeaux Partagée avec Salle de Bains Privative", "capacity"=> 2, "bed"=>2, "price"=> "102630", "type"=>"apparte"],
            ["id"=>3,"room"=> "Chambre partagée avec salle de bains privative en occupation double", "capacity"=>2, "bed"=>1, "price"=> "102630", "type"=>"apparte"],
            ["id"=>4,"room"=> "Appartement d’une chambre à coucher en occupation simple", "capacity"=> 1, "bed"=>1, "price"=> "342100", "type"=>"apparte"],
            ["id"=>5,"room"=> "Appartement d’une chambre à coucher en occupation double", "capacity"=> 1, "bed"=>1, "price"=> "171050", "type"=>"apparte"],

            // Hotel
            ["id"=>6,"room"=> "Chambre simple avec salle de bains privative", "capacity"=> 1, "bed"=>1, "price"=> "205260", "type"=>"hotel"],
            ["id"=>7,"room"=> "Chambre Lits Jumeaux Partagée avec Salle de Bains Privative", "capacity"=> 2, "bed"=>2, "price"=> "102630", "type"=>"hotel"],

             // Campus
            ["id"=>8,"room"=> "Chambre simple avec salle de bains privative", "capacity"=> 1, "bed"=>1, "price"=> "205260", "type"=>"campus"],
            ["id"=>9,"room"=> "Chambre Lits Jumeaux Partagée avec Salle de Bains Privative", "capacity"=> 2, "bed"=>2, "price"=> "102630", "type"=>"campus"],
        ];
    }*/

    public function houses(){
        return [
            // appartement
            ["id"=>1,"room"=> "Chambre simple avec salle de bains privative", "capacity"=> 1, "bed"=>1, "price"=> "205260", "type"=>"apparte","image"=>"http://gampay.org/files/appart-lit-simple.jpg"],
            ["id"=>2,"room"=> "Chambre Lits Jumeaux Partagée avec Salle de Bains Privative", "capacity"=> 2, "bed"=>2, "price"=> "102630", "type"=>"apparte","image"=>"http://gampay.org/files/appart-lit-jumeau2.jpg"],
            ["id"=>3,"room"=> "Chambre partagée avec salle de bains privative en occupation double", "capacity"=>2, "bed"=>1, "price"=> "102630", "type"=>"apparte","image"=>"http://gampay.org/files/hotellitsimple.jpg"],
            ["id"=>4,"room"=> "Appartement d’une chambre à coucher en occupation simple", "capacity"=> 1, "bed"=>1, "price"=> "342100", "type"=>"apparte","image"=>"http://gampay.org/files/hotellitsimple2.jpg"],
            ["id"=>5,"room"=> "Appartement d’une chambre à coucher en occupation double", "capacity"=> 1, "bed"=>1, "price"=> "171050", "type"=>"apparte","image"=>"http://gampay.org/files/apparte1.jpg"],
            // Hotel

            ["id"=>6,"room"=> "Chambre simple avec salle de bains privative", "capacity"=> 1, "bed"=>1, "price"=> "205260", "type"=>"hotel","image"=>"http://gampay.org/files/hotel1.jpg"],
            ["id"=>7,"room"=> "Chambre Lits Jumeaux Partagée avec Salle de Bains Privative", "capacity"=> 2, "bed"=>2, "price"=> "102630", "type"=>"hotel","image"=>"http://gampay.org/files/hotel2.jpg"],

            // Campus

            ["id"=>8,"room"=> "Chambre simple avec salle de bains privative", "capacity"=> 1, "bed"=>1, "price"=> "205260", "type"=>"campus","image"=>"http://gampay.org/files/single-room.jpg"],
            ["id"=>9,"room"=> "Chambre Lits Jumeaux Partagée avec Salle de Bains Privative", "capacity"=> 2, "bed"=>2, "price"=> "102630", "type"=>"campus","image"=>"http://gampay.org/files/single-room2.jpg"],
        ];
    }

    public function voyageTarif(){
        return [
            "alle_simple"=> "356200",
            "alle_retour"=> "549300",
        ];
    }

    public function imageListe(){
        return [
            // image principal de la page
            "offreEsD"=>"assets/offre.png",
            // image des cours
            "general_english"=>"assets/generale.jpg",
            "preparation_ielts"=>"assets/ielts.jpg",
            "AFFAIRES"=>"assets/bussi.jpg",
            "anglophone"=>"assets/english.jpg",
            "prononciation"=>"assets/maxresdefault.jpg",
        ];
    }

    public function addAgentGam(Request $request){
        $customer = Customer::where('phoneclient', $request->agent)->first();
        if($request->operation == 'add'){
            if($customer){
                if(empty($customer->phonenumber_assoc_1)){
                    Customer::where('id', $customer->id)->update([
                        'nom' => empty($customer->nom)? 'GAM':$customer->nom ,
                        'prenom'=> empty($customer->prenom)? 'Agent':$customer->prenom,
                        'type' => 'agent',
                        'customer_type' => 'agent',
                        'phonenumber_assoc_1' => $request->phone,
                        'phonenumber_assoc_2' => json_encode($request->produit),
                    ]);
                }else{
                    return response()->json([
                        'statut'=>false,
                        'message' => 'Cet utilisateur est dèjà un agent'
                    ]);
                }

            }else{
                $vendeur = Customer::where('phoneclient', $request->phone)->first();
                $agent = new Customer([
                    'nom' => 'GAM',
                    'prenom'=> 'Agent',
                    'phoneclient'=> $request->agent,
                    'mdpclient'=> '1234',
                    'pays'=> '241',
                    'option1'=> empty($vendeur)? 'gam':  $vendeur->option1,
                    'code_confirm' =>empty($vendeur)? 'gam': $vendeur->code_confirm,
                    'phonenumber_assoc_1' => $request->phone,
                    'phonenumber_assoc_2' => json_encode($request->produit),
                    'type' => 'agent',
                    'customer_type' => 'agent',
                    'status'=>1
                ]);
                $agent ->save();
            }

            return response()->json([
                'statut'=>true,
                'message' => 'Vous avez enregistré un nouvel agent'
            ]);
        }else{
            if($customer){
                Customer::where('id', $customer->id)->update([
                    'type' => 'agent',
                    'customer_type' => 'agent',
                    'phonenumber_assoc_1' => $request->phone,
                    'phonenumber_assoc_2' => json_encode($request->produit),
                ]);
                return response()->json([
                    'statut'=>true,
                    'message' => 'Opération effectuée'
                ]);
            }else{
                return response()->json([
                    'statut'=>false,
                    'message' => 'Informations incorrectes'
                ]);
            }
        }
    }

    public function getAgent(Request $request){

        $agent = Customer::where('phonenumber_assoc_1', $request->phone)->get();
        if(count($agent)){
            return response()->json([
                'statut'=>true,
                'body' => $agent
            ]);
        }else{
            return response()->json([
                'statut'=>false,
                'body' => 'Aucun agent affilié à votre compte'
            ]);
        }

    }

    public function confirmeDemandeSolde(Request $request){
        $demande = Historiquetrans::where('id', $request->id)->first();
        if($demande){
            $infoProduit = json_decode($demande->content, true);
            if( $request->etat == 'CONFIRMEE'){
                switch ($infoProduit['produit']) {
                    case 'trans_airtel':
                        $url = '*150*4*'.$infoProduit['numero'].'*'.$demande->montant.'*5811#';
                        break;
                    case 'credit_airtel':
                        $url = '*150*1*1*' + $infoProduit['numero'] + '#';
                        break;
                    case 'trans_moov':
                        $url = '*555*1*'.$infoProduit['numero'].'*'.$demande->montant.'*5811#';
                        break;
                    case 'credit_libertis':
                        $url = '*555*1*1#';
                        break;
                    default:
                }
            }
            Historiquetrans::where('id', $demande->id)->update([
                'etat'=> $request->etat
            ]);
            return response()->json([
                'statut'=>true,
                'code' => $url
            ]);

        }
    }

    public function retireAgent(Request $request){
        $agent = Customer::where('id', $request->id)->first();
        if($agent){
            Customer::where('id', $agent->id)->update([
                'type' => $request->idNull,
                'customer_type' => 'lambda',
                'phonenumber_assoc_1' => $request->idNull,
                'phonenumber_assoc_2' => $request->idNull,
            ]);
        }
    }

    public function addDataAnalyser (Request $request){
        $verifDevice = Customer::where('Fonction3', $request->device)->whereNotIn('id', [$request->id])->get();
        $customer =  Customer::where('id',$request->id)->first();
        if(count($verifDevice)==0){

            if(!empty($request->myDevice) && $request->myDevice != 'ko' && empty($customer->my_device)){
                $myDeviceId =  $request->myDevice;
            }else{
                $myDeviceId = $customer->my_device;
            }

            Customer::where('id',$request->id)->update([
                'Fonction3' => $request->device,
                'param8' =>!empty($request->phone)? $request->phone : $customer->param8,
                //'my_device' => $myDeviceId
            ]);
            Device::where('id_customer', $request->id)->update(['status' => 0]);
            $seaechDevice = Device::where('id_customer', $request->id)->where('id_device', $request->device)->first();
            if($seaechDevice){
                Device::where('id', $seaechDevice->id)->update(['status' => 1]);
            }else{
                $myDevice= new Device([// on enregistre le phone collecté
                    'marque'=> $request->phone,
                    'id_device' => $request->device,
                    'id_customer' =>$request->id,
                    'phoneclient' =>$request->phoneclient,
                    'status'=> 1
                ]);
                $myDevice->save();
            }
        }else{
            /*Customer::where('id',$request->id)->update([
                'contact_at' =>  $request->device,
            ]);*/
        }
    }

    public function recupRechargeSolde(Request $request){
        $trans = Historiquetrans::where('operation', 'demande_solde')->where('phonevendeur',$request->phone)->where('etat', 'atraiter')->first();
        if($trans){
            return response()->json([
                'statut'=>true,
                'trans' => $trans
            ]);
        }else{
            return response()->json([
                'statut'=>false
            ]);
        }
    }

    public function addCommissionParrain($transId , $filleul, $type){
        $filleul = Customer::where('id', $filleul)->first();
        if(!empty($filleul->phoneparent) && !empty($filleul->expirationparrainage) && $filleul->expirationparrainage != 'off' && str_contains($filleul->expirationparrainage, '-')) {

            $expiration = date('Y-m-d H:i:s', strtotime($filleul->expirationparrainage));
            $toDay = date('Y-m-d H:i:s');
            if($expiration < $toDay){
                Customer::where('id', $filleul->id)->update([
                    'expirationparrainage' => 'off',
                    'last_transaction_at' => date('Y-m-d H:i:s')
                ]);
                return response()->json([
                    'statut'=> false,
                    'message' => 'compte inexistant',
                    'data' => $filleul
                ]);
            }

            $parrain = Customer::where('phoneclient', $filleul->phoneparent)->first();
            $frais = 0;
            if(!empty($parrain)){
                if(!empty($parrain->flp_nbr_transaction) && str_contains($parrain->flp_nbr_transaction, ',')){
                    $tabProduits = json_decode($parrain->flp_nbr_transaction, true);
                    $credit = $tabProduits['credit'];
                    $forfait = $tabProduits['forfait'];
                    $visa = $tabProduits['visa'];
                    $transfert = $tabProduits['transfert'];
                    $es = $tabProduits['es'];
                    $forfaitInternational = $tabProduits['forfaitInternational'];
                    $view = $tabProduits['view'];
                    $edan = $tabProduits['edan'];
                    $ussd = $tabProduits['ussd'];
                }else{
                    $credit = 0;
                    $forfait = 0;
                    $visa = 0;
                    $transfert = 0;
                    $es = 0;
                    $forfaitInternational = 0;
                    $view = 0;
                    $edan = 0;
                    $ussd = 0;
                }
                if($type=='ussd') {
                    if($transId == '100'){
                        $ussd = $ussd +20;
                        $frais = 20;
                    }else{
                        $ussd = $ussd +100;
                        $frais = 100;
                    }
                }else if( $type=='edan') {
                    $edan =  $edan + 100;
                    $frais = 100;
                }else {
                    $trans = Historiquetrans::where('id', $transId)->first();
                    $frais = intval($trans->frais);
                    switch ($trans->operation) {
                        case 'achat_credit':
                            $credit = $credit + intval($trans->frais);
                            break;

                        case 'achat_forfait':
                            $forfait = $forfait + intval($trans->frais);
                            break;

                        case 'recharge_visa_uba':
                            $visa = $visa + intval($trans->frais);
                            break;

                        case 'transfert_mobile': case 'transfert_visa':
                        $transfert = $transfert + intval($trans->frais);
                        break;

                        case 'preinscription':
                            $es = $es + intval($trans->frais);
                            break;

                        case 'forfait_international': case 'sim_international': case 'esim_international':
                        $forfaitInternational = $forfaitInternational + intval($trans->frais);
                        break;

                        case 'achat_status':
                            $view = $view + intval($trans->frais);
                            break;
                    }

                    Historiquetrans::where('id', $trans->id)->update([
                        'code_validation' => $trans->frais
                    ]);
                }

                // store total frais filleul
                Customer::where('id', $filleul->id)->update(['ability'=> $frais + $filleul->ability,'last_transaction_at' => date('Y-m-d H:i:s')]);
                $newSoldeParrain = $credit + $forfait + $visa + $transfert + $es + $forfaitInternational + $view + $ussd + $edan;
                Customer::where('id', $parrain->id)->update([
                    'flp_nbr_transaction' => json_encode([
                        'credit' => $credit,
                        'forfait' => $forfait,
                        'visa' => $visa,
                        'transfert' => $transfert,
                        'es' => $es,
                        'forfaitInternational' => $forfaitInternational,
                        'view' => $view,
                        'ussd' => $ussd,
                        'edan' => $edan
                    ]),
                    'solde_parrainage' => strval($newSoldeParrain),

                ]);

                // notification commission
                $messageNotif = "Vous avez reçu une commission de $frais FCFA grâce à  $filleul->nom  $filleul->prenom";
                $this->sendNotificationInprogram($parrain->phoneclient, $parrain->phoneclient,'info', '',$messageNotif,null, '' );


                return response()->json([
                    'statut'=> true,
                ]);
            }else{
                return response()->json([
                    'statut'=> false,
                    'message' => 'pas de parrain'
                ]);
            }
        }else{
            Customer::where('id', $filleul)->update([
                'expirationparrainage' => 'off',
                'last_transaction_at' => date('Y-m-d H:i:s')
            ]);
            return response()->json([
                'statut'=> false,
                'message' => 'compte inexistant',
                'data' => $filleul
            ]);
        }
    }

    public function addFilleulMyPay(Request $request){
        $now = date('Y-m-d');
        return response()->json([
            'statut'=> false,
            'message' => 'Utilisez votre code pour obtenir de nouveaux filleuls',
        ]);

        $tantatives = ErrorApp::where('created_at', 'like', "$now%")->where('phoneclient', $request->parrain)->where('operation', 'addFilleulChallenge')->get();
        if(count($tantatives) >= 7){
            return response()->json([
                'statut'=> false,
                'message' => 'Vous avez épuisé votre nombre de tentatives journalière',
            ]);
        }else{
            $parrain =  Customer::where('phoneclient',$request->parrain)->first();
            $verifaccount = Customer::where('phoneclient', $request->filleul)->first();
            $limite = date('Y-m-d', strtotime('+30 days'));
            //$verifActivityUssd = GamRechargeHist::where('phonevendeur', $request->filleul)->get();
            if($verifaccount){
                if(empty($verifaccount->phoneparent)){
                    if(empty($verifaccount->last_transaction_at)){
                        Customer::where('id', $verifaccount->id)->update([
                            'phoneparent'=> $parrain->phoneclient,
                            //'expirationparrainage' => $limite ,
                            'option1'=>empty($verifaccount->option1)?'parrainVerify' : $verifaccount->option1. '_parrainVerify',
                        ]);
                        Customer::where('id', $parrain->id)->update([
                            'filleuls'=> $parrain->filleuls + 1,
                            //'Fonction2' => empty($parrain->Fonction2)? 1 : $parrain->Fonction2 + 1
                        ]);
                        return response()->json([
                            'statut'=> true,
                            'message' => 'Filleul ajouté',
                        ]);
                    }else{
                        return response()->json([
                            'statut'=> false,
                            'message' => 'le '. $request->filleul. ' possède déjà un compte GamPay',
                        ]);
                    }
                }else{
                    return response()->json([
                        'statut'=> false,
                        'message' => 'le '. $request->filleul. ' a déjà été parrainné',
                    ]);
                }
            }else{
                Customer::where('id', $parrain->id)->update([
                    'filleuls'=> $parrain->filleuls + 1,
                    //'Fonction2' => empty($parrain->Fonction2)? 1 : $parrain->Fonction2 + 1
                ]);
                $customer = new Customer([
                    'nom'=> 'client',
                    'prenom'=> 'GAM',
                    'pays'=> $request->indicatif,
                    'phoneclient'=> $request->filleul,
                    'mdpclient'=> '1234',
                    'phoneparent'=> $parrain->phoneclient,
                    'option1'=> 'parrainVerify',
                    //'expirationparrainage' => $limite,
                    'code_confirm'=> $parrain->code_confirm,
                    'status'=>1,
                    'contry_code'=> $request->iso,
                    'zone'=> $request->pays
                ]);
                $customer->save();
                return response()->json([
                    'statut'=> true,
                    'message' => 'Filleul ajouté',
                ]);
            }
        }
    }

    public function sendWhatsAppMessage($phone, $message, $service){
        switch ($service){
            case 'satisfaction':
                $sender = "+24104471892";
                break;
            case 'rm':
                $sender = "+24104767855";
                break;
            case 'international':
                $sender = "+24105949831";
                break;
            case 'sigma':
                $sender = "+24102195661";
                break;
            default:
                $sender = "+24104767855";
        }

        $data = [
            "phone"=> $phone,
            "whatsapp_account_phone"=> $sender,
            "text"=> $message,
            //"file_id"=> "afa9d4dd-978d-4a14-aa1b-bd65c272e645",
            //"label"=> "customer"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.timelines.ai/integrations/api/messages");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer 94171fac-a916-4f7c-b634-0760d146e4f7"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);// Check HTTP status code
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ( $http_code == 200 ||  $http_code == 201) {

                $response = json_decode($result, true);
                if($response['status'] == 'ok'){
                    $verif = $this->getStatusWhatsApp($response['data']['message_uid']);
                    if($verif == 1){
                        return 1;
                    }else{
                        return 0;
                    }
                }else{
                    return 0;
                }
            }else{
                return 0;
            }
        }else{
            return 0;
        }
        // Close handle
        curl_close($ch);
    }

    public function sendWhatsAppMessageGet(Request $request){
        switch ($request->service){
            case 'satisfaction':
                $sender = "+24104471892";
                break;
            case 'rm':
                $sender = "+24104767855";
                break;
            case 'international':
                $sender = "+24105949831";
                break;
            case 'sigma':
                $sender = "+24102195661";
                break;
            default:
                $sender = "+24104767855";
        }

        if(!empty($request->pays)){
            $nuwhatsApp = $this->getWhatsFormat($request->phone,$request->pays);
        }else{
            $nuwhatsApp = $request->phone;
        }

        $data = [
            "phone"=> $nuwhatsApp,
            "whatsapp_account_phone"=> $sender,
            "text"=> $request->message,
            //"file_id"=> "afa9d4dd-978d-4a14-aa1b-bd65c272e645",
            //"label"=> "customer"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.timelines.ai/integrations/api/messages");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer 94171fac-a916-4f7c-b634-0760d146e4f7"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);// Check HTTP status code
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ( $http_code == 200 ||  $http_code == 201) {

                $response = json_decode($result, true);
                if($response['status'] == 'ok'){
                    $verif = $this->getStatusWhatsApp($response['data']['message_uid']);
                    if($verif == 1){
                        return 1;
                    }else{
                        return 0;
                    }
                }else{
                    return 0;
                }
            }else{
                return 0;
            }
        }else{
            return 0;
        }
        // Close handle
        curl_close($ch);
    }

    public function getStatusWhatsApp($messageUID){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.timelines.ai/integrations/api/messages/$messageUID");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer 94171fac-a916-4f7c-b634-0760d146e4f7"));
        $result = curl_exec($ch);// Check HTTP status code
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ( $http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if($response['data']['status'] == 'Failed' || $response['data']['status'] == 'Sent' || $response['data']['status'] == 'Pending' ){
                    return 0;
                }else{
                    return 1;
                }
            }else{
                return 0;
            }
        }else{
            return 0;
        }
        // Close handle
        curl_close($ch);
    }

    public function getWhatsAppNumber($num,$pays){
        $numWhatsApp = strval($num);
        $newWhatsApp = 0;
        $four = $numWhatsApp[0].''. $numWhatsApp[1].''. $numWhatsApp[2].''. $numWhatsApp[3];
        $three = $numWhatsApp[0].''. $numWhatsApp[1].''. $numWhatsApp[2];
        if($four == '+241' && strlen($numWhatsApp) == 12){
            if($numWhatsApp[5] != 0){
                $newWhatsApp ='2410'. substr($numWhatsApp, 5);
            }else{
                if(in_array($numWhatsApp[5], ['6','2','5'])){
                    $newWhatsApp ='2416'. substr($numWhatsApp, 5);
                }else{
                    $newWhatsApp ='2417'. substr($numWhatsApp, 5);
                }
            }
        }elseif($three == '241' && strlen($numWhatsApp) == 11){
            if($numWhatsApp[4] != 0){
                $newWhatsApp ='2410'. substr($numWhatsApp, 4);
            }else{
                if(in_array($numWhatsApp[4], ['6','2','5'])){
                    $newWhatsApp ='2416'. substr($numWhatsApp, 4);
                }else{
                    $newWhatsApp ='2417'. substr($numWhatsApp, 4);
                }
            }
        }elseif(strlen($numWhatsApp)==9 && $numWhatsApp[0] == '0' && in_array($pays, ['241', '+241']) ){
            $newWhatsApp ='241'. substr($numWhatsApp, 1);
        }elseif(strlen($numWhatsApp)== 8 && in_array($pays, ['241', '+241'])){
            $newWhatsApp ='241'.$numWhatsApp;
        }elseif (strlen($numWhatsApp)>10){
            $newWhatsApp = $pays.''.$numWhatsApp;
        }

        return $newWhatsApp;
    }

    public function getWhatsFormat($num,$pays){
        $numWhatsApp = strval($num);
        if(strlen($numWhatsApp)==9 && $numWhatsApp[0] == '0' && in_array($pays, ['241', '+241']) ){
            $newWhatsApp ='241'. substr($numWhatsApp, 1);
        }elseif(strlen($numWhatsApp)== 8 && in_array($pays, ['241', '+241'])){
            $newWhatsApp ='241'.$numWhatsApp;
        }elseif (strlen($numWhatsApp)<10){
            $newWhatsApp = $pays.''.$numWhatsApp;
        }else{
            $newWhatsApp = $num;
        }
        return $newWhatsApp;
    }

    public function contactVisaValideTrans(){
        //$customers = DB::select("SELECT * FROM customer c WHERE phonenumber_assoc_3 is null and grade = 'visa' and id not in (select customer from contact c where  module='auto_whatsapp' and services = 'international') LIMIT 2");
        //$customers = DB::select("SELECT * FROM customer c WHERE phoneclient in ('074119984', '074582442', '074071340', '074752742', '074595435')");
        //$customers = DB::select("select * from customer c WHERE c.param5 like 'migration%' and score < 10 and username is NULL and phoneclient not in (select phoneclient from contact where  module ='migrate' and agent = 'AUTO') ");
        $customers = DB::select("select * from customer c WHERE c.param5 like 'migration%' and score < 10 and username is NULL limit 2");
        if(count($customers)>0){
            foreach ( $customers as $customer){

                if( in_array($customer->nom, ['GAM', 'Client', 'client', 'xxxx', '','XXXX', NULL]) || in_array($customer->prenom, ['GAM', NULL,'Client', 'client', 'xxxx', '','XXXX'])){
                    $nom = 'cher client';
                }else{
                    $nom = $customer->nom.' '.$customer->prenom;
                }

                $message = "Bonjour $nom, Nous sommes heureux de vous annoncer que votre service d'achat de crédit via l'option **150*3*10*GAM*MONTANT*NUMERO*MOt_DE_PASSE#* est de nouveau disponible.

Vous avez donc le choix d'utiliser ce dernier ou votre application *GamPay* pour profiter de nos services.

Nous restons à votre écoute pour toutes vos préoccupations." ;

                //$this->sendNotificationInprogram($customer->phoneclient,$customer->phoneclient,'info', '',$message,null, null );
                if(!empty($customer->whatsapp)){
                    $whatsAppNum = $customer->whatsapp;
                }else{
                    $whatsAppNum= $this->getWhatsAppNumber($customer->phoneclient, $customer->pays);
                }
                $whatsApp = $this->sendWhatsAppMessage($whatsAppNum,$message, 'satisfaction');
                /*if($whatsApp != 1){
                    $newWhatsAppNum = $this->getWhatsAppNumber($whatsAppNum, $customer->pays);
                    $this->getWhatsAppNumber($whatsAppNum, $customer->pays);
                    $this->sendWhatsAppMessage($newWhatsAppNum,$message, 'satisfaction');
                }*/

                Customer::where('id', $customer->id)->update([
                    'param5'=> NULL
                ]);

                $contact = new Contact([
                    'services' => 'satisfaction',
                    'agent' => 'AUTO',
                    'phoneclient'=> $customer->phoneclient,
                    'customer' => $customer->id,
                    'trans_customer' =>0,
                    'module'=>'migrate',
                    'support'=>'whatsapp',
                    'app'=>'back_office',
                ]);
                $contact->save();
            }
        }
        return $customers;
    }

    public function PointFinAnnee(Request $request){
        $pointCumuleapplication=0;
        $pointCumuleUssd=0;
        $challenger=Customer::where('phoneclient',$request->phoneclient)->first();

        if($challenger){
            $pointCumuleapplication=$challenger->score;
            $customerussd=Client2::where('numclient',$request->numclient)->first();
            if($customerussd){
                $pointCumuleUssd=$customerussd->score;
            }
        }
        return view('challenge/boris',compact('pointCumuleapplication','pointCumuleUssd', 'challenger'));
    }

    public function statUSSDandNewClient(){
        $hier = date("Y-m-d", strtotime('-1 day'));
        $now = date("Y-m-d");
        //1
        $periode = date("Y-m-d", strtotime('-3 month'));
        $clientElectorauxUSSD = [];
        //$clientElectorauxUSSD = DB::select("SELECT phonevendeur from gamrechargehist g where g.port = '6A' and g.created_at BETWEEN '2023-08-26 00:00:00' and '2023-08-30 00:00:00'");
        $clientEndormi = DB::select("SELECT * from customer c where c.phoneclient not in (select phonevendeur from historiquetrans h where h.created_at <= '$periode 00:00:00')");

        //2
        //$smsClientUSSD = ;
        $SMS = DB::select("SELECT * from contact c where c.created_at like '$now%'");
        $SMS_hier = DB::select("SELECT * from contact c where c.created_at like '$hier%'");


        $periode = date("Y-m-d", strtotime('-3 mounth'));
        $customNewApp = Customer::where('created_at','>=','2023-11-01 00:00:00')->whereNotNull('unlock_token')->get();
        $customNewUSSD = DB::select("SELECT * from client2 c2 where c2.created_at like '$now%'");
        $customNewUSSD_Hier = DB::select("SELECT * from client2 c2 where c2.created_at like '$hier%'");

        return response()->json([
            'new_client_app_install' => count($customNewApp),
            'client_electoraux_USSD' => count($clientElectorauxUSSD),
            'new_client_USSD' => count($customNewUSSD),
            'new_client_USSD_hier' => count($customNewUSSD_Hier),
            'client_endormi' => count($clientEndormi),
            'SMS_envoi' => count($SMS),
            'SMS_envoi_hier' => count($SMS_hier)
        ]);
    }


    public function searchOrCreateCustomer(Request $request){

        if(!empty($request->whatsapp)){
            $numSansPlus = substr($request->whatsapp, 1);
            $myCustomer = Customer::whereIn('whatsapp',[$request->whatsapp, $numSansPlus])->first();

        }else{
            $myCustomer = Customer::whereIn('phoneclient',[$request->phone, '0'.$request->phone])->first();
        }
        if($myCustomer){
            $compte = $myCustomer;
            $status = 'old';
        }else{
            $parrain = Customer::where('phoneclient',$request->parrain )->first();
            $customer = new Customer([
                'nom'=> 'client',
                'prenom'=> 'GAM',
                'pays'=> '+241',
                'phoneclient'=> $request->phone,
                'solde'=> '0',
                'mdpclient'=> '1234',
                'phoneparent'=> $parrain->phoneclient,
                'option1'=> $parrain->option1,
                'code_confirm'=> $parrain->code_confirm,
                'status'=>1
            ]);
            $customer->save();
            $compte = $customer;
            $status = 'new';
            $this->sendNotificationInprogram($request->phone,$request->phone,'info', '',"Bienvenu sur GamPay, Votre compte a été créé par $parrain->nom",null,null);

        }
        return response()->json([
            'status' => $status,
            'compte' => $compte,
            'phoneclient' => $compte->phoneclient,
            'name' => $compte->nom ." ". $compte->prenom,
            'id' => $compte->id,
            'solde' => $compte->solde
        ]);
    }


    // Gestion des carte GAM
    public function loginCard(Request $request){
        if(!empty($request->card)){
            $myCard = Card::where('qr', $request->card)->first();

            if($myCard){
                if($myCard->status == 1){

                    if(!empty($myCard->customer)){
                        $client = Customer::where('id',$myCard->customer)->get();
                    }else{
                        Customer::where('id', intval($request->user))->update([
                            'solde'=> $myCard->amount,
                            'nom' => $myCard->partner_name,
                            'phoneclient' => $myCard->code
                        ]);
                        $client = Customer::where('id',intval($request->user))->get();
                    }

                    foreach ($client as $customer) {
                        $compt = Compteur::where('num_client', $customer['phoneclient'])->get();
                        $devices = Device::where('id_customer', $customer['id'])->get();// on recupère les appareils
                        $forfaitInternational = ForfaitInternational::orderby('id', 'desc')->get();
                        $deviceInternationalRequest = DeviceInternal::all();
                        $sim = SimInternal::where('customerId', $customer['id'])->orWhere('CustomerNum', $customer['phoneclient'])->get();

                        if (count($compt) > 0) {
                            $compteur = $compt;
                        } else {
                            $compteur = 0;
                        }

                        if ($request->deviceInfo == 'GAB') {
                            $nowDay = date("Y-m-d");
                            $hisroryWait = Historiquetrans::where('phonevendeur', $customer['phoneclient'])->whereIn('etat', ['attend', 'en_agence_attend'])->where('created_at', 'like', '' . strval($nowDay) . '%')->get();
                            $historyPayment = count($hisroryWait);
                        } else {
                            $historyPayment = 0;
                        }

                        $nombre = $customer['filleuls'];
                        return response()->json([
                            'statut' => true,
                            'token' => $customer['id'],
                            'customer' => $client,
                            'filleul' => $nombre == null ? 0 : $nombre,
                            'team' => $customer['option1'],
                            'compteurs' => $compteur,
                            'devices' => $devices,
                            'devicesInternationaux' => $deviceInternationalRequest,
                            'forfaitsInternationaux' => $forfaitInternational,
                            'paymentWait' => $historyPayment,
                            'sim' => $sim,
                            'localisation' => $this->datasApp(),
                            'am' => '*150*3*10*746*',
                            'moov' => '*555*5*7*746*'
                        ]);

                    }
                }else{
                    return response()->json([
                        'statut'=> false,
                        'message' => "Cette carte n'est pas active",
                    ]);
                }
            }else{
                return response()->json([
                    'statut'=> false,
                    'message' => 'Code inconnu',
                ]);
            }
        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'Informations incorrecte',
            ]);
        }
    }


    public function affectationCard(Request $request){
        if(!empty($request->card) && !empty($request->phoneclient)){
            $customer = Customer::where('phoneclient',$request->phoneclient)->first();
            $myCard = Card::where('code', $request->card)->first();

            if($customer){

                $customer->update([
                    'solde' => strval(doubleval($customer->solde) + doubleval($myCard->amount)),
                ]);
                $myCard->update([
                    'customer' => $customer->id,
                    'phone' => $customer->phoneclient,
                    'amount' => '0',
                ]);

            }else{
                $partner = Customer::where('phoneclient', $myCard->partner)->first();
                $newCustomer = new Customer([
                    'nom'=> 'client',
                    'prenom'=> 'GAM',
                    'phoneclient'=> $request->phoneclient,
                    'mdpclient'=> '1234',
                    'option1'=> $partner->option1,
                    'solde' => $myCard->amount,
                    'code_confirm'=> $partner->option1,
                    'pays'=>$partner->pays,
                    'status'=>1,
                    'contry_code'=> $partner->contry_code,
                    'zone'=> $partner->zone
                ]);
                $newCustomer->save();
                $myCard->update([
                    'customer' => $newCustomer->id,
                    'phone' => $request->phoneclient,
                    'amount' => '0',
                ]);
                Http::get('http://gampay.org/gamclients/public/api/recupIdCustomer');
            }
            $myAccount = Customer::where('phoneclient',$request->phoneclient)->get();
            return response()->json([
                'statut'=> true,
                'message' => '',
                'customer'=> $myAccount
            ]);

        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'Informations incorrecte',
            ]);
        }
    }

    public function statusCard(Request $request){
        $myCard = Card::where('code', $request->card)->first();
        if($myCard){
            $myCard->update([
                'status' => $request->status,
            ]);
            return response()->json([
                'statut'=> true,
                'message' => 'Operation réussie',
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'Code inconnu',
            ]);
        }
    }

    // Fin Gestion des carte GAM

    public function getPriority(Request $request){
        $customer = Customer::where('id', $request->customer)->first();
        if($customer){
            if(!empty($customer->priority)){
                $priority = json_decode($customer->priority);
                $myPriority = $customer->priority;
                $customer->update(['priority'=> $request->customerNull]);
            }else{
                $myPriority = 0;
                $priority = 0;
            }
            return $myPriority;
        }
    }


    public function commandeGampay(Request $request){
        $partenaire = Customer::where('id', intval($request->partner))->first();
        if(!empty($partenaire)){
            $message = "Commande GamPay de $request->amount FCFA initié par *$partenaire->nom* *$partenaire->prenom*  le *$partenaire->phoneclient*";
            $this->sendWhatsAppMessage('24176520541',$message, 'other');
            $this->sendWhatsAppMessage('24174582442',$message, 'other');
            $commande = Historiquetrans::where('operation', 'commande_gampay')->where('phonevendeur', $partenaire->phoneclient)->where('etat', 'en_attente')->get();
            if(empty($partenaire->id_boutique) || $partenaire->id_boutique == '0'){
                $plafond = '50000';
                return response()->json([
                    'statut'=> false,
                    'message' => "Ce service n'est pas actif sur votre compte"
                ]);
            }else{
                $plafond = $partenaire->id_boutique;
            }
            if(count($commande)==0){
                if(intval($request->amount) <= intval($plafond)){
                    $compte = Customer::where('id',9855740)->first();
                    if(doubleval($compte->solde) > doubleval($request->amount)){
                        $num = $partenaire->id + intval($request->amount);
                        $code = 'gCg'.time().''.$num;

                        //$frais = round(doubleval($request->amount) * 0.025);
                        $frais = 0;
                        $montantRecharge = intval($request->amount) - $frais;

                        $newCommande = new Historiquetrans([
                            'operation' => 'commande_gampay',
                            'reference' => 'AUTO',
                            'etat' => 'en_attente',
                            'numclient' => $partenaire->phoneclient,
                            'phonevendeur' => $partenaire->phoneclient,
                            'content'=> 'recharge_compte_gam',
                            'montant' =>$request->amount,
                            'montant_sans_frais' => strval($montantRecharge),
                            'code_validation' => $compte->id,
                            'frais' => strval($frais),
                            'solde' => $partenaire->solde,
                            'origine_operation' => $request->origine,
                            'id_customer' => $partenaire->id,
                            'param6' => $code
                        ]);
                        $newCommande->save();
                        $newSolde = $montantRecharge + doubleval($partenaire->solde);
                        Customer::where('id', $partenaire->id)->update([
                            'solde' => strval($newSolde),
                            'id_boutique'=> (empty($partenaire->id_boutique) || $partenaire->id_boutique == '0')?$plafond:$partenaire->id_boutique
                        ]);

                        $newSoldeCompte = doubleval($compte->solde) - $montantRecharge;
                        Customer::where('id', $compte->id)->update([
                            'solde' => $newSoldeCompte,
                        ]);

                        $this->sendNotificationInprogram($compte->phoneclient,$partenaire->phoneclient,'gam_transfert', strval($request->amount),null,null,null);
                        return response()->json([
                            'statut'=> true,
                            'message' => 'Votre compte a été rechargé de '.$request->amount.' FCFA'
                        ]);
                    }else{
                        return response()->json([
                            'statut'=> false,
                            'message' => 'Fonds indisponible pour le moment rééssayez plus tard'
                        ]);
                    }

                }else{
                    return response()->json([
                        'statut'=> false,
                        'message' => 'Vous ne pouvez commander plus de '.$partenaire->id_boutique.' FCFA'
                    ]);
                }
            }else{
                return response()->json([
                    'statut'=> false,
                    'message' => 'Vous avez une commande impayée'
                ]);
            }
        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'Informations incorrecte'
            ]);
        }
    }

    public function recupFondPartner(Request $request){
        $trans = Historiquetrans::where('param6' , $request->code)->first();
        if(!empty($trans)){
            if($trans->etat == 'en_attente'){
                Historiquetrans::where('id', $trans->id)->update([
                    'etat' => 'CONFIRMEE',
                    'param9' => $request->agent
                ]);
                return response()->json([
                    'statut'=> true,
                    'message' => 'Paiement effectué'
                ]);
            }else{
                return response()->json([
                    'statut'=> false,
                    'message' => 'Vous avez déjà réglé cette commande'
                ]);
            }
        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'Informations incorrecte'
            ]);
        }
    }

    // interface Mypay
    public function sendTemplateMessageMyPay($whatsapp,$messageId,$operation,$file)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "'.$whatsapp.'",
                      "messageId": "'.$messageId.'",
                      "content": {
                        "templateName": "rm",
                        "templateData": {
                          "body": {
                            "placeholders": ["'.$operation.'", "Votre application GamPay" ]
                          },
                          "header": {
                            "type": "VIDEO",
                            "mediaUrl": "'.$file.'"
                          },
                          "buttons": [
                            {"type": "QUICK_REPLY", "parameter": "gam_parrainage"},
                            {"type": "QUICK_REPLY", "parameter": "gam_partenaire"},
                            {"type": "QUICK_REPLY", "parameter": "gam_annonce"},
                            {"type": "QUICK_REPLY", "parameter": "gam_other"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBackMyPay"
                    }
                  ]
                }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $result = curl_exec($curl);
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ( $http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if($response['messages'][0]['status']['groupName'] == 'PENDING'){
                    return 1;
                }else{
                    return 0;
                }
            }else{
                return 10;
            }
        }else{
            return 100;
        }
        // Close handle
        curl_close($curl);
    }

    public function checkNumberUseWhatsapp($numero, $type){
        if($type ==''){
            $numSansZero = substr($numero, 1);
            $whatsappVerify = "241$numSansZero";
        }else{
            $whatsappVerify = $numero;
        }
        $numwhat = WhatsAppVerify::where('number', $whatsappVerify)->first();
        if($numwhat){
            if($numwhat->status == "true"){
                return 1;
            }else{
                return 0;
            }
        }else{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://zylalabs.com/api/926/whatsapp+number+checker+api/743/number+checker?number=$whatsappVerify");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer 3785|LrBx4lTuGHb9YarTqygv2ukzzmfRGIXZozfaxosa"));
            $result = curl_exec($ch);// Check HTTP status code
            if (!curl_errno($ch)) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ( $http_code == 200 ||  $http_code == 201) {
                    $response = json_decode($result, true);
                    if($response['status'] != true){
                        return 0;
                    }
                    if($response['numberstatus'] == true){
                        $andWhat = new WhatsAppVerify([
                            'number'=>  "$whatsappVerify",
                            'status' => "true"
                        ]);
                        $andWhat->save();
                        return 1;
                    }else{
                        $andWhat = new WhatsAppVerify([
                            'number'=>  "$whatsappVerify",
                            'status' => "false"
                        ]);
                        $andWhat->save();
                        return 0;
                    }
                }else{
                    return 10;
                }
            }else{
                return 100;
            }
            // Close handle
            curl_close($ch);
        }

    }
    //Fin interface MyPay

    public function sendInstallationTemplate($customer, $trans){

        if(in_array($trans->operation, ['paiement_partenaire', 'debit'])){
            $operations = Historiquetrans::where('operation', $trans->operation)->where('etat', 'CONFIRMEE')->where('phonevendeur', $customer->phoneclient)->get();
            if($trans->operation == 'paiement_partenaire'){
                if($customer->avatar == 'android' && count($operations)<=1){
                    $messageId = 'paiement'.$customer->id.''.time();
                    $message = "SIMPLE, RAPIDE et EFFICACE ! Terminer votre dépôt 1xBET avec l’application GamPay. 🙂‍↔ Se référer à la vidéo ci-dessus.";
                    $video = "https://gampay.org/video_flow/depot_apk.mp4";
                }else{
                    return 1;
                }
            }else{
                if(count($operations)>1){
                    return 1;
                }
                $messageId = 'debit'.$customer->id.''.time();
                $message = "Félicitations cher client🎊, votre compte GamPay vient d’être recharger d’un montant de $trans->montant_sans_frais. Récupérez vos fonds en effectuant un transfert vers votre compte mobile money.";
                $video = "https://gampay.org/video_flow/retrait_apk.mp4";
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "'.$customer->whatsapp.'",
                      "messageId": "'.$messageId.'",
                      "content": {
                        "templateName": "product_demo",
                        "templateData": {
                          "body": {
                            "placeholders": ["'.$message.'"]
                          },
                          "header": {
                            "type": "VIDEO",
                            "mediaUrl": "'.$video.'"
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "'.$customer->id.'"},
                            {"type": "URL", "parameter": "'.$customer->whatsapp.'"},
                            {"type": "QUICK_REPLY", "parameter": "assister"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                    }
                  ]
                }',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            ));
            return $result = curl_exec($curl);
        }else{
            $messageId = 'rmi'.$customer->id.''.time();
            $operation = 'operation';
            switch($trans->operation){
                case 'achat_credit' :
                    $operation = "achat crédit";
                    break;
                case 'recharge_visa_uba' :
                    $operation = "recharge visa";
                    break;
                case 'achat_forfait' :
                    $operation = "achat forfait";
                    break;
                case 'transfert_mobile' :
                    $operation = "transfert";
                    break;
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "'.$customer->whatsapp.'",
                      "messageId": "'.$messageId.'",
                      "content": {
                        "templateName": "rmapp",
                        "templateData": {
                          "body": {
                            "placeholders": ["Votre *'.$operation.'* de *'.$trans->montant_sans_frais.'* F pour le *'.$trans->numclient.'* a été effectué avec succès", "d\'une meilleure expérience grâce à notre application *GamPay*", "votre *'.$customer->phoneclient.'* et votre mot de passe *'.$customer->mdpclient.'*"]
                          },
                          "header": {
                            "type": "IMAGE",
                            "mediaUrl": "https://gampay.org/app.PNG"
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "'.$customer->id.'"},
                            {"type": "URL", "parameter": "'.$customer->whatsapp.'"},
                            {"type": "QUICK_REPLY", "parameter": "assister"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                    }
                  ]
                }',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            ));
            $result = curl_exec($curl);
        }



        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ( $http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if($response['messages'][0]['status']['groupName'] == 'PENDING'){
                    return 1;
                }else{
                    return [0, $response];
                }
            }else{
                return [10, $result];
            }
        }else{
            return [100, $result];
        }
        // Close handle
        curl_close($curl);
    }

    public function getlinkStoreForInstallation(Request $request){
        $link = 'https://app.gampay.org/?p=download&v=vue';
        if(!empty($request->customer)){
            $customer = Customer::where('id', $request->customer)->first();
            if($customer){
                Customer::where('id', $customer->id)->update([
                    //'statut'=> 'installation'
                ]);
                if(strval($customer->avatar) == 'android'){
                    $link = 'https://play.google.com/store/apps/details?id=com.gampay.gam';
                }elseif(strval($customer->avatar) == 'ios'){
                    $link = 'https://apps.apple.com/ga/app/gampay/id6448640058';
                }
            }
        }

        return redirect($link);
    }

    public function testTemplateInstallation(Request $request){
        $customer = Customer::where('phoneclient',$request->phone)->first();
        $trans = Historiquetrans::where('phonevendeur',$customer->phoneclient)->where('operation', 'achat_credit')->first();
        $template = $this->sendInstallationTemplate($customer, $trans);
        return $template;
    }

    public function sendWhatsAppGroupMessage($message, $groupe, $service){
        switch ($service){
            case 'satisfaction':
                $sender = "+24104471892";
                break;
            case 'rm':
                $sender = "+24104767855";
                break;
            case 'international':
                $sender = "+24105949831";
                break;
            case 'sigma':
                $sender = "+24102195661";
                break;
            default:
                $sender = "+24104084184";
        }


        $data = [
            "chat_name"=> $groupe,
            "whatsapp_account_phone"=> $sender,
            "text"=> $message,
            //"file_id"=> "afa9d4dd-978d-4a14-aa1b-bd65c272e645",
            //"label"=> "customer"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.timelines.ai/integrations/api/messages/to_chat_name");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer 94171fac-a916-4f7c-b634-0760d146e4f7"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);// Check HTTP status code
        return $result;
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ( $http_code == 200 ||  $http_code == 201) {

                $response = json_decode($result, true);
                if($response['status'] == 'ok'){
                    $verif = $this->getStatusWhatsApp($response['data']['message_uid']);
                    return $verif;
                    if($verif == 1){
                        return 1;
                    }else{
                        return 0;
                    }
                }else{
                    return $result;
                }
            }else{
                return $result;
            }
        }else{
            return $result;
        }
        // Close handle
        curl_close($ch);
    }

    /*public function sendWhatsAppGroupMessage($message, $groupe, $service){
        switch ($service){
            case 'satisfaction':
                $sender = "+24104471892";
                break;
            case 'rm':
                $sender = "+24104767855";
                break;
            case 'international':
                $sender = "+24105949831";
                break;
            case 'sigma':
                $sender = "+24102195661";
                break;
            default:
                $sender = "+24104767855";
        }

        $data = [
            "chat_name"=> $groupe,
            "whatsapp_account_phone"=> $sender,
            "text"=> $message,
            //"file_id"=> "afa9d4dd-978d-4a14-aa1b-bd65c272e645",
            //"label"=> "customer"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.timelines.ai/integrations/api/messages/to_chat_name");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer 94171fac-a916-4f7c-b634-0760d146e4f7"));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $result = curl_exec($ch);// Check HTTP status code
        return $result;

        // Close handle
        curl_close($ch);
    }*/

    public function getContryIp($myIp){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "ipinfo.io/$myIp?token=5aa25ae6c48863");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer 94171fac-a916-4f7c-b634-0760d146e4f7"));
        $result = curl_exec($ch);// Check HTTP status code
        $response = json_decode($result);
        if(empty($response->country)){
            return '0';
        }
        return strval($response->country);
    }

    public function getContryIpTest(Request $request){
        $myIp = $request->ip();
        return $this->getContryIp($myIp);
    }

    public function accountValidation(Request $request){
        $reponse = 'ko';
        $customer = '';
        $type = $request->type;
        if(!empty($request->key)){
            $customer = Customer::where('id', $request->key)->first();
            if($customer){
                Customer::where('id', $customer->id)->update([
                    'status' => 1000,
                    'Fonction3' =>$request->keyNUll,
                    'param8' =>$request->keyNUll,
                    'my_device' =>$request->keyNUll
                ]);
                $reponse = 'ok';

            }
        }
        return view('challenge.validation', compact('reponse', 'customer', 'type'));
    }

    public function calculPointChallenge($filleus, $installation, $trafic, $folowers){
        $calcul = $filleus + ($installation * 3) + ($trafic * 5) + $folowers;
        return $calcul;
    }

    public function sendNewTemplateMessageRm($whatsapp, $customer_name, $amount, $type, $lev, $numClient, $pass)
    {
        $curl = curl_init();
        $messageId = 'rm/' . $type . '' . time();
        $messageCadeau = "🎊🎊 Félicitations cher client ! 🎊🎊 Grâce à votre $lev ème récupération de monnaie. GAM Gabon vous offre une E-SIM pour vos voyages à l'international et 0 frais sur votre premier transaction sur GamPay 🎉🎊. Récupérez votre cadeau 🎁 en vous connectant à l'application GamPay avec votre $numClient et votre mot de passe $pass";
        $messageAssiste = "Cher client, récupérez désormais votre monnaie sur l’application GamPay en effectuant la transaction de votre choix : Achat crédit, Transfert mobile, etc...";
        if ($type == 'cadeau') {
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "'.$whatsapp.'",
                      "messageId": "'.$messageId.'",
                      "content": {
                        "templateName": "cadeau_rm",
                        "templateData": {
                          "body": {
                            "placeholders": ["'.$messageCadeau.'"]
                          },
                         "header": {
                            "type": "IMAGE",
                            "mediaUrl": "https://gampay.org/image_flow/ussd.jpg"
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "'.$whatsapp.'"},
                            {"type": "QUICK_REPLY", "parameter": "assistance"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                    }
                  ]
                }',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            ));
        } else {
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "'.$whatsapp.'",
                      "messageId": "'.$messageId.'",
                      "content": {
                        "templateName": "accompagnement_rm",
                        "templateData": {
                          "body": {
                            "placeholders": ["'.$messageAssiste.'"]
                          },
                         "header": {
                            "type": "VIDEO",
                            "mediaUrl": "https://gampay.org/video_flow/gam-mobile_money.mp4"
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "'.$whatsapp.'"},
                            {"type": "QUICK_REPLY", "parameter": "assistance"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                    }
                  ]
                }',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            ));
        }

        $result = curl_exec($curl);
        //return $result;
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if ($response['messages'][0]['status']['groupName'] == 'PENDING') {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                return 10;
            }
        } else {
            return 100;
        }
        // Close handle
        curl_close($curl);
    }

    public function sendForfaitCadeau($phoneclient){
        $customer= Customer::where('phoneclient', $phoneclient)->first();
        $trans = new Historiquetrans([
            'operation' => 'achat_forfait',
            'reference' => 'cadeau_ussd',
            'etat' => 'atraiter',
            'numclient' => $phoneclient,
            'phonevendeur' => $phoneclient,
            'content' => '500',
            'montant'=> '1000',
            'montant_sans_frais' => '1000',
            'frais' => '0',
            'solde' => $customer->solde,
            'id_customer' => $customer->id,
            'origine_operation' => "system"
        ]);
        $trans->save();
        $customer->update([
            'option1'=> $customer->option1.'_giftUssdOk'
        ]);
        return 1;
    }

    public function sendWhatsappCustomerUssd($whatsapp, $partage)
    {
        $numWhatsApp = $whatsapp;
        $messageId1 = 'ussdinstalle/' . time();
        $messageId = 'ussdpartagefilleul/' . time();
        $message1 = "Bonjour cher client, La fidélité se célèbre chez GAM Gabon.💃💃💃💃💃 C’est avec grand plaisir que nous vous annonçons que vous faites partie de nos clients VIP 👑👑 A cet effet, nous tenions à vous exprimer notre reconnaissance en vous offrant ce magnifique cadeau.🎁 Récupérer le en accédant à notre application mobile GamPay";

        $message2 = "Bonjour";

        if (empty($partage)) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>  '{
                               "messages": [
                                {
                                  "from": "24102535748",
                                  "to": "' . $numWhatsApp . '",
                                  "messageId": "' . $messageId1 . '",
                                  "content": {
                                    "templateName": "cadeau_ussd",
                                    "templateData": {
                                      "body": {
                                        "placeholders": ["' . $message1 . '"]
                                      },
                                     "header": {
                                        "type": "IMAGE",
                                        "mediaUrl": "https://gampay.org/image_flow/ussd-cadeau-with-Netflix.jpg"
                                      },
                                      "buttons": [
                                        {"type": "URL", "parameter": "' . $numWhatsApp . '"},
                                        {"type": "QUICK_REPLY", "parameter": "assister"}
                                      ]
                                    },
                                    "language": "fr"
                                  },
                                  "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                                }
                              ]
                            }',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            ));
            curl_exec($curl);
        } else {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>  '{
                               "messages": [
                                {
                                  "from": "24102535748",
                                  "to": "' . $numWhatsApp . '",
                                  "messageId": "' . $messageId . '",
                                  "content": {
                                    "templateName": "partage_cadeau_ussd",
                                    "templateData": {
                                      "body": {
                                        "placeholders": ["' . $message2 . '"]
                                      },
                                     "header": {
                                        "type": "IMAGE",
                                        "mediaUrl": "https://gampay.org/image_flow/ussd-cadeau.jpg"
                                      },
                                      "buttons": [
                                        {"type": "URL", "parameter": "' . $whatsapp . '&option=partage"},
                                        {"type": "QUICK_REPLY", "parameter": "assister"}
                                      ]
                                    },
                                    "language": "fr"
                                  },
                                  "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                                }
                              ]
                            }',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                    'Content-Type: application/json',
                    'Accept: application/json'
                ),
            ));
            curl_exec($curl);
        }

        return 1;
    }

    public function getMenu(Request $request){
        $customer = Customer::where('id', $request->customer)->orWhere('phoneclient',$request->customer)->first();
        if($customer){
            $myMenu = DB::select("select * from menus m inner join menu_customer mc where m.id = mc.menu and mc.customer ='".$customer->id."' and mc.status = 1 and m.status = 1 and mc.deleted_at is null order by score desc ");
            if(count($myMenu)>0){
                return response()->json([
                    'statut'=> true,
                    'body' => $myMenu
                ]);
            }else{
                $menus = Menu::all();
                foreach ($menus as $menu){
                    $insert = 1;
                    if(in_array($menu->code, ['recharge', 'rm']) && empty($customer->username)){
                        $insert = 0;
                    }
                    if($insert == 1){
                        $verifMenu = MenuCustomer::where('customer', $customer->id)->where('menu', $menu->id)->get();
                        if(count($verifMenu) == 0){
                            $newMenu = new MenuCustomer([
                                'customer'=> $customer->id,
                                'menu' => $menu->id,
                                'code'=>$menu->code,
                                'status'=> 1,
                                'score' => ($customer->username == 'partenaire' && in_array($menu->code, ['rm', 'recharge', 'gam']))? 1:0
                            ]);
                            $newMenu->save();
                        }
                    }
                }
                $myMenu = DB::select("select * from menus m inner join menu_customer mc where m.id = mc.menu and mc.customer ='".$customer->id."' and mc.status = 1 and m.status = 1 and mc.deleted_at is null order by score desc ");
                return response()->json([
                    'statut'=> true,
                    'body' => $myMenu
                ]);
            }
        }
        return response()->json([
            'statut'=> false,
            'body' => []
        ]);
    }

    public function getPayment(Request $request){
        $customer = Customer::where('id', $request->customer)->orWhere('phoneclient',$request->customer)->first();
        if($customer){
            $myPayment = DB::select("select * from payments p inner join payment_customer pc WHERE p.id = pc.payment and pc.customer ='".$customer->id."' and p.status =1 and pc.status =1 and pc.deleted_at is null order by pc.score desc ");
            if(count($myPayment)>0){
                return response()->json([
                    'statut'=> true,
                    'body' => $myPayment
                ]);
            }else{
                $payments = Payment::all();
                foreach ($payments as $payment){
                    $verifPayment = PaymentCustomer::where('customer', $customer->id)->where('payment', $payment->id)->get();
                    if(count($verifPayment) == 0){
                        $newPayment = new PaymentCustomer([
                            'customer'=> $customer->id,
                            'payment' => $payment->id,
                            'code'=> $payment->code,
                            'status' => 1,
                            'score' => ($customer->username == 'partenaire' && $payment->code == 'gam' )? 10:0
                        ]);
                        $newPayment->save();
                    }
                }
                $myPayment = DB::select("select * from payments p inner join payment_customer pc WHERE p.id = pc.payment and pc.customer ='".$customer->id."' and p.status =1 and pc.status =1 and pc.deleted_at is null order by pc.score desc ");
                return response()->json([
                    'statut'=> true,
                    'body' => $myPayment
                ]);
            }
        }
        return response()->json([
            'statut'=> false,
            'body' => []
        ]);
    }

    public function setPayment(Request $request){
        if(!empty($request->code)){
            $paymentCustomer =  PaymentCustomer::where('customer', $request->customer)->where('code', $request->code)->first();
        }elseif (!empty($request->payment)){
            $paymentCustomer = PaymentCustomer::where('customer', $request->customer)->where('payment', $request->payment)->first();
        }

        if(!empty($paymentCustomer)){
            PaymentCustomer::where('id', $paymentCustomer->id)->update([
                'score'=>$paymentCustomer->score + 1,
                'last' => date('Y-m-d H:i:s'),
                'last_amount' => empty($request->amount)? 0 : intval($request->amount)
            ]);
            if($paymentCustomer->code != 'gam'){
                $gam = PaymentCustomer::where('customer', $request->customer)->where('code', 'gam')->first();
                PaymentCustomer::where('id', $gam->id)->update([
                    'score'=>$gam->score + 1,
                ]);
            }
        }
        return $paymentCustomer;
    }

    public function setMenu(Request $request){
        if(!empty($request->code)){
            $menuCustomer =  MenuCustomer::where('customer', $request->customer)->where('code', $request->code)->first();
        }elseif (!empty($request->menu)){
            $menuCustomer = MenuCustomer::where('customer', $request->customer)->where('menu', $request->code)->first();
        }
        if(!empty($menuCustomer)){
            MenuCustomer::where('id', $menuCustomer->id)->update([
                'score'=>$menuCustomer->score + 1,
                'last' => date('Y-m-d H:i:s')
            ]);
        }
        return $menuCustomer;
    }

    public function getIdForIos($id, $option2){
        // vider le option2 du client
        Customer::where('id', $id)->update([
            'option2' => null,
        ]);

        $customer = 0;
        $preteur = null;
        $inactifCustomer = Customer::whereNull('last_connexion_at')->where('option2', '<', 1000)->first();
        if($inactifCustomer){
            $preteur = $inactifCustomer;
            $newOption2 = $inactifCustomer->option2;
            $customer = $inactifCustomer->id;
        }else{
            $inactifCustomerAndroid = Customer::where('avatar', 'android')->where('option2', '<', 1000)->first();
            if($inactifCustomerAndroid){
                $preteur = $inactifCustomerAndroid;
                $newOption2 = $inactifCustomerAndroid->option2;
                $customer = $inactifCustomerAndroid->id;
            }
        }

        if($customer != 0){
            // Changer le option2 du preteur
            Customer::where('id', $customer)->update([
                'option2' => $option2,
            ]);
            // Remplir le option2 du client
            Customer::where('id', $id)->update([
                'option2' => $newOption2,
            ]);
        }
    }


    public function sendTemplateRecevedMypay($whatsapp, $message1, $message2, $sendTemplateRecevedMypay){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>  '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "' . $whatsapp . '",
                      "messageId": "' . $sendTemplateRecevedMypay . '",
                      "content": {
                        "templateName": "mypay_new_filleul",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message1 . '","' . $message2 . '"]
                          },
                         "header": {
                            "type": "IMAGE",
                            "mediaUrl": "https://gampay.org/image_flow/mypay_filleul.jpg"
                          },
                          "buttons": [
                            {"type": "QUICK_REPLY", "parameter": "applicationall"},
                            {"type": "QUICK_REPLY", "parameter": "assistanceall"},
                            {"type": "QUICK_REPLY", "parameter": "faq"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                    }
                  ]
                }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        curl_exec($curl);

        $result = curl_exec($curl);
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if ($response['messages'][0]['status']['groupName'] == 'PENDING') {
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

    public function checkNumberUseWhatsappInCustomer($numero, $type)
    {
        if (strlen($numero) > 9) {
            $num = substr($numero, 1);
        } elseif (strlen($numero) < 9) {
            $num = '0' . $numero;
        } else {
            $num = $numero;
        }

        if ($type == '') {
            $numSansZero = substr($num, 1);
            $whatsappVerify = "241$numSansZero";
        } else {
            $whatsappVerify = $numero;
        }
        $numwhat = WhatsAppVerify::where('number', $whatsappVerify)->first();
        if ($numwhat) {
            if ($numwhat->status == "true") {
                return $whatsappVerify;
            } else {
                return '0';
            }
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://zylalabs.com/api/926/whatsapp+number+checker+api/743/number+checker?number=$whatsappVerify");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer 3785|LrBx4lTuGHb9YarTqygv2ukzzmfRGIXZozfaxosa"));
            $result = curl_exec($ch); // Check HTTP status code
            if (!curl_errno($ch)) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($http_code == 200 ||  $http_code == 201) {
                    $response = json_decode($result, true);
                    if ($response['numberstatus'] == true) {
                        $andWhat = new WhatsAppVerify([
                            'number' =>  "$whatsappVerify",
                            'status' => "true"
                        ]);
                        $andWhat->save();
                        return $whatsappVerify;
                    } else {
                        $andWhat = new WhatsAppVerify([
                            'number' =>  "$whatsappVerify",
                            'status' => "false"
                        ]);
                        $andWhat->save();
                        return '0';
                    }
                } else {
                    return '0';
                }
            } else {
                return '0';
            }
            // Close handle
            curl_close($ch);
        }
    }

    public function templateHistoryMypay($whatsapp, $message, $messageId){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>  '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "' . $whatsapp . '",
                      "messageId": "' . $messageId . '",
                      "content": {
                        "templateName": "history_transaction",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "IMAGE",
                            "mediaUrl": "https://gampay.org/image_flow/mypay_filleul.jpg"
                          },
                          "buttons": [
                            {"type": "QUICK_REPLY", "parameter": "friends"},
                            {"type": "QUICK_REPLY", "parameter": "historyUssd"},
                            {"type": "QUICK_REPLY", "parameter": "applicall"},
                            {"type": "QUICK_REPLY", "parameter": "assistall"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "http://gampay.app/gamclients/public/api/recupCallBack"
                    }
                  ]
                }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        curl_exec($curl);

        $result = curl_exec($curl);
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if ($response['messages'][0]['status']['groupName'] == 'PENDING') {
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

    public function sendTemplateCustomerUssd($trans, $account){

        $oldTrans = GamRechargeHist::where('phonevendeur', $trans->phonevendeur)->where('id', '<', $trans->id)->first();
        if(empty($oldTrans)){
            $whatsapp = $this->checkNumberUseWhatsappInCustomer($trans->phonevendeur, '');
            if($whatsapp == '0'){
                $whatsapp = $this->checkNumberUseWhatsappInCustomer($trans->numeroclient, '');
            }
            if($whatsapp != '0'){
                $montant = intval($trans->montant) - 100;
                if($trans->montant == '100'){
                    $montant ='80';
                }
                $message1 = "Félicitations ! Vous venez d'envoyer $montant Fcfa de crédit au 0$trans->numeroclient";
                $message2 = "Depuis quelle province utilisez vous nos services ?";
                $sendTemplateRecevedMypay = 'newUssd'. time();
                $this->sendTemplateRecevedMypay($whatsapp, $message1, $message2, $sendTemplateRecevedMypay);
                return 1;
            }else{
                return 'pas de whatsapp nouveau $whatsapp';
            }
        }else{
            $customerkyc = Kyc::where('sms', 'like', "%template_mypay%")->whereIn('phonevendeur', [$trans->phonevendeur, '0'.$trans->phonevendeur])->first();
            if (empty($customerkyc)) {
                $fiveNumbers = DB::select("select distinct(numeroclient) from gamrechargehist g where phonevendeur ='" . $trans->phonevendeur . "' and port ='6A' and numeroclient is not null order by id desc");
                if (count($fiveNumbers) >= 3) {
                    return $this->getFiveNumberCustomerUssd($fiveNumbers, $trans->phonevendeur);

                    if (empty($account)) {
                        $newAccount = new Customer([
                            'nom' => 'client',
                            'prenom' => 'GAM',
                            'pays' => '241',
                            'phoneclient' => $trans->phonevendeur,
                            'mdpclient' => '1234',
                            'option1' => 'ussd',
                            'code_confirm' => 'ussd',
                            'status' => 1,
                            'contry_code' => 'GA',
                            'otp' => 'open'
                        ]);
                        $newAccount->save();
                        $customer = $newAccount;
                        $whatsapp = $this->checkNumberUseWhatsappInCustomer($trans->phonevendeur, '');
                    } else {
                        $customer = $account;
                        if (!empty($account->whatsapp)) {
                            $whatsapp = $account->whatsapp;
                        } else {
                            $whatsapp = $this->checkNumberUseWhatsappInCustomer($trans->phonevendeur, '');
                        }
                    }

                    if ($whatsapp != '0') {
                        Customer::where('id', $customer->id)->update([
                            'customer_type' => 'ussd',
                            'whatsapp' => $whatsapp
                        ]);

                        $montant = intval($trans->montant) - 100;
                        if ($trans->montant == '100') {
                            $montant = '80';
                        }
                        $message = "Félicitations ! Vous venez d'envoyer $montant Fcfa de crédit au 0$trans->numeroclient";
                        $messageId = 'parrainmypay' . $whatsapp . '' . time();
                        $sendTemplate =  $this->TemplateHistoryMypay($whatsapp, $message, $messageId);
                        if($sendTemplate == 1){
                            $this->setOrAddKycIn($whatsapp, 'credit_ussd', 'emeteur', 'ussd_robot', $messageId, $trans->phonevendeur, 'template_mypay');
                            GamRechargeHist::where('id', $trans->id)->update([
                                'param7' =>'Template'
                            ]);
                            return 'ok';
                        }else{
                            return 'Erreur envoi template';
                        }

                    } else {
                        return 'pas de whatsapp ancien $whatsapp';
                    }
                }
            }else{
                return 'Déjà envoyé';
            }
        }
        return 1;
    }

    Public function testSendNewTemplateUssd(Request $request){
        $trans = GamRechargeHist::where('id', $request->trans)->first();
        if($trans) {
            $customerApp = customer::where('phoneclient', $trans->phonevendeur)->orWhere('phonenumber_assoc_1', $trans->phonevendeur)->first();
            return $this->sendTemplateCustomerUssd($trans, $customerApp);
        }
        return [];
    }

    //création dans KYC
    public function setOrAddKycIn($whatsapp, $produit, $type, $provenance, $messageId, $phonevendeur, $typeSms)
    {
        $kycData = Kyc::whereIn('phonevendeur', [$phonevendeur, '0'.$phonevendeur])->first();
        if (!empty($kycData)) {
            $newProduit = $kycData->produits;
            if ($type != 'receveur') {
                if (!empty($kycData->produits)) {
                    $newProduit = $kycData->produits . "/$produit";
                } else {
                    $newProduit = $produit;
                }
            }

            Kyc::where('id', $kycData->id)->update([
                'sms' => (empty($kycData->sms)) ? $typeSms : $kycData->sms . "/$typeSms",
                'idwhatsapp' => (empty($kycData->idwhatsapp)) ? $messageId : $kycData->idwhatsapp . "/$messageId",
                'produits' => $newProduit,
            ]);
        } else {
            $newProduit = $produit;
            if ($type == 'receveur') {
                $newProduit = null;
            }
            $saveKycReceveure = new Kyc([
                'phonevendeur' => ($produit == 'credit_ussd' && $type == 'receveur') ? "0$phonevendeur" : $phonevendeur,
                'whatsapp' => $whatsapp,
                'produits' => $newProduit,
                'sms' => $typeSms,
                'provenance' => $provenance,
                'contacte_via' => 'scyd',
                'idwhatsapp' => $messageId
            ]);
            $saveKycReceveure->save();
        }
        return 1;
    }

    public function getFiveNumberCustomerUssd($fiveNumbers, $phone){
        $tabTrans = []; $tabNums = [];
        foreach ($fiveNumbers as $number){
            $trans = GamRechargeHist::where('phonevendeur', $phone)->where('port', '6A')->where('numeroclient', $number->numeroclient)->orderBy('id', 'desc')->first();
            $montant = intval($trans->montant -100);
            $num = $number->numeroclient;
            if(strlen($num)== 8){
                $num = '0'. $number->numeroclient;
            }
            $trans = $montant. ' vers le '.$num;
            array_push($tabTrans, $trans);
            array_push($tabNums, $num);
        }
        $program = ProgramSubscription::where('phoneclient', $phone)->first();
        if($program){
            ProgramSubscription::where('phoneclient', $phone)->update([
                'param2' => json_encode($tabNums),
                'param3' => json_encode($tabTrans)
            ]);
        }else{
            $newProgram = new ProgramSubscription([
                'phoneclient' => $phone,
                'program_name' => 'ussd',
                'param2' => json_encode($tabNums),
                'param3' => json_encode($tabTrans)
            ]);
            $newProgram->save();
        }

        return [$tabTrans,$tabNums ];
    }

    public function sendTemplateMessageOldUssdGoodTrafic($whatsapp, $phonevendeur, $amount, $numClient){
        $messageId = "new_communication_ussd_image".time();
        if($numClient != '0'){
            $message = "Votre achat crédit de $amount F pour le $numClient a été effectué avec succès. Découvrez encore plus de produits & services sur notre chaîne WhatsApp";
        }else{
            $message = "Vous avez reçu $amount F de crédit du $phonevendeur. Découvrez encore plus de produits & services sur notre chaîne WhatsApp";
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "' . $whatsapp . '",
                      "messageId": "' . $messageId . '",
                      "content": {
                        "templateName": "new_communication_ussd_image",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "IMAGE",
                            "mediaUrl": "https://gampay.org/image_flow/mise_en_statut_ussd.jpeg"
                          },
                           "buttons": [
                            {"type": "URL", "parameter": "' . $whatsapp . '&partage=partage&messageId=' . $messageId . '"},
                            {"type": "URL", "parameter": "' . $whatsapp . '&rm=rm&messageId=' . $messageId . '"},
                            {"type": "QUICK_REPLY", "parameter": "application_ussd"},
                            {"type": "QUICK_REPLY", "parameter": "assiste_ussd"},
                            {"type": "QUICK_REPLY", "parameter": "later_ussd"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "https://gampay.org/gamclients/public/api/recupCallBack"
                    }
                  ]
                }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $result = curl_exec($curl);
        //return $result;
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if ($response['messages'][0]['status']['groupName'] == 'PENDING') {
                    return 1;
                } else {
                    return $result;
                }
            } else {
                return $result;
            }
        } else {
            return $result;
        }
        curl_close($curl);
    }

    public function testNewTemplateUssd(){
        return $this->sendTemplateMessageOldUssdGoodTrafic('24174582442', '074582442', '600', '0');
    }

    public function getDataTransConfirm($ref){
        $data = GamRechargeHist::where('reference', $ref)->where('etat', 'sent	tonumbergam')->first();
        if($data){
            if(str_contains(strval($data->content), 'solde')){
                if($data->sender == 'Moov Money'){
                    $debut = explode('-', $data->content);
                    $partName = explode('le 20', $debut[1]);
                }else if($data->sender == 'AirtelMoney'){
                    $debut = explode(',', $data->content);
                    $partName = explode('.', $debut[1]);
                }
                return $partName[0];
            }
        }
        return '50';
    }

    /*************** login gamview ***************/
    public function sendWhatsappGamView(Request $request){
        $phone = $this->getNumByWhatsApp($request->phone);
        $message = "Confirmez la création de votre compte avec le $phone";
        $messageId = "valideAccountForGamView".time();
        //$message = $request->message;
        $whatsapp = $request->phone;
        $urlBtn = "?vaw=$request->account";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "' . $whatsapp . '",
                      "messageId": "' . $messageId . '",
                      "content": {
                        "templateName": "logingamview",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "' . $urlBtn . '"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "https://gampay.org/gamclients/public/api/recupCallBack"
                    }
                  ]
                }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: App 6528aa653d96610451e2d8054ecb3358-1207d4b9-3758-42e8-9097-19e50a29f00d',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $result = curl_exec($curl);
        $finalResponse = 0;
        //return $result;
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if ($response['messages'][0]['status']['groupName'] == 'PENDING') {
                    $finalResponse = 1;
                }
            }
        }
        // Close handle
        curl_close($curl);
        return response()->json([
            'statut'=> true,
            'body'=> $finalResponse
        ]);

    }


    public function sendWhatsappGamViewShare(Request $request){
        $message = $request->message;
        $messageId = "shareGamView".time();
        $whatsapp = $request->phone;
        $urlBtn = "$request->share&u=$request->user&v=$request->status";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://kl2g1.api.infobip.com/whatsapp/1/message/template',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                   "messages": [
                    {
                      "from": "24102535748",
                      "to": "' . $whatsapp . '",
                      "messageId": "' . $messageId . '",
                      "content": {
                        "templateName": "gamview_shared",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "' . $urlBtn . '"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "https://gampay.org/gamclients/public/api/recupCallBack"
                    }
                  ]
                }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: App 6528aa653d96610451e2d8054ecb3358-1207d4b9-3758-42e8-9097-19e50a29f00d',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));

        $result = curl_exec($curl);
        $finalResponse = 0;
        //return $result;
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                if ($response['messages'][0]['status']['groupName'] == 'PENDING') {
                    $finalResponse = 1;
                }
            }
        }
        // Close handle
        curl_close($curl);
        return response()->json([
            'statut'=> true,
            'body'=> $finalResponse,
            'track' => $result
        ]);
    }

    public function getInfoCustomerFlowMeta(Request $request){
        $customer = Customer::where('phoneclient',$request->phone)->first();
        if($customer){
            return response()->json([
                'statut'=> true,
                'nom'=> $customer->nom .' '. $customer->prenom,
                'solde' => $customer->solde .' FCFA'
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'nom'=> 'Pas trouvé',
                'solde' =>'0 FCFA'
            ]);
        }
    }

}
