<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Notif;
use App\Models\WhatsAppVerify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AlertesController extends Controller
{
    /**** Message whatsapp ****/
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

    /**** FIN Message whatsapp ****/


    /**** Notifications Firebase ****/
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
    /*** Fin Notifications Firebase ****/
}
