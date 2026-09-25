<?php

namespace App\Http\Controllers;

use App\Models\Carte;
use App\Models\Cadeau;
use App\Models\Contact;
use App\Models\Client2;
use App\Models\Customer;
use App\Models\Communication;
use App\Models\ConfirmTrans;
use App\Models\Diffusion;
use App\Models\ForfaitInternational;
use App\Models\CustomerComplement;
use App\Models\ErrorApp;
use App\Models\GamElectriciteHist;
use App\Models\GamRechargeHist;
use App\Models\Historiquetrans;
use App\Models\Kyc;
use App\Models\ProgramSubscription;
use App\Models\Notif;
use App\Models\Reclammation;
use App\Models\Soldes;
use App\Models\TemoinPartner;
use App\Models\WhatsAppVerify;
use App\Models\WhatsAppBackUp;
use App\Models\Wifi;
use App\Models\ProjetAction;
use App\Models\Projet;
use App\Models\ForfaitDataCredit;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Infobip\Api\SendSmsApi;
use Infobip\Configuration;
use Infobip\Model\SmsAdvancedTextualRequest;
use Infobip\Model\SmsDestination;
use Infobip\Model\SmsTextualMessage;

class ServicesController extends Controller
{
    
    /* alerte whatsapp */
    
    public function sendWhatsappMessageInGroupWaAPI($service, $message)
    {
        switch ($service) {
            case 'satisfaction':
                $sender = "+24104471892";
                break;
            case 'rm':
                $sender = "+24104767855";
                break;
            case 'sigma':
                $sender = "+24102195661";
                break;
                // Groupe traitement operation
            case 'traitement':
                $sender = "120363416560008249@g.us";
                break;
                // Groupe GAM
            case 'gam':
                $sender = "120363403402224078@g.us";
                break;
                // Groupe travaux objectif
            case 'work':
                $sender = "120363401467436387@g.us";
                break;
                 // Groupe service client
            case 'service_client':
                $sender = "120363402879948253@g.us";
                break;
                // Groupe dept international
            case 'international':
                $sender = "120363400142570137@g.us";
                break;
                 // Groupe transfert GamPay
            case 'transfert_gampay':
                $sender = "120363403716090826@g.us";
                break;
            default:
                $sender = "120363415646127499@g.us";
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://gate.whapi.cloud/messages/text',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
             "typing_time": 0,
             "to": "' . $sender . '",
             "body": "' . $message . '"
                }',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer coStlX5jmah3boTuygUEaqRwoECbZbPu',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));
        $result = curl_exec($curl);
        return $result ;
        curl_close($curl);
    }
    
    /* end alerte whatsapp */
    public function sendSms(Request $request){

        if(!empty($request->id)){
            switch ($request->produit){
                case 'challenge':
                    Customer::find($request->id)->update([
                        'locked_at'=> date("Y-m-d"),
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
                case 'reclamation':
                    Reclammation::where('id',$request->id)->update([
                        'param3'=> now(),
                    ]);
                    break;
            }
        }


        $BASE_URL = "https://kl2g1.api.infobip.com";
        $API_KEY = "1080734913520e26498f9801bbf3296d-818caabf-a1ea-44eb-951f-5bad49dfd10b";

        $result = substr($request->phone, 0, 1);
        if($result == '0'){
            $phone = substr($request->phone, 1);
        }else{
            $phone = $request->phone;
        }

        $SENDER = "GAM";
        $RECIPIENT = "241".$phone;
        $MESSAGE_TEXT = $request->message;

        $configuration = (new Configuration())
            ->setHost($BASE_URL)
            ->setApiKeyPrefix('Authorization', 'App')
            ->setApiKey('Authorization', $API_KEY);

        $client = new Client();

        $sendSmsApi = new SendSMSApi($client, $configuration);
        $destination = (new SmsDestination())->setTo($RECIPIENT);
        $message = (new SmsTextualMessage())
            ->setFrom($SENDER)
            ->setText($MESSAGE_TEXT)
            ->setDestinations([$destination]);

        $request = (new SmsAdvancedTextualRequest())->setMessages([$message]);

        try {
            $smsResponse = $sendSmsApi->sendSmsMessage($request);
            return 1;
        } catch (Throwable $apiException) {
            return 0;
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
                $tokenVendeur = $customer['unlock_token'];
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
                $tokenReceive = $customer['unlock_token'];
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
                $message= "Vous avez éffectuez un transfert de $montant $challenge $nom2" ;
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

    public function mailPass(){
        $client = new Client([
            'base_uri' => "https://kl2g1.api.infobip.com/",
            'headers' => [
                'Authorization' => "App 1080734913520e26498f9801bbf3296d-818caabf-a1ea-44eb-951f-5bad49dfd10b",
                'Content-Type' => 'multipart/form-data',
                'Accept' => 'application/json',
            ]
        ]);

        $response = $client->request(
            'POST',
            'email/2/send',
            [
                RequestOptions::MULTIPART => [
                    ['name' => 'from', 'contents' => "GAMGabon@selfserviceib.com"],
                    ['name' => 'to', 'contents' => "gamgabon@gmail.com"],
                    ['name' => 'subject', 'contents' => 'This is a sample email subject'],
                    ['name' => 'text', 'contents' => 'This is a sample email message.'],
                    // example how to attach a file
                    /*[
                        'Content-type' => 'multipart/form-data',
                        'name' => 'file',
                        'contents' => fopen('/tmp/testfile.pdf', 'r'),
                        'filename' => 'testfile.pdf',
                    ],*/
                ],
            ]
        );

        return ["HTTP code: " . $response->getStatusCode() . PHP_EOL,
            "Response body: " . $response->getBody()->getContents() . PHP_EOL];
    }

    public function recupTransApis(Request $request){
        $now = date("Y-m-d");
        if($request->module == 'bfm'){
            $tran = Historiquetrans::where('id', $request->trans)->first();
        }else{
            $tran = Historiquetrans::where('reference', $request->ref)->first();
        }

        if($tran){
            $verifTrans = Historiquetrans::where('phonevendeur', $tran->phonevendeur)->where('operation','paiement_partenaire')->whereIn('etat',['follow', 'follow'])->where('created_at','like',"%{$now}%")->get();
            $client = Customer::where('id', intval($tran->id_customer))->get();// pour que ça fasse comment dans l'authentification!
            $customer = Customer::where('id', intval($tran->id_customer))->first();

            if(count($verifTrans)>=100 && empty($request->module)){
                return response()->json([
                    'statut' => true,
                    'link'=> '',
                    'am'=> '*150*3*10*746*',
                    'moov'=> '*555*5*7*746*',
                    'body'=> 'max',
                    'message'=> 'max'
                ]);
            }else if($customer->failed_attempts != null && $customer->failed_attempts >= 3 && empty($request->module)){
                return response()->json([
                    'statut' => true,
                    'link'=> '',
                    'am'=> '*150*3*10*746*',
                    'moov'=> '*555*5*7*746*',
                    'body'=> 'mdp',
                    'message'=> 'mdp'
                ]);
            }else if($tran->etat != 'attend'){
                if(in_array($tran->etat, ['atraiter', 'en_attente'])){
                    $etat = 'in_progress';
                }else if(in_array($tran->etat, ['confirme', 'CONFIRMEE'])) {
                    $etat = 'purshase';
                }else{
                    $etat = 'cancel';
                }
                return response()->json([
                    'statut' => true,
                    'link'=> '',
                    'am'=> '*150*3*10*746*',
                    'moov'=> '*555*5*7*746*',
                    'body'=> 'start',
                    'message'=> $etat
                ]);
            }else{

                if($request->module == 'bfm'){
                    $partenaire = Customer::where('phoneclient', intval($tran->numclient))->first();
                }else{
                    Historiquetrans::where('id',$tran->id)->update(['param3'=> 'in_progress']);
                    $partenaire = Customer::where('id', intval($tran->param1))->first();
                }

                return response()->json([
                    'statut' => true,
                    'customer' => $client,
                    'link'=> '',
                    'am'=> '*150*3*10*746*',
                    'moov'=> '*555*5*7*746*',
                    'body'=>[
                        'message' => 'Transaction ok',
                        'montantFrais' => $tran->montant,
                        'id' => $tran->id,
                        'ref' => $tran->reference,
                        'frais' => $tran->frais,
                        'montantSansFrais' => $tran->montant_sans_frais,
                        'operation' => $tran->operation,
                        'devise' => $request->module == 'bfm'? 'FCFA' : $tran->param5,
                        'montantEntre' => $request->module == 'bfm'?$tran->montant : $tran->param9,
                        'phonePartner' => $partenaire->phoneclient,
                        'nomPartner' => $partenaire->nom.' '.$partenaire->prenom,
                        'avatarPartner' => $tran->param3,
                        'module' => $request->module
                    ]
                ]);
            }

        }else{
            return response()->json([
                'statut'=> false,
                'message'=> 'Transaction inexistante'
            ]);
        }
    }

    public function failedMdp(Request $request){
        $client = Customer::where('id', intval($request->id))->orWhere('phoneclient', $request->phone)->first();
        if($client){
            Customer::where('id', $client->id)->update([
                'failed_attempts'=>$client->failed_attempts == null? 1 : $client->failed_attempts + 1
            ]);
        }
        return 1;
    }

    public function updatesoldes(Request $request){
        switch ($request->operation){
            case 'rendu_monnaie_simple' :
                $produit = $request->operateur == 'MOBICASH'? 'MM':'AM';
                break;
            case 'achat_credit' :
                $produit = $request->operateur == 'AIRTEL_GA'? 'CA':'CL';
                break;
            case 'achat_forfait' :
                $produit = 'FA';
                break;
            default:
                $produit = 'GAM';
        }

        $soldes = Solde::where('produit',$produit)->first();
        if($soldes){
            Solde::where('id', $soldes->id)-> update([
                'solde' => ($request->traitement == 'debit')?
                    strval( doubleval($soldes->solde) - doubleval($request->montant)) :
                    strval(doubleval($soldes->solde) + doubleval($request->montant))
            ]);

            return 1;
        }
        return 0;
    }

    public function iaCustomerUssd(Request $request){
        $numDefault = "";
        $customer = Customer::where('phoneclient', $request->phone)->first();
        if($customer){// Si le numero a un compte
            if (!empty($customer->phonenumber_assoc_1)) {// Si il a un numero par defaut
                $numDefault = $customer->phonenumber_assoc_1; // numero par defaut = phonenumber_assoc_1 du compte
                $phoneCaract = strval($customer->phonenumber_assoc_1);
                $debut = $phoneCaract[0].$phoneCaract[1];
                if($debut == '07'){
                    $historiqueCredit = new Historiquetrans([
                        'operation' => 'achat_credit',
                        'reference' => $request->ref,
                        'etat' => 'atraiter',
                        'numclient' => $customer->phonenumber_assoc_1,
                        'phonevendeur' => $request->phone,
                        'content'=> 'AIRTEL_GA',
                        'montant' =>strval($request->montant),
                        'montant_sans_frais' => strval($request->montant),
                        'frais' =>'0',
                        'solde' => $customer->solde,
                        'origine_operation' => 'BackOffice',
                        'id_customer' => $customer->id,
                        'param2' =>'GA',
                        'param4' => $customer->pays,
                        'param9' => 'customer',

                    ]);
                    $historiqueCredit->save();
                }else{
                    GamRechargeHist::where('id', $request->id)->update([
                        'numeroclient'=> $customer->phonenumber_assoc_1
                    ]);
                }
            } else {// Si il n'a pas un numero par defaut
                // on recherche le numero le plus utilisé dans gamrechargehist
                $getNumDefault = DB::select("SELECT numeroclient, COUNT(*) as count FROM gamrechargehist g where phonevendeur = '$customer->phoneclient' and port ='6A' and etat like '%a ete recharge avec succes montant%' GROUP BY numeroclient ORDER BY count DESC limit 1");
                if(count($getNumDefault) == 0){//Si on en trouve pas
                    $numDefault = $customer->phoneclient;
                    Customer::where('id', $customer->id)->update([
                        'phonenumber_assoc_1'=>$customer->phoneclient //  numero par defaut = phoneclient du compte
                    ]);
                    $historiqueCredit = new Historiquetrans([
                        'operation' => 'achat_credit',
                        'reference' => $request->ref,
                        'etat' => 'atraiter',
                        'numclient' => $customer->phonenumber_assoc_1,
                        'phonevendeur' => $request->phone,
                        'content'=> 'AIRTEL_GA',
                        'montant' =>strval($request->montant),
                        'montant_sans_frais' => strval($request->montant),
                        'frais' =>'0',
                        'solde' => $customer->solde,
                        'origine_operation' => 'BackOffice',
                        'id_customer' => $customer->id,
                        'param2' =>'GA',
                        'param4' => $customer->pays,
                        'param9' => 'customer',

                    ]);
                    $historiqueCredit->save();
                }else{//Si on trouve un numero habituel
                    foreach ($getNumDefault as $numDefault){
                        $defaultNum = $numDefault['numeroclient'];
                    }
                    // on enregistre le numero par défaut
                    Customer::where('id', $customer->id)->update([
                        'phonenumber_assoc_1'=>$defaultNum //  numero par defaut = numero le plus utilisé
                    ]);
                    // on relance la transaction avec le numero par defaut
                    GamRechargeHist::where('id', $request->id)->update([
                        'numeroclient'=> $customer->phonenumber_assoc_1
                    ]);
                }
            }
            // La communication
            $numNotif = $request->phone;
            $messageNotif = "Cher client, suite à votre erreur, nous avons effectué votre achat crédit de $request->montant sur le $numDefault, votre numero par defaut chez GAM. Personalisez-le sur GamPay http://app.gampay.org";
            Customer::where('id', $customer->id)->update([
                'nom_mobile'=>$messageNotif //  numero par defaut = numero le plus utilisé
            ]);
            Http::get("https://gampay.org/gamclients/public/api/sendSmsGet?phone=$numNotif&message=$messageNotif");
            return 1;
        }else{
            $newCustomer = new Customer([
                'nom'=> 'client',
                'prenom'=> 'GAM',
                'pays'=> '241',
                'phoneclient'=> $request->phone,
                'solde'=> strval($request->montant),
                'mdpclient'=> '1234',
                'option1'=> 'gam',
                'code_confirm'=> 'gam',
                'satus'=>0
            ]);
            if( $newCustomer->save()){
                Http::get('https://gampay.org/gamclients/public/api/recupIdCustomer');
                $recharge = new Historiquetrans([
                    'operation' => 'recharge_compte_gam',
                    'reference' => $request->ref,
                    'etat' => 'CONFIRMEE',
                    'numclient' => $request->phone,
                    'phonevendeur' => $request->phone,
                    'content'=>'GAM',
                    'montant' =>strval($request->montant),
                    'montant_sans_frais' => strval($request->montant),
                    'frais' =>0,
                    'solde' => $request->montant,
                    'origine_operation' => 'BackOffice',
                ]);
                $recharge->save();
                $reclamation = new Reclammation([
                    'type_reclammation'=>'systeme',
                    'objet'=>"Reclamation Ussd",
                    'operation'=>'achat_credit',
                    'statut'=>"new",
                    'phoneclient'=>$request->phone,
                    'phonebeneficiaire'=>$request->numclient,
                    'montant'=>$request->montant,
                    'message'=>'USSD',
                ]);
                $reclamation ->save();
            }

            return 1;
        }
    }

    public function callBackPartnaire(Request $request){
        $ch = curl_init();
        $dataString = json_encode([
            'reference' => $request->reference,
            'amount' => $request->amount,
            'currency' => $request->devise,
            'amount_commission' => $request->amount_commission,
            'commission' => $request->commission,
            'phone' => $request->phone,
            'status' => $request->status,
            'payment_mode' => $request->payment_mode,
        ]);
        curl_setopt($ch, CURLOPT_URL, $request->link);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        return curl_exec($ch);
    }

    public function callBackPartnaireInProgram($reference,$amount,$amount_commission,$commission,$devise,$phone,$status,$link,$payment_mode){
        $ch = curl_init();
        $dataString = json_encode([
            'reference' => $reference,
            'amount' => $amount,
            'amount_commission' => strval($amount_commission),
            'commission' => strval($commission),
            'currency' => $devise,
            'phone' => $phone,
            'status' => $status,
            'payment_mode' => $payment_mode,
        ]);
        curl_setopt($ch, CURLOPT_URL, $link);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        return curl_exec($ch);
    }

    public function devise($amount, $current){
        $newAmount=0;
        switch ($current) {
            case 'USD':
                $newAmount = doubleval($amount) / 611.42;
                break;
            case 'EUR':
                $newAmount = doubleval($amount) / 657.46;
                break;
            case 'XAF':
                $newAmount = number_format($amount /100,2,".");
                break;
            default:
                $newAmount = doubleval($amount);
                break;
        }
        return $newAmount;
    }

    public function valideTransApisPartenaire(Request $request){
        Http::get("https://gampay.app/gamclients/public/api/countScoreCustomerUssd");
        if(!empty($request->ref)){
            $transs = Historiquetrans::where('reference', $request->ref)->get();
        }else if(!empty($request->id)){
            $transs = Historiquetrans::where('id', intval($request->id))->get();
        }else{
            $transs = Historiquetrans::where('operation', 'paiement_partenaire')->whereIn('etat', ['atraiter', 'en_attente'])->orderBy('id', 'desc')->take(5)->get();
        }

        if (count($transs)==0){
            $this->valideTransApisPartenaireAttend();
            return Response()->json([
                'status'=> 404,
                'message'=>'Not found'
            ]);
        }else{
            foreach ($transs as $trans){
                $partenaire =Customer::where('phoneclient', $trans['numclient'])->first();// recuperation compte partenaire
                $reference = $trans['reference'];
                $customer= $trans['phonevendeur'];
                $amount = !empty($trans['param9'])?$trans['param9']: $trans['montant'];
                $currency= $trans['param5'];
                $amount_commission = $this->devise($trans['montant_sans_frais'],$currency);
                $commission = $this->devise($trans['frais'],$currency);
                $payment_mode = $trans['param2'];

                $status='';
                $temoin = TemoinPartner::where('reference', $reference)->get();

                if(count($temoin)==0){
                    $newTemoin = new TemoinPartner([
                        'reference' =>$reference,
                        'montant'=>$trans['montant_sans_frais'],
                        'numclient'=>$trans['numclient'],
                        'phonevendeur'=>$trans['phonevendeur'],
                    ]);
                    $newTemoin->save();
                    if(!empty($newTemoin->id)){
                        Customer::where('id', $partenaire->id)->update([
                            'solde'=>doubleval($partenaire->solde) + doubleval($trans['montant_sans_frais'])
                        ]);
                        Historiquetrans::where('id',$trans['id'])->update([
                            'etat'=>'CONFIRMEE',
                        ]);
                        $status='PURCHASED';
                        if($status=='PURCHASED'){
                            $this->sendNotificationInprogram($customer, $trans['numclient'],'gam_transfert', $amount,'',null, $currency );

                            if(!empty($partenaire->Fonction7)){
                                $calback = $this->callBackPartnaireInProgram($reference,$amount,$amount_commission,$commission,$currency,$customer,$status,$partenaire->Fonction7,$payment_mode);
                                return $calback;
                            }
                        }
                    }else{
                        Historiquetrans::where('id',$trans['id'])->update([
                            'etat'=>'CONFIRMEE',
                        ]);
                    }

                }else{
                    Historiquetrans::where('id',$trans['id'])->update([
                        'etat'=>'CONFIRMEE',
                    ]);
                }
            }
            $this->valideTransApisPartenaireAttend();
            return $transs;
        }
    }
    
    public function valideTransApisPartenaireAttend(){
        $transs = Historiquetrans::where('operation', 'paiement_partenaire')->where('etat', 'attend')->orderBy('id', 'desc')->take(10)->get();
        if (count($transs)==0){
            return Response()->json([
                'status'=> 404,
                'message'=>'Not found'
            ]);
        }else{
            foreach ($transs as $trans){
                $partenaire =Customer::where('phoneclient', $trans['numclient'])->first();// recuperation compte partenaire
                $reference = $trans['reference'];
                $customer= $trans['phonevendeur'];
                $amount = !empty($trans['param9'])?$trans['param9']: $trans['montant'];
                $currency= $trans['param5'];
                $amount_commission = $this->devise($trans['montant_sans_frais'],$currency);
                $commission = $this->devise($trans['frais'],$currency);
                $payment_mode = $trans['param2'];

                $status='';
                if($trans['etat'] =='attend' && date('Y-m-d H:i', strtotime($trans['created_at'].'+ 48 hours')) <= date('Y-m-d H:i')){
                    Historiquetrans::where('id',$trans['id'])->update([
                        'etat'=>'annule'
                    ]);
                    $status='DECLINED';
                }elseif($trans['etat']  =='attend' && $trans['param3']=='error_pass'){
                    Historiquetrans::where('id',$trans['id'])->update([
                        'etat'=>'annule'
                    ]);
                    $status='DECLINED';
                }

                if($status !=''){
                    $messageNotif ='Votre paiement de '.$amount .''.$currency.' vers '. $partenaire->nom. ' '.$partenaire->prenom. ' a échoué';
                    $this->sendNotificationInprogram($customer,$customer,'info', '',$messageNotif,null, null );

                    if(!empty($partenaire->Fonction7)){
                        $calback = $this->callBackPartnaireInProgram($reference,$amount,$amount_commission,$commission,$currency,$customer,$status,$partenaire->Fonction7,$payment_mode);
                        return $calback;
                    }
                }
            }
            return $transs;
        }
    }

    public function insertFailAttempt(Request $request){
        $customer = Customer::where('id', intval($request->customer))->first();
        if($customer){
            customer::where('id', $customer->id)->update([
                'failed_attempts' => 3
            ]);
            $numNotif = $customer->phoneclient;
            $messageNotif ='Votre compte a été desactive ';
            $this->sendNotificationInprogram($numNotif,$numNotif,'info', '',$messageNotif,null, null );
        }
        $trans =  Historiquetrans::where('id',intval($request->trans))->first();
        if($trans){
            customer::where('id', $trans->id)->update([
                'param3' => 'error_pass'
            ]);
        }
    }


    public function storeTokenOrabank(){
        $recupToken = ProgramSubscription::where('id', 34357)->first();
        if(date('Y-m-d H:i', strtotime($recupToken->updated_at.'+ 5 min')) <= date('Y-m-d H:i') || $recupToken->param1 == null){
            $tokenGenerate = Http::get('https://b5625c3a2f10.ngrok.app/gamclients/public/api/storeTokenOrabank')->body();
        }
        /*$token = $this->orabankGetAcces();
        if($token['status'] != 0){
            ProgramSubscription::where('id', 34357)->update([
                'param1' => $token['body']
            ]);
        }*/
        return $recupToken;
    }

    public function storePaymentMode(Request $request){
        Historiquetrans::where('id', $request->trans)->update([
            'param2'=> $request->mode
        ]);
    }

    public function storeErrorApp(Request $request){
        $error =  new ErrorApp([
            'customer' => $request->customer,
            'phoneclient'=> $request->phone,
            'operation'=> $request->operation,
            'message'=> $request->message,
            'payment'=> $request->payment,
            'operateur'=> $request->operateur,
            'montant'=> $request->montant,
            'app'=> $request->app,
            'version'=> $request->version,
            'type_customer'=> $request->type,
        ]);
        $error->save();
        return $error;
    }

    public function disableOrabank(Request $request){
        $cartes = Carte::whereNotIn('treatment',[$request->etat, 'attribution', 'verification', 'paiement'])->where('owner', 'ORABANK')->take(10)->get();
        if(count($cartes)>0){
            foreach ($cartes as $carte){
                Carte::where('id', $carte->id)->update([
                    'treatment' => $request->etat,
                ]);
                $customer= Customer::where('id', $carte->id_customer)->first();
                $numNotif = $customer->phoneclient;
                $messageNotif ="Cher client, les recharges Orabank sur votre application GamPay sont momentanément indisponibles. GAM GABON vous remercie pour votre bonne compréhension";
                $this->sendNotificationInprogram($numNotif,$numNotif,'info', '',$messageNotif,null, null );
            }
        }
        return $cartes;
    }

    public function validePaiementStatus(){
        $strans = Historiquetrans::whereNotIn('etat', ['CONFIRMEE', 'attend'])->where('operation', 'achat_status')->get();
        if($strans){
            foreach ($strans as $stran){
                Status::where('id', intval($stran->content))->update([
                    'status'=>1
                ]);
                Historiquetrans::where('id',$stran->id)->update([
                    'etat'=>'CONFIRMEE'
                ]);

                $message = "Le paiement de votre publication a été éffectué succès";
                $this->sendNotificationInprogram($stran->phonevendeur, $stran->phonevendeur, 'info', '',$message, '', '');

                // Notifier les reférents
                $referents = Customer::where('customer_type', 'referent')->get();
                if($referents){
                    foreach ($referents as $referent){
                        $numNotif = $referent->phoneclient;
                        $messageNotif = 'Une nouvelle publication disponible dans votre zone';
                        // On envoie le code par notification et par sms au propiétaire
                        $this->sendNotificationInprogram($numNotif, $numNotif, 'info', '',$messageNotif, '', '');
                    }
                }
            }
        }
        return $strans;
    }


    public function recupAndDisableCustomerParrainage(Request $request){
        //$filleuls = Customer::where('phoneparent', $request->phone)->where('id', '>', 9948621)->whereNotIn('expirationparrainage', ['off'])->get();
        $filleuls = Customer::where('phoneparent', $request->phone)->whereNotIn('expirationparrainage', ['off'])->get();
        if(count($filleuls)>0){
            foreach ($filleuls as $filleul){
                if(strtotime($filleul['expirationparrainage']) < strtotime(strval(date("Y-m-d")))){
                    Customer::where('id', $filleul['id'])->update([
                        'expirationparrainage' => 'off'
                    ]);
                }
            }

            //$myFilleuls = Customer::where('phoneparent', $request->phone)->where('id', '>', 9948621)->whereNotIn('expirationparrainage', ['off'])->get();
            $myFilleuls = Customer::where('phoneparent', $request->phone)->whereNotNull('expirationparrainage')->whereNotIn('expirationparrainage', ['off'])->orderBy('id', 'desc')->get();
            $dataCustomer = Customer::where('phoneclient', $request->phone)->get();
            if(count($myFilleuls)>0){
                return response()->json([
                    'statut'=> true,
                    'filleuls' => $myFilleuls,
                    'countFilleuls' => count($myFilleuls),
                    'customer' => $dataCustomer,
                    'view' => 10,
                    'indication' => 'Activer la récuperation de vos gains en maintenant une activité mensuelle (au moins 30 transactions)',
                    'quota' => 30
                ]);
            }else{
                return response()->json([
                    'statut'=> false, 'message' => 'Pas de filleuls actifs'
                ]);
            }
        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'Pas de filleuls'
            ]);
        }
    }


    public function recupAndAccumulationGainParrainage(Request $request){

        $customer = Customer::where('phoneclient', $request->phone)->first();
        if(!empty($customer)){
            if(!empty($customer->flp_nbr_transaction)){
                $tabProduits = json_decode($customer->flp_nbr_transaction, true);
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
            $dataCustomer = Customer::where('phoneclient', $request->phone)->get();
            return response()->json([
                'statut'=> true,
                'trans'=> [],
                'customer' => $dataCustomer,
                'solde_parrainage' => $customer->solde_parrainage,
                'flp_nbr_transaction' => [
                    'credit' => $credit,
                    'forfait' => $forfait,
                    'visa' => $visa,
                    'transfert' => $transfert,
                    'es' => $es,
                    'forfaitInternational' => $forfaitInternational,
                    'view' => $view,
                    'ussd' => $ussd,
                    'edan' => $edan
                ],
                'view' => 10,
                'indication' => 'Activer la récuperation de vos gains en maintenant une activité mensuelle (au moins 30 transactions)',
                'quota' => 30
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'compte inexistant'
            ]);
        }
    }

    public function recupAndAccumulationGainParrainageOnly(Request $request){
        $customer = Customer::where('phoneclient', $request->phone)->first();
        if($customer){

            if(!empty($customer->flp_nbr_transaction)){
                $tabProduits = json_decode($customer->flp_nbr_transaction, true);
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
            $transFilleuls = DB::select("select * from historiquetrans h WHERE h.phonevendeur in (select phoneclient from customer where phoneparent = '$request->phone' and expirationparrainage <> 'off' ) and h.code_validation is null and etat in ('confirme', 'CONFIRMEE') and deleted_at is null");
            $edans = DB::select("select * from gam_electricite_hist h WHERE h.num_client in (select phoneclient from customer where phoneparent = '$request->phone' and expirationparrainage <> 'off' ) and h.param3 is null and etat in ('confirme', 'CONFIRMEE') and deleted_at is null");
            $ussds = DB::select("select * from gamrechargehist h WHERE h.phonevendeur in (select phoneclient from customer where phoneparent = '$request->phone' and expirationparrainage <> 'off' ) and h.param3 ='param3' and deleted_at is null and (h.etat like '%a ete recharge avec succes%' or h.etat = '{data_status:recharge  effectuee}')");

            if(count($transFilleuls)>0 || count($edans)>0 || count($ussds)>0){

                if(count($transFilleuls)>0){
                    foreach ($transFilleuls as $tran){
                        switch ($tran->operation) {
                            case 'achat_credit':
                                $credit = $credit + intval($tran->frais);
                                break;

                            case 'achat_forfait':
                                $forfait = $forfait + intval($tran->frais);
                                break;

                            case 'recharge_visa_uba':
                                $visa = $visa + intval($tran->frais);
                                break;

                            case 'transfert_mobile': case 'transfert_visa':
                            $transfert = $transfert + intval($tran->frais);
                            break;

                            case 'preinscription':
                                $es = $es + intval($tran->frais);
                                break;

                            case 'forfait_international': case 'sim_international': case 'esim_international':
                            $forfaitInternational = $forfaitInternational + intval($tran->frais);
                            break;

                            case 'achat_status':
                                $view = $view + intval($tran->frais);
                                break;
                        }

                        Historiquetrans::where('id', $tran->id)->update([
                            'code_validation' => $tran->frais
                        ]);

                        $filleul = Customer::where('phoneclient', $tran->phonevendeur)->first();
                        if($filleul){
                            Customer::where('id', $filleul->id)->update(['ability'=> intval($tran->frais) + $filleul->ability ]);
                        }
                    }
                }

                if(count($edans)>0){
                    foreach ($edans as $tran){
                        $edan = $edan + 100;
                    }
                    GamElectriciteHist::where('id', $tran->id)->update([
                        'param3' => '100'
                    ]);

                    $filleul = Customer::where('phoneclient', $tran->num_client)->first();
                    if($filleul){
                        Customer::where('id', $filleul->id)->update(['ability'=> 100 + $filleul->ability ]);
                    }
                }

                if(count($ussds)>0){
                    foreach ($ussds as $tran){
                        $ussd = $ussd + 100;
                    }
                    GamRechargeHist::where('id', $tran->id)->update([
                        'param3' => '100'
                    ]);

                    $filleul = Customer::where('phoneclient', $tran->num_client)->first();
                    if($filleul){
                        Customer::where('id', $filleul->id)->update(['ability'=> 100 + $filleul->ability ]);
                    }
                }

                $total =  $credit + $forfait + $visa + $transfert + $es + $forfaitInternational + $view +$edan + $ussd;

                Customer::where('id', $customer->id)->update([
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
                    'solde_parrainage' => strval($total)
                ]);
                $dataCustomer = Customer::where('phoneclient', $request->phone)->get();
                return response()->json([
                    'statut'=> true,
                    'trans'=>$transFilleuls,
                    'customer' => $dataCustomer,
                    'solde_parrainage' => strval($total),
                    'flp_nbr_transaction' => [
                        'credit' => $credit,
                        'forfait' => $forfait,
                        'visa' => $visa,
                        'transfert' => $transfert,
                        'es' => $es,
                        'forfaitInternational' => $forfaitInternational,
                        'view' => $view,
                        'ussd' => $ussd,
                        'edan' => $edan
                    ],
                    'view' => 10,
                    'indication' => 'Activer la récuperation de vos gains en maintenant une activité mensuelle (au moins 30 transactions)',
                    'quota' => 30
                ]);

            }else{
                return response()->json([
                    'statut'=> false,
                    'message' => 'Pas de transactions'
                ]);
            }

        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'compte inexistant'
            ]);
        }
    }

    public function ussdEl(){
        $customers = GamRechargeHist::where('param2', 'not like','2023%')->Where('param7', '!=', 'election')->where('port','6A')->where(function($q) {
            $q->where('reference', 'like', 'MP230827%')->orWhere('reference', 'like', 'MP230828%')->orWhere('reference', 'like', 'MP230829%')->orWhere('reference', 'like', 'MP230830%');})->whereNotIn('phonevendeur', GamRechargeHist::select('phonevendeur')->where('id', '>=',102416228)->where('param1', '!=','closed'))->take(10)->get();
        foreach($customers as $customer){
            GamRechargeHist::where('id', $customer->id)->update([
                'param7'=>'election',
            ]);
        }
        return $customers;
    }

    public function prisecondsup(){
        $now = date("Y-m-d", strtotime('-1 day'));
        //Primaires
        $requestPrim  = DB::select("select * from customer where param5 not like '%hackathonCli%' and phoneclient in ( select phoneclient from reclammation where updated_at >= '2023-08-20 00:00:00' and username is null and type_reclammation = 'systeme') and score =0");
        $primToday =  $this->getToday($requestPrim);
        $customersprimHier = count($requestPrim) - $primToday;


        //Secondaires
        $secondRequest = DB::select("select * from customer where param5 not like '%hackathonCli%' and phoneclient in ( select phoneclient from reclammation where updated_at >= '2023-08-20 00:00:00' and username is null and type_reclammation = 'systeme') and score >=1 and (code_confirmed_at is not null and code_confirmed_at <> 'code_confirmed_at') ");
        $secondToday =  $this->getToday($secondRequest);
        $customersecondHier =  count($secondRequest) - $secondToday;


        //Supérieurs
        $periode = date("Y-m-d", strtotime('-8 month'));
        $supRequest = DB::select("select * from customer where param5 not like '%hackathonCli%' and score >=10 and (code_confirmed_at is not null and code_confirmed_at <> 'code_confirmed_at') and username is null and updated_at >= '$periode' ");
        $supToday =  $this->getToday($supRequest);
        $customersupHier = count($supRequest) - $supToday;

        return response()->json([
            'primaires'=> count($requestPrim),
            'primairesHier'=> $customersprimHier,
            'secondaire'=> count($secondRequest),
            'secondaireHier'=> $customersecondHier,
            'superieurs'=> count($supRequest),
            'superieursHier'=> $customersupHier
        ]);

    }

    public function getToday($tab){
        $now = date("Y-m-d");
        $toDay = 0;
        foreach ($tab as $data){
            if(str_contains(strval($data->updated_at), strval($now))){
                $toDay = $toDay +1;
            }
        }
        return $toDay;
    }

    public function errorsApp(Request $request){
        $now = date("Y-m-d");
        $yesterday = date("Y-m-d", strtotime('-1 day'));
        $errorToday = DB::select(" select * from error_app where created_at like '$now%' ");
        $errorHier = DB::select(" select * from error_app where created_at like '$yesterday%' ");

        return response()->json([
            'statut'=> true,
            'today'=>  count($errorToday),
            'todayError'=>  $errorToday,
            'hier'=>  count($errorHier),
            'hierError'=>  $errorHier
        ]);
    }

    public function telechargemntappclient(Request $request){
        $countjleo=0;
        $countcampagnard=0;
        $countdragage=0;
        $eachinstallations=DB::select(" SELECT * FROM customer c where c.score >= 1 and c.code_confirm in (SELECT c2.code_confirm from customer c2 WHERE c2.type ='hotesse');");

        foreach($eachinstallations as $customer){

            if($customer->code_confirm =='jleo'){

                $countjleo= $countjleo + 1;
            }else  if($customer->code_confirm =='campagnard2'){
                $countcampagnard=$countcampagnard + 1 ;
            }
        } return response()->json([
            'status'=> true,
            'Jeanne&leo'=> $countjleo,
            'campagnard'=>  $countcampagnard,
        ]);
    }

    public function serviceContactCustomer(Request $request){
        $verifCustomer = Customer::where('id', $request->idClient)->orWhere('phoneclient', $request->phoneClient)->first();
        if($verifCustomer){
            $info = json_encode([
                'name'=>  $verifCustomer->nom .' '. $verifCustomer->prenom,
                'phone'=>  $verifCustomer->phoneclient ,
                'whatsapp'=>  $verifCustomer->whatsapp ,
                'solde'=>  $verifCustomer->solde ,
                'score'=>  $verifCustomer->score ,
                'zone'=>  $verifCustomer->zone ,
            ]);
            $idClient = $verifCustomer->id;
        }else{
            $info = 'none';
            $idClient = 0;
        }
        $contact = new Contact([
            'services' => $request->services,
            'agent' => $request->agent,
            'customer' => $idClient,
            'info_customer' => $info,
            'trans_customer' =>$request->trans,
            'module'=>$request->module,
            'support'=>$request->support,
            'app'=>empty($request->app)? 'back_office': $request->app ,
        ]);
        $contact->save();
        return 1;
    }

    public function serviceContactCustomerIn($idClient, $phoneClient, $services, $agent, $trans, $module, $support, $app){
        $verifCustomer = Customer::where('id', $idClient)->orWhere('phoneclient', $phoneClient)->first();
        if($verifCustomer){
            $info = json_encode([
                'name'=>  $verifCustomer->nom .' '. $verifCustomer->prenom,
                'phone'=>  $verifCustomer->phoneclient ,
                'whatsapp'=>  $verifCustomer->whatsapp ,
                'solde'=>  $verifCustomer->solde ,
                'score'=>  $verifCustomer->score ,
                'zone'=>  $verifCustomer->zone ,
            ]);
            $idClient = $verifCustomer->id;
        }else{
            $info = 'none';
            $idClient = 0;
        }
        $contact = new Contact([
            'services' => $services,
            'agent' => $agent,
            'customer' => $idClient,
            'phoneclient' => $phoneClient,
            'info_customer' => $info,
            'trans_customer' =>$trans,
            'module'=>$module,
            'support'=>$support,
            'app'=>empty($app)? 'back_office': $app,
        ]);
        $contact->save();
        return 1;
    }

    public function imagepub(){
        return json_encode([
            'https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/pub%2F3.jpg?alt=media&token=65323791-a394-4b31-8824-ffcc415ce85b',
            'https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/pub%2F1.jpg?alt=media&token=9dd56825-dc09-4f73-a0b5-706f2b70d809',
            'https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/pub%2F2.jpg?alt=media&token=22871a2d-7026-49b9-9de1-8cdbace38550' ,
            'https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/pub%2F4.jpg?alt=media&token=91848e92-8ee8-400f-b571-7962aec869ec',
            'https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/pub%2F5.jpg?alt=media&token=226013d7-24b5-4e58-a362-2647f03b1ac4'
        ]);
    }

    public function exchangeDeviceXAf(){
        return [
            "49"=>   ["pays"=>"Germany","devise"=>'EUR',"Taux"=>"655.95"],
            "61"=> ["pays"=>"Australia","devise"=>'AUD',"Taux"=>"416.66"],
            "32"=>   ["pays" => "Belgium","devise"=>'EUR',"Taux"=>"655.95"],
            "229"=>   ["pays" => "Benin","devise"=>'XOF',"Taux"=>"1.00"],
            "226"=>  ["pays" => "Burkina Faso","devise"=>'XOF',"Taux"=>"1.00"],
            "257"=>  ["pays" => "Burundi","devise"=>'BIF',"Taux"=>"0.21"],
            "237"=>  ["pays" => "Cameroon","devise"=>'XOF',"Taux"=>"1.00"],
            "1CAD"=>   ["pays" => "Canada","devise"=>'CAD',"Taux"=>"471.08"],
            "269"=>  ["pays" => "Comoros","devise"=>'KMF',"Taux"=>"1.33"],
            "225"=>  ["pays" => "Cote d'ivoire","devise"=>'XOF',"Taux"=>"1.00"],
            "45"=>  ["pays" => "Danemark","devise"=>'DKK',"Taux"=>"91.30"],
            "20"=>  ["pays" => "Egypte","devise"=>'EGP',"Taux"=>"20.11"],
            "34"=>  ["pays"=>"Spain","devise"=>'EUR',"Taux"=>"655.95"],
            "1"=>   ["pays"=>"United States","devise"=>'USD',"Taux"=>"616.52"],
            "251"=>   ["pays"=>"Ethiopia","devise"=>'EUR',"Taux"=>"10.90"],
            "33"=>   ["pays"=>"France","devise"=>'EUR',"Taux"=>"655.95"],
            "241"=>   ["pays"=>"Gabon","devise"=>'XOF',"Taux"=>"1.00"],
            "220"=>   ["pays"=>"Gambia","devise"=>'GMD',"Taux"=>"9.53"],
            "233"=>  ["pays"=>"Ghana","devise"=>'GHS',"Taux"=>"51.95"],
            "995"=>   ["pays"=>"Georgia","devise"=>'EUR',"Taux"=>"655.95"],
            "972"=>   ["pays"=>"Israel","devise"=>'EUR',"Taux"=>"655.95"],
            "30"=>  ["pays"=>"Greece","devise"=>'EUR',"Taux"=>"655.95"],
            "245"=>  ["pays" => "Guinea-Bissau","devise"=>'XOF',"Taux"=>"1.00"],
            "374"=>    ["pays" => "Armenia", "devise" => "EUR","Taux"=>"655.95"],
            "509"=>  ["pays"=>"Haiti","devise"=>'HTG',"Taux"=>"4.61"],
            "31"=>  ["pays"=>"Nertherlands","devise"=>'EUR',"Taux"=>"655.95"],
            "91"=>  ["pays"=>"India","devise"=>'INR',"Taux"=>"7.48"],
            "353"=>  ["pays"=>"Ireland","devise"=>'EUR',"Taux"=>"655.95"],
            "39"=> ["pays"=>"Italy","devise"=>'EUR',"Taux"=>"655.95"],
            "962"=>  ["pays"=>"Jordan","devise"=>'JOD',"Taux"=>"833.33"],
            "254"=>  ["pays"=>"Kenya","devise"=>'KES',"Taux"=>"3.99"],
            "231"=>  ["pays"=>"Liberia","devise"=>'USD',"Taux"=>"615.38"],
            "352"=>  ["pays"=>"Luxambourg","devise"=>'EUR',"Taux"=>"655.95"],
            "261"=>  ["pays"=>"Madagascar","devise"=>'MGA',"Taux"=>"0.13"],
            "223"=>  ["pays"=>"Mali","devise"=>'XOF',"Taux"=>"1.00"],
            "212"=>  ["pays"=>"Morocco","devise"=>'MAD',"Taux"=>"62.81"],
            "222"=>  ["pays"=>"Mauritania","devise"=>'MRU',"Taux"=>"16.23"],
            "227"=>  ["pays"=>"Niger","devise"=>'XOF',"Taux"=>"1.00"],
            "234"=>  ["pays"=>"Nigeria","devise"=>'NGN',"Taux"=>"0.69"],
            "47"=>  ["pays"=>"Norway","devise"=>'NOK',"Taux"=>"59.27"],
            "256"=>  ["pays"=>"Uganda","devise"=>'UGX',"Taux"=>"0.16"],
            "243"=>  ["pays"=>"Congo_RDC","devise"=>'USD',"Taux"=>"621.11"],
            "44"=>  ["pays"=>"United_Kingdom","devise"=>'GBP',"Taux"=>"795.54"],
            "250"=>  ["pays"=>"Rwanda","devise"=>'RWF',"Taux"=>"0.49"],
            "221"=>  ["pays"=>"Senegal","devise"=>'XOF',"Taux"=>"1.00"],
            "248"=> ["pays"=>"Seychelles","devise"=>'SCR',"Taux"=>"50.00"],
            "232"=> ["pays"=>"Sierra_Leone","devise"=>'SLE',"Taux"=>"27.31"],
            "94"=> ["pays"=>"Sri_Lanka","devise"=>'LKR',"Taux"=>"1.85"],
            "46"=>  ["pays"=>"Sweden","devise"=>'SEK',"Taux"=>"61.87"],
            "255"=>  ["pays"=>"Tanzania","devise"=>'TZS',"Taux"=>"0.24"],
            "235"=>  ["pays"=>"Chad","devise"=>'XOF',"Taux"=>"1.00"],
            "66"=>  ["pays"=>"Thailand","devise"=>'THB',"Taux"=>"17.62"],
            "228"=>  ["pays"=>"Togo","devise"=>'XOF',"Taux"=>"1.00"],
            "260"=>  ["pays"=>"Zambia","devise"=>'ZMW',"Taux"=>"24.21"]
        ];
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
                $sender = "+24104084184";
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

    public function sendMessageNewCustomer(){
        $now = date('Y-m-d');
        //$customers = DB::select("select * from customer c where phoneclient ='074582442' limit 10");
        //$customers = DB::select("select * from customer c where c.contact_at is null and flp_created_at like '$now%' limit 5");
        $customers = DB::select("select * from customer c where c.contact_at is null and created_at > '2024-05-18 00:00:00' and deleted_at is null limit 5 ");
        $message = "🎊🎉 Bienvenue dans l'univers *GAM Gabon*! 🎉🎊

Nous sommes ravis de vous accueillir sur notre plateforme *GamPay* 🤩🤗. N'hésitez pas à explorer nos fonctionnalités et à utiliser nos services.

Nous restons disponible en cas de difficultés.
*Bonne expérience*!✅😊";
        if(count($customers)){
            foreach ($customers as $customer){
                Customer::where('id', $customer->id)->update(['contact_at' => strval(date('Y-m-d H:i:s'))]);
                if(!empty($customer->whatsapp)){
                    $whatsAppNum = $customer->whatsapp;
                }else{
                    $whatsAppNum= $this->getWhatsAppNumber($customer->phoneclient, $customer->pays);
                }
                $whatsApp = $this->sendWhatsAppMessage($whatsAppNum,$message, 'rm');
                if($whatsApp != 1){
                    $newWhatsAppNum = $this->getWhatsAppNumber($whatsAppNum, $customer->pays);
                    $this->getWhatsAppNumber($whatsAppNum, $customer->pays);
                    $this->sendWhatsAppMessage($newWhatsAppNum,$message, 'other');
                }
               
            }
        }
        
        $this->rembourseRmwhatsappLoading();
        return $customers;
    }

    public function parameters(Request $request){
        $phoneclient = '';
        if(!empty($request->phoneclient)){
          $customer = Customer::where('phoneclient', $request->phoneclient)->first();
        }
        return json_encode([
            //page login
            "facebook" => "https://www.facebook.com/GAMGabon",
            "pwa" => "https://app.gampay.org",
            //fin page login

            //page reclamationListView
            "Whatsapp1"=>"https://wa.me/+24174471892",
            //finpage reclamationListView

            //page header
            "mise_a_jour"=>"https://app.gampay.org/",
            //fin page header

            //homeComponents
            "partage_statut"=>"https://app.gampay.org/",
            //fin homeComponents

            //page GamWebView
            "cgu"=> empty($customer)?"https://gampay.org/gamclients/public/cgu": "https://gampay.org/gamclients/public/cgu". $customer->id,
            //fin page GamWebView

            //page home
            "lien_Android"=>"https://play.google.com/store/apps/details?id=com.gampay.gam",
            "lien_Ios"=>"",
            "lien_chaine_whatsApp"=>"https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c",
            //fin page home

            //page profil
            "supp_compte"=>" https://gampay.app/gamclients/public/removecompte",
            //fin page profil

            //consiliation -> page challenge
            "filleul"=>"https://gampay.app/gamclients/public/filleul",
            //fin consiliation -> page challenge

            //esDubai -> page linguistique
            "img_1"=>"https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/img%2Fanglais.gif?alt=media&token=88d4719a-9bb8-474b-83ce-c0f83cacdf56",
            "img_2"=>"https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/img%2Fmode_travail.gif?alt=media&token=f0a304a4-6a3e-4b91-afb7-8283466f9304",
            "img_3"=>"https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/img%2Fdemande_visa.gif?alt=media&token=096693f3-3358-4f4a-a23f-0a8a9635928e",
            "img_5"=>"https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/img%2Fsoutient.gif?alt=media&token=171db41d-b08e-4d7d-8b88-0264174b6354",
            "img_7"=>"https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/img%2Factivite_sociale.gif?alt=media&token=24959a64-0262-4043-ae80-c42784da972b",
            "img_8"=>"https://firebasestorage.googleapis.com/v0/b/app-gampay.appspot.com/o/img%2Fpeople.gif?alt=media&token=c3b973d4-33b5-4d1d-bd2b-ae78dc7c91c2",
            "img_autre"=>"http://gampay.org/files/presentationESD.gif",
            //fin esDubai -> page linguistique

            //forfait -> page forfaitGam
            "lien_qr_code" => "https://gampay.org/files/QR_code/",
            //fin forfait -> page forfaitGam
            //forfait -> page activation
            "Whatsapp2"=>"https://wa.me/+24105949831",
            //fin forfait -> page activation

            //réclamation     
            "img_reclamation"=>"https://media.giphy.com/media/U3O6bnlgwdK5nj5Lrf/giphy.gif",
            //fin réclamation

            //page historiquePage
            "recapTrans"=>"https://gampay.org/gamclients/public/recapTrans",
            //fin page historiquePage
            "frais_ria" => "",
            "frais_Gam" => "2.5",
            "frais_am" => "",
            "frais_moov" => "",
            
            // liste des secteurs d'activité
            "secteur" => [
                "Enseignement" ,
                "Militaire" ,
                "Banquier" ,
                "Medecine" ,
                "Etudiant" ,
                "Automobile" ,
                "Transport",
                "Commerce" ,
                "Petrole"
                ],
             // Price view  
             "vue" =>"50", 
             "link_visite_card" =>"https://gampay.org/gamclients/public/visiteCarte?carte=", 
        ]);
    }

    public function confrimTransTraitStatus(){

        $confirms = ConfirmTrans::where('id','>', 58520)->whereNull('param2')->take(5)->get();
        if(count($confirms)>0){
            foreach ($confirms as $confirm){
                if(!empty($confirm->reference) && $confirm->reference != 'envoye'){
                    $ref_split = str_split($confirm->reference);
                    $newDate = "20$ref_split[5]$ref_split[6]-$ref_split[7]$ref_split[8]-$ref_split[9]$ref_split[10] $ref_split[12]$ref_split[13]:$ref_split[14]$ref_split[15]:00";

                    $tran = Historiquetrans::whereIn('operation', ['transfert_visa', 'transfert_mobile', 'rendu_monnaie_simple'])
                        ->whereNotIn('etat', ['CONFIRMEE', 'confirme'])->where('numclient', $confirm->phonevendeur)
                        ->where('montant_sans_frais', $confirm->montant)->where('created_at', '<',$newDate)->first();

                    if($tran){
                        Historiquetrans::where('id',$tran->id)->update([
                            'reference'=>$confirm->reference,
                            'param1'=>$confirm->content,
                            'etat' => 'CONFIRMEE'
                        ]);
                    }
                }
                $updateConfirm = DB::update("UPDATE confirmtrans SET param2 = 'traite' WHERE id=$confirm->id");
            }
        }
        //Http::get('https://gampay.app/gamclients/public/api/confrimTransDemandeStatus');
        return $confirms;
    }

    public function confrimTransDemandeStatus(){
        $trans = Historiquetrans::whereIn('operation', ['rendu_monnaie_simple','transfert_visa', 'transfert_mobile'])->where('content', 'MOBICASH')->where('etat', 'demande')->orderBy('id', 'desc')->take('10')->get();
        if(count($trans)>0){
            foreach ($trans as $tran){
                Historiquetrans::where('id',$tran->id)->update([
                    'etat' => 'CONFIRMEE'
                ]);
            }
        }
        return $trans;
    }

    public function clientsActifDodo(Request $request){
        $now = date("Y-m-d");
        $customerdodoussd=DB::select("select  * from client2 c WHERE c.etat ='dodo' and c.created_at BETWEEN  '2023-07-01 00:00:00 'and NOW() ");
        $customeractifussd=DB::select("select * from client2 c WHERE c.etat ='actif' and c.created_at BETWEEN '2023-07-01 00:00:00'and  NOW()");
        $contactsToday = Customer::where('locked_at', $now)->where('customer_type','<>', 'pharmacie')->whereNull('username')->get();
        return response()->json([
            "statut"=>true,
            "client_ussd_endormie"=>count($customerdodoussd),
            "client_ussd_actif"=>count($customeractifussd),
            "client_contacte"=>count($contactsToday),
        ]);
    }


    public function migratedCadeau(){
        $now = date("Y-m-d");
        $cadeauApp = 0;
        $cadeauUSSD = 0;
        $migrateToday = 0;
        $cadeauApptoDay = 0;
        $cadeauUSSDtoDay = 0;
        $migrate = 0;
        $actifToday = 0;

        $contactTotal = DB::select("SELECT * from contact c where (c.module = 'migrate' or c.module = 'appCadeau' or c.module = 'ussdCadeau')");
        $contactTodayTotal = DB::select("SELECT * from contact c where (c.module = 'migrate' or c.module = 'appCadeau' or c.module = 'ussdCadeau') and created_at like '$now%'");

        if(count($contactTotal)>0){
            foreach ($contactTotal as $migre){
                switch($migre->module){
                    case 'appCadeau' :
                        $cadeauApp = $cadeauApp + 1;
                        break;

                    case 'ussdCadeau' :
                        $cadeauUSSD = $cadeauUSSD + 1;
                        break;
                    case 'migrate' :
                        $migrate = $migrate + 1;
                        break;

                }
            }
        }

        if(count($contactTodayTotal)>0){
            foreach ($contactTodayTotal as $migre){
                switch($migre->module){
                    case 'appCadeau' :
                        $cadeauApptoDay = $cadeauApptoDay + 1;
                        break;
                    case 'ussdCadeau' :
                        $cadeauUSSDtoDay = $cadeauUSSDtoDay + 1;
                        break;
                    case 'migrate' :
                        $migrateToday = $migrateToday + 1;
                        break;

                }
            }
        }

        $sleeping = 106953;
        $actif = 12594;

        $sleepingNow = DB::select("select  * from client2 c WHERE c.etat ='dodo' and c.created_at BETWEEN  '2023-07-01 00:00:00 'and NOW() ");
        $actifNow = DB::select("select * from client2 c WHERE c.etat ='actif' and c.created_at BETWEEN '2023-07-01 00:00:00'and  NOW()");

        foreach($actifNow as $client){
            if($client->created_at > date('Y-m-d H:i:s', strtotime('-1 day'))){
                $actifToday =  $actifToday +1;
            }
        }


        return response()->json([
            "Endormis départ"=>$sleeping,
            'Actif départ' => $actif,
            'Endormis Maintenant' => count($sleepingNow),
            'Actif Maintenant' => count($actifNow),
            "Total contactés"=>count($contactTotal),
            'Total contactés cadeau Application' => $cadeauApp,
            'Total contactés cadeau Ussd' => $cadeauUSSD,
            'Total contactés migration' => $migrate,
            "Total contactés du jour "=>count($contactTodayTotal),
            "Total contactés du jour cadeau Application "=>$cadeauApptoDay,
            "Total contactés du jour cadeau Ussd "=>$cadeauUSSDtoDay,
            "Total contactés du jour migration"=>$migrateToday,
        ]);
    }

    public function buyforme(Request $request){
        return redirect('https://app.gampay.org?bfm='.$request->bfm);
    }

    public function gamview(Request $request){
        if(empty($request->p) || empty($request->v)){
             return redirect('https://app.gampay.org/?p=2280618&v=vue');
        }else{
            return redirect('https://app.gampay.org/?p='.$request->p.'&v='.$request->v);
        }
    }
    
    public function rm(Request $request){
        if(!empty($request->id)){
            return redirect('https://app.gampay.org/?log='.$request->id);
        }elseif (!empty($request->assist)){
            $trans = $this->getTransRmWhatappIn($request->assist);
            if($trans != 0){
                return redirect('https://wa.me/24104471892?text=');
            }else{
                return redirect('https://wa.me/24104471892?text=');
            }
        }elseif (!empty($request->useApp)){
            if($request->option == 'promo'){
                $phone = $this->getNumByWhatsApp($request->useApp);
                $customer = Customer::where('phoneclient', $phone)->first();
                $message = "Nouveau filleul pour installation $request->useApp";
                if($customer){
                    if(!empty($customer->phoneparent)){
                        if($customer->phoneparent == 'GAM'){
                            $message = "Nouveau filleul *GAM* pour installation *$request->useApp*";
                        }else{
                            $parrain = Customer::where('phoneclient', $customer->phoneparent)->first();
                            if($parrain){
                                if(!empty($parrain->color)){
                                    $message = "Nouveau filleul pour installation *$request->useApp* filleul d'un challenger affecté au *$parrain->color*";
                                }
                            }
                        }
                    }
                }
                $this->sendWhatsAppGroupMessageIn($message, 'Travaux objectifs (1)', 'other');
                Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=work&message=".$message);
                return redirect('https://app.gampay.org/?p=download&v=vue');
            }else{
                $trans = $this->getTransRmWhatappIn($request->useApp);
                if($trans != 0){
                    return redirect('https://gampay.app/gamclients/public/rm?id='.$trans['customer']);
                }else{
                    return redirect('https://app.gampay.org/?p=download&v=vue');
                }  
            }
        }else{
            return redirect('https://app.gampay.org/?p=download&v=vue');
        }
    }
    
    
    public function storeApp(Request $request){
        if($request->phone=='ios'){
            return redirect('https://apps.apple.com/ga/app/gampay/id6448640058');
        }else{
            return redirect('https://play.google.com/store/apps/details?id=com.gampay.gam&hl=fr');
        }
    }
    
    public function getNameWifiPlan($plan){
        if($plan == 'jour'){
            $name = "journalier";
        }elseif($plan == 'semaine'){
            $name = "hebdomadaire";;
        }elseif($plan == 'wifiCard'){
            $name = "1 mois Wifi+Card";;
        }elseif($plan == 'wifiNetflix'){
            $name = "1 mois Wifi+Netflix";;
        }else{
            $name = "Mensuel";;
        }
        return $name;
    }
    
    /*public function valideTransWifi(){
        return 100;
        $now = date('Y-m-d');
        $trans = Historiquetrans::where('operation', 'achat_wifi')->whereIn('etat', ['atraiter', 'en_attente'])->get();
        if(count($trans)){
            foreach ($trans as $tran){
                if($tran->content == 'jour'){
                    $ticket = Wifi::where('dure', $tran->content)->where('date_debut', $now)->where('statut', 'unused')->first();
                    if($tran->reference == 'scyd_buy_wifi'){
                        $ticket = Wifi::where('dure', $tran->content)->where('param2','not like','%0%')->where('statut', 'unused')->first();
                    }
                }else{
                    $ticket = Wifi::where('dure', $tran->content)->where('date_debut', '>=', $now)->where('statut', 'unused')->first();
                    if($tran->reference == 'scyd_buy_wifi'){
                        $ticket = Wifi::where('dure', $tran->content)->where('param2','not like','%0%')->where('statut', 'unused')->first();
                    }
                }
                if($ticket){
                    Historiquetrans::where('id', $tran->id)->update([
                        'etat'=>'CONFIRMEE',
                        'param9'=> json_encode([
                            'login' => 'gam', 'pass' => $ticket->code, 'valide' => $ticket->date_fin
                        ])
                    ]);
                    
                    if($tran->reference == 'scyd_buy_wifi'){
                        if(str_contains($ticket->dure,$tran->code_confirm)){
                            $nameWifi = $this->getNameWifiPlan($tran->content);
                            $whatsapp = '241'.substr($tran->phonevendeur,1);
                            //$message = "Vous venez d'acheter un ticket *WIFI GAM $nameWifi*, votre ticket est *$ticket->code*";
                            $message = "Transaction effectuée avec succès ✅. Vous venez d'acheter un ticket *WIFI GAM $nameWifi*, votre abonnement sera reconduit dans quelques instants.";
                            
                            if($ticket->param2 == 'sigma'){
                                $message = "Transaction effectuée avec succès ✅. Vous venez d'acheter un ticket *WIFI GAM $nameWifi*, votre ticket est *$ticket->code*";
                                Wifi::where('id', $ticket->id)->update(['statut' => 'used','param1'=>$tran->phonevendeur]);
                            }
                            
                            Http::get("https://gampay.org/gamclients/public/api/templateTicketWifiByScyd?whatsapp=$whatsapp&message=$message");
                            
                            $messageAlerte = "Le *$tran->phonevendeur* vient d'acheté un ticket *WIFI GAM $nameWifi*";
                            $this->sendWhatsappMessageInGroupWaAPI('work',$messageAlerte);
                        }
                    }else{
                        Wifi::where('id', $ticket->id)->update(['statut' => 'used']);
                    }
                }
            }
        }
        return $trans;
    }*/
    
    
    public function distributionApp(Request $request){

        $customers = Customer::where('score', '>', 1)->whereNull('username')->where('status', 0)->orderBy('score', 'desc')->take(2)->get();

        foreach ($customers as $customer){
            $getRang = ProgramSubscription::where('id', 36969)->first();
            $rang = intval($getRang->param8) +1;

            if($rang <= 2500){

                if($rang <= 2){
                    $lot1 = new Cadeau([
                        'name' => 'Bourse Es Dubai'
                        ,'customer'=>$request->app == 'ussd'? $customer->num : $customer->id,
                        'phoneclient' => $request->app == 'ussd'? $customer->numclient : $customer->phoneclient
                        ,'type' => 'article'
                        ,'description' => "Des BOURSES D'ÉTUDES DE LANGUE ANGLAISE à Dubaï Ouiiiii!!!! Vous ne rêvez pas !!!"
                        ,'file' => 'dubai.jpeg'
                        ,'validity' => '2024-12-31',
                        'param2' => 'app'
                    ]);
                    $lot1->save();
                }

                if($rang<=50){
                    $lot2 = new Cadeau([
                        'name' => 'Pocket Wifi international'
                        ,'customer'=>$request->app == 'ussd'? $customer->num : $customer->id
                        ,'phoneclient' => $request->app == 'ussd'? $customer->numclient : $customer->phoneclient,
                        'type' => 'article'
                        ,'description' => "Ayez du forfait partout dans le monde, Ouiiiii vous l'avez bien entendu, elle peut changer de pays"
                        ,'file' => 'wifi.jpeg'
                        ,'validity' => '2024-02-28',
                        'param2' => 'app'
                    ]);
                    $lot2->save();
                }


                $lot3 = new Cadeau([
                    'name' => 'Sim internationale'
                    ,'customer'=>$request->app == 'ussd'? $customer->num : $customer->id,
                    'phoneclient' => $request->app == 'ussd'? $customer->numclient : $customer->phoneclient
                    ,'type' => 'trans'
                    ,'description' => "Plus besoin d'avoir une Sim physique dans votre téléphone, utilisez désormais la e-Sim de GAM Gabon et restez connectés à vos proches partout dans le monde"
                    ,'file' => 'sim.jpeg'
                    ,'validity' => '2024-12-31',
                    'content'=> '500',
                    'operation'=> 'sim',
                    'montant'=> '10406',
                    'param1' => 'WW_901O_STACK_ONEOFF_WORLD_500MB_1D',
                        'param2' => 'app'
                ]);
                $lot3->save();


                /*if($rang == 2 || $rang == 3 || ($rang >= 6 && $rang <=100) ){
                    $lot4 = new Cadeau([
                        'name' => 'Carte VISA UBA'
                        ,'customer'=>$request->app == 'ussd'? $customer->num : $customer->id,
                        'phoneclient' => $request->app == 'ussd'? $customer->numclient : $customer->phoneclient
                        ,'type' => 'article'
                        ,'description' => "En partenariat avec UBA, les cartes Visa gérées par GAM Gabon sont particulières et pleines de surprises  Obtenez la carte Visa et chargez un max de gain"
                        ,'file' => 'visa.jpeg'
                        ,'validity' => '2024-02-28'
                    ]);
                    $lot4->save();
                }*/

                if($rang >= 51 && $rang <=80 ){
                    $lot4 = new Cadeau([
                        'name' => 'Sac suprise'
                        ,'customer'=>$request->app == 'ussd'? $customer->num : $customer->id,
                        'phoneclient' => $request->app == 'ussd'? $customer->numclient : $customer->phoneclient
                        ,'type' => 'article'
                        ,'description' => "Vous avez droit également à un sac surprise. C'est le lot mystère de GAM Gabon"
                        ,'file' => 'surprise.jpeg'
                        ,'validity' => '2024-02-28',
                        'param2' => 'app'
                    ]);
                    $lot4->save();
                }
                if($rang >= 81 && $rang <= 131) {
                    $lot6 = new Cadeau([
                        'name' => 'Crédit de communication'
                        ,'customer'=> $request->app == 'ussd'? $customer->num : $customer->id,
                        'phoneclient' => $request->app == 'ussd'? $customer->numclient : $customer->phoneclient
                        ,'type' => 'trans',
                        'operation'=> 'achat_credit',
                        'montant'=> '1000',
                        'description' => "Profitez d'un achat crédit de 2000 FCFA vers un numéro de votre choix"
                        ,'file' => 'credit.jpeg'
                        ,'validity' => '2024-02-28',
                        'param2' => 'app'
                    ]);
                    $lot6->save();
                }

                if($rang >= 132 && $rang <= 880) {
                    $lot5 = new Cadeau([
                        'name' => 'Forfait internet'
                        , 'customer' => $request->app == 'ussd' ? $customer->num : $customer->id,
                        'phoneclient' => $request->app == 'ussd' ? $customer->numclient : $customer->phoneclient
                        , 'type' => 'trans',
                        'content' => '200',
                        'operation' => 'achat_forfait',
                        'montant' => '800',
                        'description' => "Profitez des forfaits internet de GAM GABON valide jusqu'à 2 mois"
                        , 'file' => 'forfait.jpeg'
                        , 'validity' => '2024-02-28',
                        'param2' => 'app'
                    ]);
                    $lot5->save();
                }

                if($rang >= 881) {
                    $lot7 = new Cadeau([
                        'name' => 'Code Promo',
                        'customer' => $request->app == 'ussd' ? $customer->num : $customer->id,
                        'phoneclient' => $request->app == 'ussd' ? $customer->numclient : $customer->phoneclient,
                        'type' => 'trans',
                        'operation' => 'code_promo',
                        'description' => "Effectuez vos transactions sans frais sur votre application GamPay pendant 1 mois",
                        'file' => 'promo.jpeg',
                        'validity' => '2024-03-31',
                        'param2' => 'app'
                    ]);
                    $lot7->save();
                }


                Customer::where('id', $customer->id)->update([
                    'status'=> 10
                ]);

                ProgramSubscription::where('id', 36969)->update([
                    'param8'=> strval($rang)
                ]);
            }else{
                return 'Fin de la distribution';
            }
        }
        return 1;

    }


    public function distributionUssd(){
        $idClient2 = ProgramSubscription::where('id', 36969)->first();
        $tabId = empty($idClient2->param4)? [] : json_decode($idClient2->param4);
        
        $customers = Client2::where('score', '>', 1)->whereNotIn('num', $tabId)->orderBy('score', 'desc')->take(10)->get();
        
        foreach ($customers as $customer){
            $getRang = ProgramSubscription::where('id', 36969)->first();
            $rang = intval($getRang->param7) +1;
            
            if($rang <= 2500){

                if($rang <= 3) {
                    $lot1 = new Cadeau([
                        'name' => 'Bourse Es Dubai',
                        'customer'=> $customer->num,
                        'phoneclient' =>  $customer->numclient,
                        'type' => 'article',
                        'description' => "Des BOURSES D'ÉTUDES DE LANGUE ANGLAISE à Dubaï Ouiiiii!!!! Vous ne rêvez pas !!!",
                        'file' => 'dubai.jpeg',
                        'validity' => '2024-12-31',
                        'param2' => 'ussd'
                    ]);
                    $lot1->save();
                }

                if($rang<=150) {
                    $lot2 = new Cadeau([
                        'name' => 'Pocket Wifi international',
                        'customer'=> $customer->num ,
                        'phoneclient' =>  $customer->numclient,
                        'type' => 'article',
                        'description' => "Ayez du forfait partout dans le monde, Ouiiiii vous l'avez bien entendu, elle peut changer de pays",
                        'file' => 'wifi.jpeg',
                        'validity' => '2024-02-28',
                        'param2' => 'ussd'
                    ]);
                    $lot2->save();
                }
                
                $lot3 = new Cadeau([
                    'name' => 'Sim internationale'
                    ,'customer'=> $customer->num ,
                    'phoneclient' =>  $customer->numclient,
                    'type' => 'trans'
                    ,'description' => "Plus besoin d'avoir une Sim physique dans votre téléphone, utilisez désormais la e-Sim de GAM Gabon et restez connectés à vos proches partout dans le monde"
                    ,'file' => 'sim.jpeg'
                    ,'validity' => '2024-12-31',
                    'content'=> '500',
                    'operation'=> 'sim',
                    'montant'=> '10406',
                    'param1' => 'WW_901O_STACK_ONEOFF_WORLD_500MB_1D',
                    'param2' => 'ussd'
                ]);
                $lot3->save();

                /*if($rang == 2 || $rang == 3 || ($rang >= 6 && $rang <=100) ){
                    $lot4 = new Cadeau([
                        'name' => 'Carte VISA UBA'
                        ,'customer'=>$request->app == 'ussd'? $customer->num : $customer->id,
                        'phoneclient' => $request->app == 'ussd'? $customer->numclient : $customer->phoneclient
                        ,'type' => 'article'
                        ,'description' => "En partenariat avec UBA, les cartes Visa gérées par GAM Gabon sont particulières et pleines de surprises  Obtenez la carte Visa et chargez un max de gain"
                        ,'file' => 'visa.jpeg'
                        ,'validity' => '2024-02-28'
                    ]);
                    $lot4->save();
                }*/

                if($rang >= 151 && $rang <= 220) {
                    $lot4 = new Cadeau([
                        'name' => 'Sac suprise'
                        ,'customer'=> $customer->num ,
                        'phoneclient' =>  $customer->numclient,
                        'type' => 'article'
                        ,'description' => "Vous avez droit également à un sac surprise. C'est le lot mystère de GAM Gabon"
                        ,'file' => 'surprise.jpeg'
                        ,'validity' => '2024-02-28',
                        'param2' => 'ussd'
                    ]);
                    $lot4->save();
                }


                if($rang >= 221 && $rang <= 271) {
                    $lot6 = new Cadeau([
                        'name' => 'Crédit de communication'
                        ,'customer'=> $customer->num ,
                        'phoneclient' =>  $customer->numclient,
                        'type' => 'trans',
                        'operation'=> 'achat_credit',
                        'montant'=> '1000',
                        'description' => "Profitez d'un achat crédit de 2000 FCFA vers un numéro de votre choix"
                        ,'file' => 'credit.jpeg'
                        ,'validity' => '2024-02-28',
                        'param2' => 'ussd'
                    ]);
                    $lot6->save();
                }

                if($rang >= 271 && $rang <= 1021) {
                    $lot5 = new Cadeau([
                        'name' => 'Forfait internet'
                        ,'customer'=> $customer->num ,
                        'phoneclient' =>  $customer->numclient,
                        'type' => 'trans',
                        'content' => '200',
                        'operation' => 'achat_forfait',
                        'montant' => '800',
                        'description' => "Profitez des forfaits internet de GAM GABON valide jusqu'à 2 mois"
                        , 'file' => 'forfait.jpeg'
                        , 'validity' => '2024-02-28',
                        'param2' => 'ussd'
                    ]);
                    $lot5->save();
                }


                if($rang >= 1022) {
                    $lot7 = new Cadeau([
                        'name' => 'Code Promo',
                        'customer'=> $customer->num ,
                        'phoneclient' =>  $customer->numclient,
                        'type' => 'article',
                        'operation' => 'code_promo',
                        'description' => "Effectuez vos transactions sans frais sur votre application GamPay pendant 1 mois",
                        'file' => 'promo.jpeg',
                        'validity' => '2024-03-31',
                        'param2' => 'ussd'
                    ]);
                    $lot7->save();
                }
                
                array_push($tabId, $customer->num);
                ProgramSubscription::where('id', 36969)->update([
                    'param7'=> strval($rang),
                    'param4'=> json_encode($tabId)
                ]);
                
            }else{
                return 'Fin de la distribution';
            }
        }
        return 1;
    }
    
    public function distributionAppCarte(Request $request){
        $customers = DB::select("select * from customer where status = 10 and id in (select id_customer from carte_information ci where expire_by < '2024-05-01 00:00:00' and deleted_at is null) order by score desc limit 10");
        foreach ($customers as $customer){
                $lot4 = new Cadeau([
                    'name' => 'Carte VISA UBA'
                    ,'customer'=> $customer->id,
                    'phoneclient' => $customer->phoneclient
                    ,'type' => 'article'
                    ,'description' => "En partenariat avec UBA, les cartes Visa gérées par GAM Gabon sont particulières et pleines de surprises  Obtenez la carte Visa et chargez un max de gain"
                    ,'file' => 'visa.jpeg'
                    ,'validity' => '2024-02-28'
                ]);
                $lot4->save();
                Customer::where('id', $customer->id)->update([
                    'status'=> 20
                ]);
        }
        return 1;

    }
    
    
    public function sendMessageNewCustomerCreditUssd(){
        $now = date('Y-m-d');
        $customers = DB::select("select * from gamrechargehist g where sender = 'AirtelMoney' and  port = '6A' and reference like 'MP240219%' and receiver like 'xxx%'");
        $message = "Bonjour cher client,
*GAM Gabon* tient à vous présenter ses plus sincères excuses, pour le retard observé lors de votre achat de crédit de communication.
Suite au désagrément observé, nous réduisons de façon exceptionnel le coût de l'achat de la carte VISA prépayé UBA à -25% ainsi que vos frais de transaction à 0% pour une durée de 7 jours.
Pour plus d'informations contactez votre gestionnaire.
*GAM Gabon* vous remercie pour votre compréhension.
";
        if(count($customers)){
            foreach ($customers as $customer){
                $whatsAppNum= $this->getWhatsAppNumber($customer->numeroclient, '241');
                $whatsApp = $this->sendWhatsAppMessage($whatsAppNum,$message, 'satisfaction');
                if($whatsApp != 1){
                    $newWhatsAppNum = $this->getWhatsAppNumber($whatsAppNum, '241');
                    $this->sendWhatsAppMessage($newWhatsAppNum,$message, 'satisfaction');
                }
            }
        }
        return $customers;
    }
    
    
    public function getStatsInternational(){

        $visa = DB::select("select DISTINCT phonevendeur from historiquetrans h where h.operation in ('transfert_visa', 'recharge_visa_uba')");
        $buy = DB::select("select DISTINCT phonevendeur from historiquetrans h where h.operation ='transfert_visa'");
        $recharge = DB::select("select DISTINCT phonevendeur from historiquetrans h where h.operation ='recharge_visa_uba'");
        
        return response()->json([
            'Les clients VISA'=> count($visa),
            'Les clients Recharge'=> count($recharge),
            'Les clients Paiement'=> count($buy)
        ]);
    }
    
    /*public  function notifSoldeTrans(){
        $trans = Historiquetrans::whereIn('operation', ['transfert_visa', 'transfert_mobile', 'rendu_monnaie_simple'])->where('content', 'AIRTEL MONEY')->where('etat', 'CONFIRMEE')->orderBy('id', 'desc')->first();
        if(!empty($trans->param1)){

            $arrays=explode(",",$trans->param1);

            $verifieStarter = explode(":",$trans->param1);

            $recuparrays=$arrays[1];
            $tab=explode(".",$recuparrays);
            $recuptabs=$tab[1].".".$tab[2];
            $delimiter="de";
            $find=explode($delimiter,$recuptabs);
            $montantAirtel=$find[2];
            if(doubleval($montantAirtel)<200000){
                $customers = DB::select("select * from customer where phoneclient in ('074582442', '074119984', '076520541')");
                $message = " Le solde AIRTEL MONEY est de $montantAirtel";
                if(count($customers)){
                    foreach ($customers as $customer){
                        $this->sendWhatsAppMessage($customer->whatsapp,$message, 'satisfaction');
                    }
                }
            }
            
            return $montantAirtel;
        }
        return $trans;
    }*/
    
    /*public function notifSoldeTrans(){
        Http::get('https://gampay.app/gamclients/public/api/notifTransMoov');
        $trans = Historiquetrans::whereIn('operation', ['transfert_visa', 'transfert_mobile', 'rendu_monnaie_simple'])->where('content', 'AIRTEL MONEY')->where('etat', 'CONFIRMEE')->orderBy('id', 'desc')->first();
        if(!empty($trans->param1)){
            $arrays=explode(",",$trans->param1);
            $verifieStarter = explode(":",$trans->param1);

            $recuparrays=$arrays[1];
            $tab=explode(".",$recuparrays);
            $recuptabs=$tab[1].".".$tab[2];
            $delimiter="de";
            $find=explode($delimiter,$recuptabs);
            $montantAirtel=$find[2];

            $solde= Soldes::where('produit', 'AM'.$trans->param2 )->first();
            Soldes::where('id', $solde->id)->update([
               'solde' => intval($montantAirtel)
            ]);
            if(doubleval($montantAirtel) < doubleval($solde->param2) && $solde->solde != intval($montantAirtel)){
                
                if(abs(doubleval($montantAirtel) - doubleval($solde->solde))>=5000){
                    $customers = DB::select("select * from customer where phoneclient in ('074582442', '074119984', '076520541')");
                    $message = "Le solde AIRTEL MONEY de la SIM $trans->param2 est de $montantAirtel";
                    if(count($customers)){
                        foreach ($customers as $customer){
                            $this->sendWhatsAppMessage($customer->whatsapp,$message, 'other');
                        }
                    }
                }    
            }else{
               return [
                   'solde'=> $solde,
                   'montant'=>$montantAirtel
               ];
            }
        }
        return $trans;
    }*/
    
    public function notifSoldeTrans(){
        Http::get('https://gampay.app/gamclients/public/api/notifTransMoov');
        $trans = Historiquetrans::whereIn('operation', ['transfert_visa', 'transfert_mobile', 'rendu_monnaie_simple'])->where('content', 'AIRTEL MONEY')->where('etat', 'CONFIRMEE')->orderBy('id', 'desc')->first();
        if($trans){
            $accuse = GamRechargeHist::where('reference', $trans->reference)->first();
            if($accuse){
                 $arrays=explode(":",$accuse->content);
                $recuparrays=$arrays[4];

                $recuparrays=$arrays[4];
                $tab=explode(".",$recuparrays);
                $montantAirtel=$tab[0];
                
                //return $montantAirtel;

                $solde= Soldes::where('produit', 'AM'.$trans->param2 )->first();
                Soldes::where('id', $solde->id)->update([
                    'solde' => intval($montantAirtel)
                ]);
                if(doubleval($montantAirtel) < doubleval($solde->param2) && $solde->solde != intval($montantAirtel)){

                    if(abs(doubleval($montantAirtel) - doubleval($solde->solde))>=5000){
                        $customers = DB::select("select * from customer where phoneclient in ('074582442', '074119984', '076520541')");
                        $message = "Le solde AIRTEL MONEY de la SIM $trans->param2 est de $montantAirtel";
                        if(count($customers)){
                            foreach ($customers as $customer){
                                $this->sendWhatsAppMessage($customer->whatsapp,$message, 'other');
                            }
                        }
                    }
                }else{
                    return [
                        'solde'=> $solde,
                        'montant'=>$montantAirtel
                    ];
                }
            }
        }
        return $trans;
    }
    
    
    public  function notifTransMoov(request $request){
        $trans = DB::select("select * from gamrechargehist where sender ='Moov Money' and port ='4A' and content  like 'Sender: Moov Money%' order by id desc limit 1");
        foreach ($trans as $tran){
            $find=explode('de',$tran->content);
            $DeletedSpace = str_replace(' ', '', $find[count($find)-1]);
            $find2 = explode('FCFA', $DeletedSpace);
            $montant=$find2[0];
            $solde= Soldes::where('id', 36)->first();
            
            Soldes::where('id', $solde->id)->update([
                'solde' => intval($montant)
            ]);
            if(intval($montant) < intval($solde->param2) && $solde->solde != intval($montant)){
                if(abs(doubleval($montant) - doubleval($solde->solde))>= 5000){
                    $message = "Le solde MOOV MONEY est de $montant FCFA";
                    $this->sendWhatsAppMessage('24174582442',$message, 'other');
                    $this->sendWhatsAppMessage('24102507006',$message, 'other');
                    $this->sendWhatsAppMessage('24176520541',$message, 'other');
                }
                return [
                    'solde'=> $solde,
                    'montant'=>$montant
                ];
            }else{
                return [
                    'solde'=> $trans, 
                    'montant'=>"c'est déjà bon ". intval($montant) ." $solde->solde  $solde->param2 "
                ];
            }
        }
        return  'no trans';
    }
    
    public function sendNotifForMoovPayment(){
        $payments = GamRechargeHist::where('port', '22A')->where('id','>',104534550)->where('sender' , 'Moov Money')->where('password', 'reference')->take(5)->get();
        if(count($payments)>0){
           foreach ($payments as  $payment) {
               GamRechargeHist::where('id', $payment->id)->update(['password'=> 'reference_traite']);
               $message = "Paiement Moov Money de $payment->montant FCFA effectué par le $payment->phonevendeur";
               $this->sendWhatsAppMessage('24102187228',$message, 'other');
               $this->sendWhatsAppMessage('24174582442',$message, 'other');
               $this->sendWhatsAppMessage('24174734044',$message, 'other');
           }
        }
        return $payments;
    }
    
    // redirection rendu monney whatsApp
    public function rmWhat(Request $request){
        if(!empty($request->trans)){
            return redirect('https://wa.me/24102535748?text=ga-'.$request->trans);
        }else{
            return redirect('https://app.gampay.org/?v=vue&p=2280618');
        }
    }
    
   public function checkNumberUseWhatsapp(Request $request){

        $numwhat = WhatsAppVerify::where('number', $request->number)->get();
        if(count($numwhat)>0){
            return 1;
        }else{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://zylalabs.com/api/926/whatsapp+number+checker+api/743/number+checker?number=$request->number");
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
                            'number'=>  $request->number,
                            'status' => "true"
                        ]);
                        $andWhat->save();
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
            curl_close($ch);
        }
    }
    
    
    public function recupCallBack(Request $request){
        if(!empty($request->results)){
            $data = $request->results;
            $report = WhatsAppBackUp::where('messageId', $data[0]['messageId'])->first();
            $messageId = $data[0]['messageId'];
            if($report){
                $report->update([
                    'seen_at' =>json_encode($data)
                ]);
                if($report->status == 'DELIVERED'){
                   
                    if(str_contains($messageId, 'diff')){
                        $id_Diff= explode('#',strval($messageId));
                        $diff = Diffusion::where('id', intval($id_Diff[1]))->first();
                        if($diff){
                            Diffusion::where('id', $diff->id)->update([
                                'open' =>$diff->open + 1
                            ]);
                            $com = Communication::where('id', $diff->com)->first();
                            Communication::where('id', $com->id)->update([
                                'open' => $com->open + 1
                            ]);
                        }
                    }else{
                        $trans = Historiquetrans::where('param4', $data[0]['messageId'])->first();
                        if($trans){
                            Historiquetrans::where('param4', $data[0]['messageId'])->update([
                                'reference' => 'open'
                            ]);
                        }

                        // For KYC
                        if(str_contains($data[0]['messageId'], 'parrainmypay') || str_contains($data[0]['messageId'], 'filleulmypay')){
                            $messageId = $data[0]['messageId'];
                            $kyc = Kyc::where('idwhatsapp', 'like', "%{$messageId}%")->first();
                            if($kyc){
                                Kyc::where('id', $kyc->id)->update([
                                    'param2' => 'open'
                                ]);
                            }
                        }  
                    }
                    
                }

            }else{
                $backup = new WhatsAppBackUp([
                    'messageId' => $data[0]['messageId'] ,
                    'sender'=> '24102535748',
                    'channel'=> 'whatsapp',
                    'recipeint'=> $data[0]['to'],
                    'price'=> json_encode($data[0]['price']),
                    'status'=> $data[0]['status']['groupName'],
                    'send_at'=> $data[0]['sentAt'],
                    'done_at'=> $data[0]['doneAt'],
                    'bulkId'=> $data[0]['bulkId'],
                    'error'=> json_encode($data[0]['error']),
                ]);
                $backup->save();

                if($data[0]['status']['groupName'] == 'DELIVERED'){
                    
                    if(str_contains($messageId, 'diff')) {
                        $id_Diff= explode('#',strval($messageId));
                        $diff = Diffusion::where('id', intval($id_Diff[1]))->first();
                        if($diff){
                            Diffusion::where('id', $diff->id)->update([
                                'delivered' =>$diff->delivered + 1
                            ]);
                            $com = Communication::where('id', $diff->com)->first();
                            Communication::where('id', $com->id)->update([
                                'delivered' => $com->delivered + 1
                            ]);
                        }
                    }else{
                        $trans = Historiquetrans::where('param4', $data[0]['messageId'])->first();
                        if($trans){
                            Historiquetrans::where('param4', $data[0]['messageId'])->update([
                                'reference' => 'delivered'
                            ]);
                        }
                        // For KYC
                        if(str_contains($data[0]['messageId'], 'parrainmypay') || str_contains($data[0]['messageId'], 'filleulmypay')){
                            $messageId = $data[0]['messageId'];
                            $kyc = Kyc::where('idwhatsapp', 'like', "%{$messageId}%")->first();
                            if($kyc){
                                Kyc::where('id', $kyc->id)->update([
                                    'param2' => 'delivered'
                                ]);
                            }
                        } 
                    }
                }
            }
        }
        Http::get('https://gampay.app/gamclients/public/api/verifWhatsAppTransTraite');
        return $request->results;
    }
    
    public function recupCallBackService(Request $request){
        if(!empty($request->results)){
            $data = $request->results;
            $report = WhatsAppBackUp::where('messageId', $data[0]['messageId'])->first();
            if($report){
                $report->update([
                    'seen_at' =>json_encode($data)
                ]);
            }else{
                $backup = new WhatsAppBackUp([
                    'messageId' => $data[0]['messageId'] ,
                    'sender'=> '24102535748',
                    'channel'=> 'whatsapp',
                    'recipeint'=> $data[0]['to'],
                    'price'=> json_encode($data[0]['price']),
                    'status'=> $data[0]['status']['groupName'],
                    'send_at'=> $data[0]['sentAt'],
                    'done_at'=> $data[0]['doneAt'],
                    'bulkId'=> $data[0]['bulkId'],
                    'error'=> json_encode($data[0]['error'])
                ]);
                $backup->save();
            }
            $backup = $this->getBackUpwhatsappServices($data[0]['messageId']);
        }
       
        return $request->results;
    }
    
    public function recupCallBackMyPay(Request $request){
        Http::get('https://gampay.app/gamclients/public/api/verifWhatsAppTransTraite');
        if(!empty($request->results)){
            $data = $request->results;
            $report = WhatsAppBackUp::where('messageId', $data[0]['messageId'])->first();
            if($report){
                $report->update([
                    'seen_at' =>json_encode($data)
                ]);
                if($report->status != 'UNDELIVERABLE'){

                    $customer = Historiquetrans::where('etat', $data[0]['messageId'])->first();
                    if($customer){
                        Customer::where('id', $customer->id)->update([
                            //'color' => 'open'
                        ]);
                    }
                }
            }else{
                $backup = new WhatsAppBackUp([
                    'messageId' => $data[0]['messageId'] ,
                    'sender'=> '24102535748',
                    'channel'=> 'whatsapp',
                    'recipeint'=> $data[0]['to'],
                    'price'=> json_encode($data[0]['price']),
                    'status'=> $data[0]['status']['groupName'],
                    'send_at'=> $data[0]['sentAt'],
                    'done_at'=> $data[0]['doneAt'],
                    'bulkId'=> $data[0]['bulkId'],
                    'error'=> json_encode($data[0]['error']),
                ]);
                $backup->save();

                if($data[0]['status']['groupName'] == 'DELIVERED'){
                    $customer = Historiquetrans::where('etat', $data[0]['messageId'])->first();
                    if($customer){
                        Customer::where('id', $customer->id)->update([
                            //'color' => 'delivered'
                        ]);
                    }
                }
            }
        }

        return $request->results;
    }
    
    public function sendTemplateMessage(Request $request){

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
                      "to": "'.$request->whatsapp.'",
                      "messageId": "'.$request->messageId.'",
                      "content": {
                        "templateName": "rm",
                        "templateData": {
                          "body": {
                            "placeholders": ["'.$request->customer_name.'", "'.$request->amount.'", "'.$request->partner.'"]
                          },
                          "header": {
                            "type": "VIDEO",
                            "mediaUrl": "https://gampay.app/video_flow/rm.mp4"
                          },
                          "buttons": [
                            {"type": "QUICK_REPLY", "parameter": "credit"},
                            {"type": "QUICK_REPLY", "parameter": "transfert"},
                            {"type": "QUICK_REPLY", "parameter": "assistance"},
                            {"type": "QUICK_REPLY", "parameter": "useapp"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "https://gampay.app/gamclients/public/api/recupCallBack"
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
    
    
    public function sendTemplateMessageTest(Request $request){

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
                      "to": "'.$request->whatsapp.'",
                      "messageId": "'.$request->messageId.'",
                      "content": {
                        "templateName": "offre",
                        "templateData": {
                          "body": {
                            "placeholders": []
                          },
                          "header": {
                            "type": "IMAGE",
                            "mediaUrl": "'.$request->file.'"
                          },
                          "buttons": [
                            {"type": "QUICK_REPLY", "parameter": "reclam"},
                            {"type": "QUICK_REPLY", "parameter": "ussd"},
                            {"type": "QUICK_REPLY", "parameter": "transferts"},
                            {"type": "QUICK_REPLY", "parameter": "recharge"},
                            {"type": "QUICK_REPLY", "parameter": "visa"},
                            {"type": "QUICK_REPLY", "parameter": "carte"},
                            {"type": "QUICK_REPLY", "parameter": "Mo"},
                            {"type": "QUICK_REPLY", "parameter": "dubai"},
                            {"type": "QUICK_REPLY", "parameter": "sim"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "https://gampay.app/gamclients/public/api/recupCallBack"
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
    
    public function verifWhatsAppMessagebackup(Request $request){
        if(!empty($request->latence)){
            sleep(5);
        }
        $backup = WhatsAppBackUp::where('messageId', $request->mess)->first();
        if($backup){
            if(!empty($backup->seen_at)){
                $status = 'open';
            }else{
                $status = 'delivered';
            }
            Historiquetrans::where('id', $request->trans)->update([
                'reference' => $status
            ]);
        }else{
            $trans = Historiquetrans::where('id', $request->trans)->first();
            Historiquetrans::where('id', $request->trans)->update([
                'operation' => $trans->param9,
                'content' => $trans->param2,
                'etat' => 'atraiter'
            ]);
        }
        return 1;
    }
    
    
    public function getTransRmWhatappIn($whatsapp){
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
        $trans = Historiquetrans::where('numclient', $phone)->where('operation', 'recharge_compte_gam')->where('content', 'whatsapp')->where('tentative_effectue', '0')->orderBy('id', 'desc')->first();
        if($trans){
            $myCustomer = Customer::where('phoneclient', $trans->numclient)->first();
            $partenaire = Customer::where('phoneclient', $trans->phonevendeur)->first();

            if($myCustomer){
                $status = 'old';
                Customer::where('id',$myCustomer->id)->update([
                    'solde' => strval(doubleval($myCustomer->solde) + doubleval($trans->montant_sans_frais)),
                    'whatsapp' => $whatsapp
                ]);
                $this->sendNotificationInprogram($partenaire->phoneclient, $myCustomer->phoneclient,'rm_3.0', $trans->montant_sans_frais,null,null, null);
                $compte = $myCustomer;
            }else{
                $customer = new Customer([
                    'nom'=> 'client',
                    'prenom'=> 'GAM',
                    'pays'=> '+241',
                    'phoneclient'=> $phone,
                    'solde'=> $trans->montant_sans_frais,
                    'mdpclient'=> '1234',
                    'option1'=> $partenaire->option1,
                    'code_confirm'=> $partenaire->code_confirm,
                    'whatsapp'=> $whatsapp,
                    'satus'=>1
                ]);
                $customer->save();
                Http::get('https://gampay.app/gamclients/public/api/recupIdCustomer');
                $compte = $customer;
                $status = 'new';
                $this->sendNotificationInprogram($phone,$phone,'info', '',"Bienvenu sur GamPay, Votre compte a été créé par $partenaire->nom",null,null);

            }

            Historiquetrans::where('id', $trans->id)->update([
                'numclient' => $compte->phoneclient,
                'reference' => 'open',
                'tentative_effectue'=> '1',
            ]);

            return [
                'customer' => $compte->id,
                'phoneclient' => $compte->phoneclient,
            ];
        }else{
            return 0;
        }
    }
    
    
    // requête interface filles
    public function sendmessageCustomerSleeping(Request $request){
        if(!empty($request->count)){
            ProgramSubscription::where('phoneclient', '074582442')->update([
               'param5'=>$request->count,
               'param6'=>'0'
            ]);
        }
        //$customers = Client2::where('etat', 'dodo')->whereNull('grade')->take(1)->get();
        $customers = Client2::whereIn('numclient', ['074582442'])->get();
        if(count($customers)){
            foreach ($customers as $customer){
                $messageId = 'rc#'. $customer->num. ''. time();
                $whatsapp = '241'. substr($customer->numclient, 1);

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
                              "to": "'. $whatsapp.'",
                              "messageId": "'.$messageId.'",
                              "content": {
                                "templateName": "offre",
                                "templateData": {
                                  "body": {
                                    "placeholders": []
                                  },
                                  "header": {
                                    "type": "IMAGE",
                                    "mediaUrl": "https://gampay.org/services.PNG"
                                  },
                                  "buttons": [
                                    {"type": "QUICK_REPLY", "parameter": "reclam"},
                                    {"type": "QUICK_REPLY", "parameter": "ussd"},
                                    {"type": "QUICK_REPLY", "parameter": "transferts"},
                                    {"type": "QUICK_REPLY", "parameter": "recharge"},
                                    {"type": "QUICK_REPLY", "parameter": "visa"},
                                    {"type": "QUICK_REPLY", "parameter": "carte"},
                                    {"type": "QUICK_REPLY", "parameter": "Mo"},
                                    {"type": "QUICK_REPLY", "parameter": "dubai"},
                                    {"type": "QUICK_REPLY", "parameter": "sim"}
                                  ]
                                },
                                "language": "fr"
                              },
                              "notifyUrl": "https://gampay.app/gamclients/public/api/recupCallBackService"
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
                             $reponse =  1;
                         }else{
                                $reponse = 0;
                         }
                    }else{
                           $reponse = 10;
                    }
                }else{
                      $reponse = 100;
                }
                // Close handle
                curl_close($curl);
                
                $data = [
                    'messageId' => $messageId,
                    'send' => 'ok',
                    'done' => 'ko',
                    'seen' => 'ko',
                ];
                Client2::where('num', $customer->num)->update(['grade' => json_encode($data)]);
            }
            $nbr = ProgramSubscription::where('phoneclient', '074582442')->first();
            ProgramSubscription::where('phoneclient', '074582442')->update([
                'param6'=> strval(intval($nbr->param6) + count($customers))
            ]);
        }
        
        return [ $reponse, $customers];
    }

    public function getCustomerSleepingSendMessage(Request $request){
        $nbr = ProgramSubscription::where('phoneclient', '074582442')->first();
        $customers = DB::select("select * from client2 where grade like '%rc#%' or grade like '%rccust#%'");
        if(count($customers)>0){
            return response()->json([
                'statut'=> true,
                'body'=>$customers,
                'but' => $nbr->param5,
                'actu' => $nbr->param6,
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'body'=>'0',
            ]);
        }
    }
    
    public function addcustomerOnlist(){
        $customers = Client2::where('etat', 'dodo')->whereNull('grade')->take(2)->get();
        foreach ($customers as $customer){
            $messageId = 'rc#'. $customer->num. ''. time();
            $data = [
                'messageId' => $messageId,
                'send' => 'ok',
                'done' => 'ko',
                'seen' => 'ko',
            ];
            Client2::where('num', $customer->num)->update(['grade' => json_encode($data)]);
        }

    }
    
    public function getBackUpwhatsappServices($messageId){
        $backUp = WhatsAppBackUp::where('messageId', $messageId)->get();
        if(count($backUp)>0){
            foreach ($backUp as $back){
                if($back->status == 'DELIVERED'){
                    WhatsAppBackUp::where('id', $back->id)->update([
                        'param2'=>'traite'
                    ]);
                    if(!empty($back->seen_at)){
                        $seen = 'ok';
                    }else{
                        $seen = 'ko';
                    }
                    $data = [
                        'messageId' => $back->messageId,
                        'done' => 'ok',
                        'seen' => $seen,
                    ];
    
                    $phoneSansIndicatif = substr($back->recipeint, 3);
                    $client = Client2::where('numclient', '0'.$phoneSansIndicatif)->orWhere('numexpediteur',$phoneSansIndicatif)->first();
                    if($client){
                        Client2::where('num', $client->num)->update([
                            'grade'=> json_encode($data)
                        ]);
                    }
                }
            }
        }
    }
    
    public function orientationScydCustomer(Request $request){
        if($request->whatsapp){
            $numWhats = $request->whatsapp;
            $phoneSansIndicatif = substr($numWhats, 3);
            $debuNum = $phoneSansIndicatif[0] . '' . $phoneSansIndicatif[1];
            if (strlen($phoneSansIndicatif) == 8) {
                if (in_array($debuNum, ['74', '04'])) {
                    $phone = '074' . substr($phoneSansIndicatif, 2);
                    $whatsapp1 = '24174'. substr($phoneSansIndicatif, 2);
                    $whatsapp2 = '24104'. substr($phoneSansIndicatif, 2);
                } elseif (in_array($debuNum, ['77', '07'])) {
                    $phone = '077' . substr($phoneSansIndicatif, 2);
                    $whatsapp1 = '24177'. substr($phoneSansIndicatif, 2);
                    $whatsapp2 = '24107'. substr($phoneSansIndicatif, 2);
                } elseif ($debuNum == '76') {
                    $phone = '076' . substr($phoneSansIndicatif, 2);
                    $whatsapp1 = '24176'. substr($phoneSansIndicatif, 2);
                    $whatsapp2 = '24176'. substr($phoneSansIndicatif, 2);
                } elseif (in_array($debuNum, ['66', '06'])) {
                    $phone = '066' . substr($phoneSansIndicatif, 2);
                    $whatsapp1 = '24166'. substr($phoneSansIndicatif, 2);
                    $whatsapp2 = '24106'. substr($phoneSansIndicatif, 2);
                } elseif (in_array($debuNum, ['62', '02'])) {
                    $phone = '062' . substr($phoneSansIndicatif, 2);
                    $whatsapp1 = '24162'. substr($phoneSansIndicatif, 2);
                    $whatsapp2 = '24102'. substr($phoneSansIndicatif, 2);
                } elseif (in_array($debuNum, ['65', '05'])) {
                    $phone = '065' . substr($phoneSansIndicatif, 2);
                    $whatsapp1 = '24165'. substr($phoneSansIndicatif, 2);
                    $whatsapp2 = '24105'. substr($phoneSansIndicatif, 2);
                }elseif ($debuNum =='60'){
                    $phone = '060'. substr($phoneSansIndicatif, 2);
                    $whatsapp1 = '24160'. substr($phoneSansIndicatif, 2);
                    $whatsapp2 = '24160'. substr($phoneSansIndicatif, 2);
                } else {
                    $phone = $phoneSansIndicatif;
                    $whatsapp1 = $request->whatsapp;
                    $whatsapp2 = $request->whatsapp;
                }
            } else {
                $phone = $phoneSansIndicatif;
                $whatsapp1 = $request->whatsapp;
                $whatsapp2 = $request->whatsapp;
            }

            $myCustomer = Customer::whereIn('whatsapp', [$whatsapp1,$whatsapp2, $phone])->orWhere('phoneclient', $phone)->first();
            if($myCustomer){
                $compte = $myCustomer;
            }else{
                $customer = new Customer([
                    'nom'=> 'client',
                    'prenom'=> 'GAM',
                    'pays'=> '+241',
                    'phoneclient'=> $phone,
                    'solde'=> '0',
                    'mdpclient'=> '1234',
                    'option1'=> 'reveil',
                    'code_confirm'=> 'reveil',
                    'whatsapp'=> $request->whatsapp,
                    'satus'=>1
                ]);
                $customer->save();
                Http::get('https://gampay.app/gamclients/public/api/recupIdCustomer');
                $compte = $customer;
                $this->sendNotificationInprogram($phone,$phone,'info', '',"Bienvenu dans l'univers GamPay",null,null);

            }

            $backup = WhatsAppBackUp::where('messageId', 'like', 'rc#%')->where('id', '>', 10852)->whereIn('recipeint', [$whatsapp1, $whatsapp2])->first();
            if(!empty($backup)){
                WhatsAppBackUp::where('id', $backup->id)->update([
                    'service' => $request->service,
                    'offre' => $request->offre,
                    'seen_at'=> date('Y-m-d H:i:s')
                ]);
                
                $deletedDuplicate= $this->deletedDuplicateWhatsappBackup($backup->id, $backup->recipeint);
            }

            return redirect("https://app.gampay.org/?log=$compte->id&v=$request->offre");
        }else{
            return redirect('https://app.gampay.org/?v=vue&p=2280618');
        }
    }
    
    // end
    
    
    // alertes script
    public function getAlerteTransactions(){
        $this->getAlerteReclamations();
        Http::get('https://gampay.app/gamclients/public/api/alerteSoldesOperations?operation=achat_credit&seuil=20000');
        $now = date('Y-m-d H:i:s');
        $timeAlerte = date('Y-m-d H:i:s', strtotime("$now -2 minutes"));
        $alertes = Historiquetrans::whereNull('timestamps')->whereNotIn('etat', ['CONFIRMEE', 'confirme', 'attend', 'en_attente', 'en_agence_attend', 'atraiter', 'TRAITE', 'a_valider', 'null','numero','ID'])->where('id', '>', 36806659)->whereNotIn('numclient', ['06550385112'])->whereNotIn('operation', ['recharge_compte_gam', 'transfert_gam'])->where('created_at', '<', $timeAlerte)->take(5)->get();
        if(count($alertes)>0){
            foreach ($alertes as $alerte) {
                $message = "*$alerte->operation* de *$alerte->montant_sans_frais FCFA* operateur *$alerte->content* pour le *$alerte->phonevendeur* destinée au *$alerte->numclient* effectuée via *$alerte->origine_operation* avec etat *$alerte->etat* depuis plus de 2 minute. Créée le *$alerte->created_at*  time : $timeAlerte";
                $send = 1;
                
                if((str_contains($alerte->etat, '2.Depot') && $alerte->operation == 'rendu_monnaie_simple') ){
                    Historiquetrans::where('id', $alerte->id)->update([
                        'etat'=> 'atraiter',
                        'operation'=> 'achat_credit',
                        'content' => $alerte->content == 'MOBICASH'? 'LIBERTIS':'AIRTEL_GA',
                        'timestamps' => date('Y-m-d H:i:s')
                    ]);
                    $send = 0;
                }else if(in_array($alerte->operation, ["rendu_monnaie_simple", "transfert_mobile"]) && $alerte->content == 'MOBICASH' && $alerte->etat == 'demande'){
                    $data = $this->getDataRmNumclient($alerte->numclient);
                    if(count($data)>0){
                        if((empty($data[0]['nom']) || str_contains($data[0]['nom'], 'ntrer PIN') || str_contains($data[0]['nom'], 'pas correc')) && $alerte->operation == "rendu_monnaie_simple"){
                            $send = 0;
                            $this->reloadTrans($alerte->id, 'achat_credit', $alerte->content);
                        }else{
                            $soldeMoov = Soldes::where('id', 36)->first();
                            if($soldeMoov->solde >= intval($alerte->montant_sans_frais)){
                                Historiquetrans::where('id', $alerte->id)->update([
                                    'etat'=> 'CONFIRMEE',
                                    'timestamps' => date('Y-m-d H:i:s')
                                ]);
                                $send = 0;
                            }
                        }
                    }
                }else if($alerte->operation == 'rendu_monnaie_simple' && $alerte->etat == 'AREGARDER'){
                    $data = $this->getDataRmNumclient($alerte->numclient);
                    if(count($data)>0){
                        if(empty($data[0]['nom']) || str_contains($data[0]['nom'], 'ntrer PIN') || str_contains($data[0]['nom'], 'pas correc')){
                            $send = 0;
                            $this->reloadTrans($alerte->id, 'achat_credit', $alerte->content);
                        }
                    }
                }

                if($send==1){
                    Historiquetrans::where('id', $alerte->id)->update([
                        'timestamps' => date('Y-m-d H:i:s')
                    ]);
                    if($alerte->operation == 'transfert_visa'){
                         Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=international&message=".$message);
                    }else{
                         Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=".$message);
                    }
                }
                
            }
        }
        return [$timeAlerte , $alertes];
    }
    
    public function getAlertePaiementPartenaire(){
        $now = date('Y-m-d H:i:s');
        $timeAlerte = date('Y-m-d H:i:s', strtotime("$now -5 minute"));
        $alertes = Historiquetrans::whereNull('timestamps')->where('etat','attend')->where('id', '>', 37505212)->whereNotIn('numclient', ['06550385112'])->whereIn('operation', ['paiement_partenaire'])->where('created_at', '<', $timeAlerte)->take(5)->get();
        if(count($alertes)>0){
            foreach ($alertes as $alerte) {
                $transOk =  Historiquetrans::whereIn('etat',['CONFIRMEE', 'confirme'])->where('phonevendeur', $alerte->phonevendeur)->whereIn('operation', ['paiement_partenaire'])->get();
                if(count($transOk)==0){
                    $customer = Customer::where('phoneclient', $alerte->phonevendeur)->first();
                    $message = "*$alerte->operation* de *$alerte->montant FCFA* pour *$alerte->content* effectué par le *$alerte->phonevendeur* via *$alerte->param2* avec etat *$alerte->etat* ";
                    //$this->sendWhatsAppMessage('24174582442',$message, 'other');
                    $this->sendWhatsAppGroupMessage($message, 'GAM Service client (4)', 'other');
                    Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=".$message);
                    Http::get('https://gampay.org/gamclients/public/api/sendcustomer1XBET?customer='.$alerte->phonevendeur);

                }
                Historiquetrans::where('id', $alerte->id)->update([
                    'timestamps' => date('Y-m-d H:i:s')
                ]);
            }
        }
       
        $this->getAlertePaiementPartenaireValide();
        return [$timeAlerte , $alertes, ];
    }
    
    public function getAlerteRechargeVisa(){
        //Http::get("https://gampay.org/gamclients/public/api/sendWhatshappRequest?numero=24174582442&message=hello");
        $now = date('Y-m-d H:i:s');
        $timeAlerte = date('Y-m-d H:i:s', strtotime("$now -1 second"));
        $alertes = Historiquetrans::whereNull('timestamps')->where('etat', 'en_attente')->where('id', '>', 36909632)->where('operation', 'recharge_visa_uba')->where('updated_at', '<', $timeAlerte)->take(5)->get();
        if(count($alertes)>0){
            foreach ($alertes as $alerte) {
                Historiquetrans::where('id', $alerte->id)->update([
                    'timestamps' => date('Y-m-d H:i:s')
                ]);
                $message = "*Recharge visa* de *$alerte->montant FCFA* effectué par le *$alerte->phonevendeur* pour le compte *$alerte->content* effectuée via *$alerte->origine_operation* en attente de traitement. Créée le *$alerte->updated_at*";
                $type = "Sigma";
                if (str_contains($alerte->origine_operation, 'flutterApp')) {
                    $type = "GamPay";
                }
                
                $message ="Nouvelle recharge visa *$type* de *$alerte->montant FCFA*, compte *$alerte->content* en attente ";
                
                Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=traitement&message=".$message);
                
                /*Http::get("https://gampay.org/gamclients/public/api/sendWhatshappRequest?numero=24174582442&message=".$message);
                Http::get("https://gampay.org/gamclients/public/api/sendWhatshappRequest?numero=24176520541&message=".$message);
                Http::get("https://gampay.org/gamclients/public/api/sendWhatshappRequest?numero=24102621171&message=".$message);
                Http::get("https://gampay.org/gamclients/public/api/sendWhatshappRequest?numero=24102934942&message=".$message);
                Http::get("https://gampay.org/gamclients/public/api/sendWhatshappRequest?numero=24104471892&message=".$message);
                Http::get("https://gampay.org/gamclients/public/api/sendWhatshappRequest?numero=24104071340&message=".$message);*/
                //$this->sendWhatsAppGroupMessage($message, 'Sygma (2)', 'other');
                
            }
        }
        $this->getAlerteCommandeVisa();
        return [$timeAlerte , $alertes];
    }
    
    public function getAlerteCommandeVisa(){
        $now = date('Y-m-d H:i:s');
        $alertes = Historiquetrans::whereNull('timestamps')->whereIn('etat', ['confirme', 'CONFIRMEE', 'en_attente', 'atraiter'])->where('id', '>', 36980420)->where('operation', 'achat_visa_uba')->take(5)->get();
        if(count($alertes)>0){
            foreach ($alertes as $alerte) {
                Historiquetrans::where('id', $alerte->id)->update([
                    'timestamps' => date('Y-m-d H:i:s')
                ]);
                $message = "*Commande carte visa* effectué par le *$alerte->phonevendeur* le *$alerte->created_at*";
                $this->sendWhatsAppGroupMessage($message, 'Sygma (2)', 'other');
                Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=traitement&message=".$message);
            }
        }
        
        return  $alertes;
    }
    
    public function getAlertePaiementPartenaireValide(){
        $alertes = DB::select("SELECT * from historiquetrans h where (timestamps is null or timestamps != 'alerte') and operation ='debit' and numclient  not in ('06550385112') and etat = 'CONFIRMEE' and id > 37624759 limit 5");
        //return $alertes;
        if(count($alertes)>0){
            foreach ($alertes as $alerte) {
                if($alerte->operation == 'debit'){
                   $message = "*$alerte->operation* de *$alerte->montant FCFA* pour *$alerte->content* effectué par le *$alerte->numclient* confirmée* ";
                   
                   Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=".$message);
                }else{
                   $message = "*$alerte->operation* de *$alerte->montant FCFA* pour *$alerte->content* effectué par le *$alerte->phonevendeur* via *$alerte->param2* confirmée* ";
                   //$this->sendWhatsAppGroupMessage($message, 'GAM Service client (4)', 'other'); 
                }
            }
            Historiquetrans::where('id', $alerte->id)->update([
                'timestamps' => 'alerte'
            ]);
        }
        return $alertes;
    }
    
    
    public function confirmeRecupTransScyd(){
        $transScyd = Historiquetrans::where('origine_operation', 'scyd')->where('param8', 'success')->take(5)->get();
        if(count($transScyd)){
            foreach ($transScyd as $trans){
                $recharge =  Historiquetrans::where('operation', 'recharge_compte_gam')->where('montant', $trans->montant)->where('numclient', $trans->phonevendeur)->where('content', 'whatsapp')->whereIn('etat', ['open', 'delivered'])->first();
                if(!empty($recharge)){
                    Historiquetrans::where('id', $recharge->id)->update([
                        'reference' => 'success'
                    ]);
                }
                Historiquetrans::where('id', $trans->id)->update([
                    'param8' => 'confirme'
                ]);

            }
        }
        return $transScyd;
        
    }
    
    public function deletedDuplicateWhatsappBackup($id,$recipeint){
     
        $duplicateBackUp = DB::select("select * from whatsapp_backup wb where messageId like 'rc#%' and (id > 10852 and id <> $id) and recipeint = $recipeint and deleted_at is null order by id desc");
        
        if(count($duplicateBackUp)>0){
            foreach ($duplicateBackUp as $back){
                WhatsAppBackUp::where('id',$back->id)->delete();
            }
        }
         
        return 1;
    }
    
    
    public function rembourseRmwhatsappLoading(){
        $attente = date("Y-m-d H:i:s", strtotime('-6 hours'));
        //return $attente;
        $rms = DB::select("select * from historiquetrans where operation ='recharge_compte_gam' and id >37269913 and content ='whatsapp' and param5 is null and tentative_effectue = '0' and created_at < '$attente' and deleted_at is null limit 5");
        if(count($rms)>0){
            foreach ($rms as $rm){
                Historiquetrans::where('id', $rm->id)->update([
                    'param5'=> 'loading_repay_rm'
                ]);
                $backup = WhatsAppBackUp::where('messageId', $rm->param4)->first();
                if($backup){
                    $numWhatsApp = $backup->recipeint;
                    $messageId = 'repay'.$rm->id.''.time();
                    $operation = $rm->param9 =='rendu_monnaie_simple'? 'transfert mobile' : 'achat de credit';
                    $message = "Vous n'avez toujours pas récupéré votre monnaie. Notre système vous rembousera automatiquement en $operation de $rm->montant_sans_frais F vers le $rm->numclient dans une heure, si vous ne le faites pas vous même.";
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
                                  "to": "'.$numWhatsApp.'",
                                  "messageId": "'.$messageId.'",
                                  "content": {
                                    "templateName": "repayi",
                                    "templateData": {
                                      "body": {
                                        "placeholders": ["'.$message.'"]
                                      },
                                     "header": {
                                        "type": "IMAGE",
                                        "mediaUrl": "https://gampay.org/assistance.jpg"
                                      },
                                      "buttons": [
                                        {"type": "QUICK_REPLY", "parameter": "creditrm200000026532619"},
                                        {"type": "QUICK_REPLY", "parameter": "transfertrm200000026532619"},
                                        {"type": "QUICK_REPLY", "parameter": "assistance200000026532619"},
                                        {"type": "QUICK_REPLY", "parameter": "app200000026532619"}
                                      ]
                                    },
                                    "language": "fr"
                                  },
                                  "notifyUrl": "https://gampay.app/gamclients/public/api/recupCallBack"
                                }
                              ]
                            }',
                            CURLOPT_HTTPHEADER => array(
                                'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                                'Content-Type: application/json',
                                'Accept: application/json'
                            ),
                        ));
                    /*
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
                                  "to": "'.$numWhatsApp.'",
                                  "messageId": "'.$messageId.'",
                                  "content": {
                                    "templateName": "rmrepay",
                                    "templateData": {
                                        "body": {
                                            "placeholders": ["'.$message.'"]
                                        },
                                        "header": {
                                            "type": "IMAGE",
                                            "mediaUrl": "https://gampay.org/assistance.jpg"
                                        },
                                        "buttons": [
                                            {"type": "QUICK_REPLY", "parameter": "credit"},
                                            {"type": "QUICK_REPLY", "parameter": "transfert"},
                                            {"type": "QUICK_REPLY", "parameter": "assistance"},
                                            {"type": "QUICK_REPLY", "parameter": "useapp"}
                                        ]
                                    },
                                    "language": "fr"
                                  },
                                  "notifyUrl": "https://gampay.app/gamclients/public/api/recupCallBack"
                                }
                              ]
                            }',
                            CURLOPT_HTTPHEADER => array(
                                'Authorization: App 7403e849cd341d0de2636b2948beaa58-000d00ea-4452-432a-81c9-f7c83aebbb15',
                                'Content-Type: application/json',
                                'Accept: application/json'
                            ),
                        ));
                    */
                    curl_exec($curl);
                }
            }
        }
        $this->whatsappRmRepay();
        return [$attente, $rms];
    }

    public function whatsappRmRepay(){
        $myTime = date("Y-m-d H:i");
        $now = date('Y-m-d');
       
        if(str_contains($myTime, '00:')){
            $rms = DB::select("select * from historiquetrans where operation ='recharge_compte_gam' and id >37269913 and content ='whatsapp' and param5 is null and param7 is null and deleted_at is null limit 5");
             // reload count trans partenaire
            $partenaires = DB::select("select * from customer where username ='partenaire' and (flp_nbr_transaction not like '%$now%' or flp_nbr_transaction is null) limit 10");
            if(count($partenaires)>0){
                foreach ($partenaires as $partenaire){
                    if(!empty($partenaire->flp_nbr_transaction)){
                        $myTab = json_decode($partenaire->flp_nbr_transaction, true);
                        $newTab = array_merge($myTab, ["".$now."" => $partenaire->stock_gab]);
                    }else{
                        $newTab = ["".$now."" => $partenaire->stock_gab];
                    }

                    Customer::where('id', $partenaire->id)->update([
                        'stock_gab'=> 0,
                        'flp_nbr_transaction' => json_encode($newTab) 
                    ]);
                }
            }
            
        }elseif(str_contains($myTime, '06:01') || str_contains($myTime, '06:02')){
            $this->getCumulCollecteur();
            $attente = date("Y-m-d H:i:s", strtotime('-1 hour'));
            $rms = DB::select("select * from historiquetrans where operation ='recharge_compte_gam' and id >37269913 and content ='whatsapp' and param5 = 'loading_repay_rm' and param7 is null and updated_at < '$attente' and deleted_at is null limit 5");
        }else{
            $attente = date("Y-m-d H:i:s", strtotime('-1 hour'));
            $rms = DB::select("select * from historiquetrans where operation ='recharge_compte_gam' and id >37269913 and content ='whatsapp' and param5 = 'loading_repay_rm' and param7 is null and updated_at < '$attente' and deleted_at is null limit 5");
        }
        if(count($rms)>0){
            foreach ($rms as $rm) {
                Historiquetrans::where('id', $rm->id)->update([
                    'param5' => 'repay_rm',
                    'param2' => NULL,
                    'operation' => $rm->param9,
                    'content' => $rm->param2,
                    'etat' => 'atraiter'
                ]);
            }
        }
        $this->rmWhatsappHelpCustomer();
        return $rms;
    }
    
    public function rmWhatsappHelpCustomer(){
        $attente = date("Y-m-d H:i:s", strtotime('-10 minutes'));
        $rms = DB::select("select * from historiquetrans where operation ='recharge_compte_gam' and id >37269913 and content ='whatsapp' and tentative_effectue ='0' and (param7 like '%achat_credit%' or param7 like '%transfert_mobile%') and updated_at < '$attente' and deleted_at is null limit 5");
        if(count($rms)>0){
            foreach ($rms as $rm) {
                $customer = Customer::where('phoneclient', $rm->numclient)->first();
                if($customer){
                    
                    if(doubleval($customer->solde) >= doubleval($rm->montant_sans_frais)){
                        Customer::where('id',$customer->id)->update([
                            'solde' => strval(doubleval($customer->solde) - doubleval($rm->montant_sans_frais))
                        ]);
                        $historiqueTrans = new Historiquetrans([
                            'operation' => $rm->param9,
                            'reference' => 'AUTO',
                            'etat' => 'atraiter',
                            'numclient' => $rm->numclient,
                            'phonevendeur' => $rm->numclient,
                            'content'=> $this->getOperateur($rm->param9, $rm->numclient, '241'),
                            'montant' =>$rm->montant_sans_frais,
                            'montant_sans_frais' => $rm->montant_sans_frais,
                            'frais' => '0',
                            'solde' => $customer->solde,
                            'origine_operation' => 'scyd',
                            'id_customer' => $customer->id,
                            'tentative_effectue' => '0',
                            'param9' => $rm->param9 =='transfert_mobile'? 'banking' :'customer',
                            'param5' => 'help_rm',

                        ]);
                        $historiqueTrans->save();
                        
                        Historiquetrans::where('id', $rm->id)->update([
                            'param5' => 'help_rm',
                            'tentative_effectue' => '1',
                        ]);  
                    }
                }
               
            }
        }
        return $rms;
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
            "label"=> "customer"
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://app.timelines.ai/integrations/api/messages");
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
    
    /*public function sendWhatsAppGroupMessageIn($message, $groupe, $service){
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

        // Close handle
        curl_close($ch);
    }*/
    
    public function getDataRmNumclient($numclient){
        $data = CustomerComplement::where('phoneclient', $numclient)->get();
        if (count($data)==0){
            $newData = new CustomerComplement([
                'phoneclient' => $numclient,
                'id_customer' => 12354
            ]);
            $newData->save();
            return [];
        }
        return $data;
    }
    
    public function reloadTrans($id, $operation, $content){
        Historiquetrans::where('id', $id)->update([
            'etat'=> 'atraiter',
            'operation'=> $operation,
            'content' => $content == 'MOBICASH'? 'LIBERTIS':'AIRTEL_GA',
            'timestamps' => date('Y-m-d H:i:s')
        ]);
    }
    
    
    public function getOperateur($operation, $numero, $pays){
        $debut = $numero[0].''.$numero[1].''.$numero[2];
        if(in_array($debut, ['074', '076', '077']) and  in_array($pays,['+241', '241'])){
            if($operation == 'achat_credit'){
                $operateur = 'AIRTEL_GA';
            }else{
                $operateur = 'AIRTEL MONEY';
            }
        }elseif (in_array($debut, ['066', '062', '060', '065']) and  in_array($pays,['+241', '241'])){
            if($operation == 'achat_credit'){
                $operateur = 'LIBERTIS';
            }else{
                $operateur = 'MOBICASH';
            }
        }else{
            $operateur = 'international';
        }

        return $operateur;
    }
    
    public function testNotifGroup(Request $request){
        $message = "message dans le groupe";
        return $this->sendWhatsAppGroupMessage($message, $request->groupe, 'other');
    }
    
    public function sendWhatsapCustmerSuivi(){
        $customers = DB::select("select * from customer where phoneclient in (select DISTINCT phonevendeur from historiquetrans h  where  operation in ('transfert_mobile','recharge_visa_uba', 'achat_wifi') and id >37239936 and etat not in ('attend', 'en_agence_attend') and origine_operation not in ('GAB', 'AGENCE_SIGMA','scyd') and phonevendeur not in ('077669452','076328458','077524683','074119984','077727842','065370384','066463853','062856272','066656665','062958678','062792513','066315718','077014619','074708344','077413017','077959673','066703816','077976865','074069040','074591381','077112938','077693160','077326850','062250625','077036305','074197659','077964878','062178972','066884094','074298647','074169955','077525302','074404941','077315562','074401148','076593004','074045819','077061401','066199818','077588307','077591176','077344558','074773030','077398339','074151303','066647573','077317542','062462112','077839590','074015914','077142379','074208460','074187956','062841005','074008332','077967213','076417731','074299215','077747244','077557230','077558990','062632224','077250587','077094763','074388309','077684320','074137762','066496015','+241076372667','074288563','074775958','074331013','077422855','077329087','077560734','074792456','066537539','077239448','074519609','077930572','074698151','074143285','077669754','066852412'))");
        foreach ($customers as $customer){
            Customer::where('id', $customer->id)->update([
                'flp_point_caisse' => 'service_client'
            ]);
        }
        return $customers;
    }
    
    public function getContryIp(Request $request){
        $myIp =  $request->myip;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "ipinfo.io/$myIp?token=5aa25ae6c48863");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer 94171fac-a916-4f7c-b634-0760d146e4f7"));
        $result = curl_exec($ch);// Check HTTP status code
        $response = json_decode($result);
        return $response ;
    }
    
    
    public function assistCustomerChallenge(Request $request){
        $now = date('Y-m-d H:i:s');
        $timeAlerte = date('Y-m-d H:i:s', strtotime("$now -30 minutes"));

        $alertes = Customer::where('statut', 'challenger')->whereNotNull('confirmation_sent_at')->where('confirmation_sent_at', 'like', '2024-07%')->where('confirmation_sent_at', '<', $timeAlerte)->take(5)->get();
        
        if(count($alertes)>0){
            foreach ($alertes as $alerte){
                $messageCustomer = "Cher client, votre participation au challenge GAM a été validée. N'hésitez pas à nous contacter si vous avez des questions. 
                
Retrouvez plus de réponses en rejoignant notre communauté WhatsApp : https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c";
                $this->sendWhatsAppMessage($alerte->whatsapp,$messageCustomer, 'other');
                Customer::where('id', $alerte->id)->update([
                    'confirmation_sent_at' => $request->nomNull 
                ]);
            }
        }
        return [$alertes, $timeAlerte, 1];
    }
    
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
    
    
    public function getCustomerAddcontact(Request $request){
        if(empty($request->id)){
           $customers = DB::select("SELECT * from customer where param1 ='statut_whatsapp' and phoneparent is not null and username is null and deleted_at is null limit 1 ");
           //return count($customers);
           foreach ($customers as $customer){
              return response()->json([
                  'id'=> $customer->id,
                  'name'=> 'cha_'.$customer->phoneparent_.''.$customer->phoneclient,
                  'phone'=>$customer->phoneclient,
                  'whatsapp'=>$customer->whatsapp]);
            }
        }else{
            Customer::where('id', $request->id)->update([
                'param1' => 'add'
            ]);
        }

    }
    
    public function sendTemplateAssist1xbet(Request $request){
        $customer = Customer::where('id', $request->customer)->first();
        if($customer){
            if(empty($request->whatsapp)){
                $numWhatsApp = $customer->whatsapp;
            }else{
                $numWhatsApp = $request->whatsapp;
                $phone = $this->getNumByWhatsApp($request->whatsapp);
                if(in_array($phone, [$customer->phoneclient, '0'.$customer->phoneclient])){
                    Customer::where('id', $customer->id)->update(['whatsapp'=> $request->whatsapp]);
                }
            }
            $message = 'Faire un dépot 1xBET avec GamPay';
            if($request->phone == 'ios'){
                $message = 'Faire un dépot 1xBET avec GamPay. *Utilisez la plateforme web de 1xBET*';
            }

            $messageId = 'assistance1xbet'.$customer->id;
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
                      "to": "'.$numWhatsApp.'",
                      "messageId": "'.$messageId.'",
                      "content": {
                        "templateName": "startassist1xbet",
                        "templateData": {
                          "body": {
                            "placeholders": ["'.$message.'"]
                          },
                         "header": {
                            "type": "VIDEO",
                            "mediaUrl": "https://gampay.app/video_flow/offres.mp4"
                          },
                          "buttons": [
                           {"type": "QUICK_REPLY", "parameter": "am_pay"},
                            {"type": "QUICK_REPLY", "parameter": "moov_pay"},
                            {"type": "QUICK_REPLY", "parameter": "visa_pay"},
                            {"type": "QUICK_REPLY", "parameter": "assistance"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "https://gampay.app/gamclients/public/api/recupCallBack"
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
            return $result;
        }
        return response()->json([
            'statut'=> true,
            'message'=>'Message envoyé',
            'data'=> $customer
        ]);
    }
    
    
   public function getCumulCollecteur(){
        $yesterday =  date('Y-m-d', strtotime("-1 day"));
        
        $venteVisaGamPay = 0;
        $venteVisaGab = 0;
        $ventebet = 0;
        $fraisVisa = 0;
        $fraisBet = 0;
        $bets = Historiquetrans::where('operation', 'paiement_partenaire')->whereNotIn('etat', ['annule', 'attend'])->where('created_at', 'like', "%{$yesterday}%")->get();
        $visas = Historiquetrans::where('operation', 'recharge_visa_uba')->whereNotIn('etat', ['en_agence_attend', 'attend'])->where('created_at', 'like', "%{$yesterday}%")->get();
        //return [$bets, $visas];
        if(count($bets)){
            foreach ($bets as $bet){
                $ventebet = intval($bet->montant) + $ventebet;
                $fraisBet = intval($bet->frais) + $fraisBet;
            }
        }

         if(count($visas)){
            foreach ($visas as $visa){
                if(in_array($visa->origine_operation, ['GAB', 'AGENCE_SIGMA'])){
                    $venteVisaGab = intval($visa->montant) + $venteVisaGab;
                }else{
                    $venteVisaGamPay = intval($visa->montant) + $venteVisaGamPay;
                }
                $fraisVisa = intval($visa->frais) + $fraisVisa;
            }
        }

        $messagegroupe = "Cumul $yesterday Visa GAB/AGENCE : *$venteVisaGab FCFA* Visa GamPay : *$venteVisaGamPay FCFA* 1XBET : *$ventebet FCFA*";

        $message = "Cumul $yesterday

Visa GAB/AGENCE : *$venteVisaGab FCFA*
Visa GamPay : *$venteVisaGamPay FCFA*
Frais Visa $fraisVisa

1XBET : $ventebet
Frais 1xbet : $fraisBet
";
            
        //return [ $messagegroupe, $message];

        //$this->sendWhatsAppMessage('24162440345', $message, 'marketing');
        //$this->sendWhatsAppMessage('24102507006', $messagegroupe, 'marketing');
        $this->sendWhatsAppMessage('24174582442', $messagegroupe, 'marketing');
        //$this->sendWhatsAppGroupMessage($messagegroupe, 'Sygma (2)', 'other');
        Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=traitement&message=".$messagegroupe);
        return [ $messagegroupe, $message];

    }
    
    
    public function getRapportRm(Request $request){
        $yesterday = date("Y-m-d", strtotime('-1 day'));
        //Récupération du compte principal
        if(!empty($request->customer)){
            $partners = Customer::where('id', $request->customer)->orWhere('phoneclient', $request->customer)->get();
        }else{
            $partners = Customer::where('username', 'partenaire')->where('flp_grade','master')->where('remember_created_at', '<>', $yesterday)->take(3)->get();
        }
        if(count($partners)>0){
            foreach ($partners as $partner){
                Customer::where('id', $partner->id)->update([
                    "priority" => 'notif',
                    "remember_created_at"=> $yesterday
                ]);
                // Récupération des comptes associés (caisses)
                $caisses = DB::select("select * from customer where username = 'partenaire' and code_confirm ='".$partner->code_confirm."' and deleted_at is null");
                if(count($caisses)){
                    foreach ($caisses as $caisse){
                        $validates = 0;
                        $validatesAmount = 0;
                        $verify = 0;
                        $verifyAmount = 0;
                        $total = 0;
                        // Récupération des transactions de la caisse
                        $trans = DB::select("select * from historiquetrans h where phonevendeur ='".$caisse->phoneclient."' and numclient <> '".$caisse->phoneclient."' and created_at like '".$yesterday."%'");
                        $nbrTrans =  count($trans);
                        if(count($trans)>0){
                            // Récupération des datails du rapport
                            foreach ($trans as $tran){
                                if($tran->etat == 'CONFIRMEE'){
                                    $validates = $validates +1;
                                    $validatesAmount = $validatesAmount + intval($tran->montant);
                                }elseif (in_array($tran->etat, ['demande', 'TRAITE', 'cour', 'cours', 'null', 'AREGARDER', 'stock', 'atraiter'])){
                                    $verify = $verify +1;
                                    $verifyAmount = $verifyAmount + intval($tran->montant);
                                }
                                $total = $total + intval($tran->montant);
                            }
                        }
                        // Titre de la notification
                        if(count($caisses)>1){
                            $titre = $caisse->prenom .' '. $yesterday;
                        }else{
                            $titre = 'RAPPORT '. $yesterday;
                        }
                        // Construction du message
                        $message = "Nombre transaction : $nbrTrans; Total : $total FCFA
 Transactions validées :  $validates ( $validatesAmount FCFA) ; Transactions à vérifier :  $verify ( $verifyAmount FCFA). " ;
                        // envoi de la notification au compte principal
                        $this->sendNotificationInprogram($partner->phoneclient, $partner->phoneclient, $titre, '',$message, '', '');

                        if($verify > 0){
                            // Alerte groupe GAM
                            $messagegroupe = "$verify transaction(s) à vérifier pour *$caisse->nom  $caisse->prenom  $caisse->phoneclient*";
                            //$this->sendWhatsAppGroupMessage($messagegroupe, 'GAM Service client (4)', 'other');
                            Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=".$messagegroupe);
                        }
                    }
                }
            }
        }
        return $partners;
    }
    
    
    // alertes Reclamation USSD

   public function getAlerteReclamations()
    {
        $alertes = Reclammation::where('objet', 'Reclamation Ussd')->whereNull('param1')->where('statut', 'new')->get();
        if (count($alertes) > 0) {
            foreach ($alertes as $alerte) {
                $messageID = 'erreur_ussd' . time();
                $messageTemplate = "Cher client, votre achat de crédit Libertis de $alerte->montant Fcfa n’a pas abouti car vous avez rentré $alerte->phonebeneficiaire à la place du numéro de téléphone.";
                $sendTemplate = $this->sendTemplateMessageErreurUssd("241" . substr($alerte->phoneclient, 1), $messageID, $messageTemplate);
                //return $sendTemplate;
                if ($sendTemplate == 1) {
                    $trans = GamRechargeHist::where('phonevendeur', $alerte->phoneclient)->where('montant', $alerte->montant)->where('port', '6A')->first();
                    if ($trans) {
                        GamRechargeHist::where('id', $trans->id)->update([
                            'param5' => $messageID
                        ]);
                    }
                    $param1 = $messageID;
                } else {
                    $message = "ERREUR USSD du $alerte->phoneclient a rentré  $alerte->phonebeneficiaire à la place du numéro de téléphone, montant  $trans->montant, référence transaction $trans->reference";
                    $this->sendWhatsAppGroupMessage($message, 'GAM Service client (4)', 'other');
                    Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=".$message);
                    $param1 = 'alerte';
                }

                Reclammation::where('id', $alerte->id)->update([
                    'param1' => $param1
                ]);
            }
        }
        $this->getBackupWhatsappForErrorUssd();
        return [$alertes];
    }

    public function sendTemplateMessageErreurUssd($whatsapp, $messageId, $message)
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
                      "to": "' . $whatsapp . '",
                      "messageId": "' . $messageId . '",
                      "content": {
                        "templateName": "erreure_ussd",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "IMAGE",
                            "mediaUrl": "https://gampay.org/image_flow/newComrm.jpg"
                          },
                          "buttons": [
                            {"type": "QUICK_REPLY", "parameter": "correction_number"},
                            {"type": "QUICK_REPLY", "parameter": "assiste_ussd"}
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
        //return $result;
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
        // Close handle 24104071340 24102621171
        curl_close($curl);
    }

    public function getBackupWhatsappForErrorUssd(){
        $now = date('Y-m-d H:i:s');
        $timeAlerte = date('Y-m-d H:i:s', strtotime("$now -5 minutes"));
        $alertes = Reclammation::where('objet' ,'Reclamation Ussd')->where('param1','like',"erreur_ussd%")->where('updated_at', '<', $timeAlerte)->take(5)->get();
        if(count($alertes)>0){
            foreach ($alertes as $alerte) {
                $statut= "";
                $backup = WhatsAppBackUp::where('messageId', $alerte->param1)->where('status', 'DELIVERED')->first();
                if($backup){
                    $message = "Le $alerte->phoneclient a bien reçu le template pour rectifier son ERREUR USSD";
                    $this->sendWhatsAppGroupMessage($message, 'Travaux objectifs (1)', 'other');
                    Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=work&message=".$message);
                    $statut = "treatment";
                }else{
                    $trans = GamRechargeHist::where('phonevendeur',$alerte->phoneclient )->where('montant', $alerte->montant)->where('port', '6A')->first();
                    if($trans){
                    $message = "ERREUR USSD du $alerte->phoneclient a rentré  $alerte->phonebeneficiaire à la place du numéro de téléphone, montant  $trans->montant, référence transaction $trans->reference";
                    $this->sendWhatsAppGroupMessage($message, 'GAM Service client (4)', 'other');
                    Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=".$message);
                    $statut = $alerte->statut;
                    }
                }
                Reclammation::where('id', $alerte->id)->update([
                    'statut' => $statut,
                    'param1'=> 'alerte'
                ]);
            }
        }
    }
    
    /******************************* shareCom by scyd_Meta **************************************/

  public function createAccount(Request $request)
  {
    $customer = Customer::where('phoneclient', $request->phone)->first();
    if (empty($customer)) {
      $client = new Customer([
        'nom' => 'GAM',
        'prenom' => 'client',
        'phoneclient' => $request->phone,
        'mdpclient' => '1234',
        'option1' => 'shareCom',
        'otp' => 'open',
        'code_confirm' => 'shareCom',
        'whatsapp' => $request->whatsapp,
        'phoneparent' => $request->parrain,
        'status' => 1
      ]);
      $client->save();
    }
  }

  public function shareCom(Request $request)
  {
    $request->typeFile == 'image';
    $templateName = 'com_sharecom_image';
    if ($request->typeFile == 'video') {
      $templateName = 'com_sharecom_video';
    }

    $phone = $this->getNumByWhatsApp($request->whatsapp);

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
          "to": "' . $request->whatsapp . '",
          "type": "template",
          "template": {
              "name": "' . $templateName . '",
              "language": {
                  "code": "fr"
              },
              "components": [
                  {
                      "type": "header",
                      "parameters": [
                          {
                              "type": "' . $request->typeFile . '",
                              "' . $request->typeFile . '": {
                                  "link": "' . $request->file . '"
                              }
                          }
                      ]
                  },
                  {
                      "type": "body",
                      "parameters": [
                          {
                              "type": "text",
                              "text": "' . $request->message . '"
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
        'Authorization: Bearer EAAVx2scjZBYIBQLblhmDpi2E3OsuF0lk0WZBYVbXJupcVL9HkrkNNL9PwI1r45vSp0yPEe2SgYXbacetxxuSV5tXVSnc2KDHlLNUeUnfTwbrZBCMZApAz5hZAHwEiZBAzLbZAvkm6IKLVNx10r8I4lyuLH6rdHsTOcnG9i7uZALsxhhuQGkchJ03r8hfW9qmiGPoRwZDZD',
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
          Http::get("https://gampay.org/clients/public/api/createAccount?phone=$phone&parrain=$request->parrain&whatsapp=$request->whatsapp");

          return response()->json([
            'statut' => true,
            'body' => $response['messages'][0]['id']
          ]);
        } else {
          return response()->json([
            'statut' => false,
            'body' => ''
          ]);
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
  /******************************* end shareCom by scyd_Meta **************************************/
  
  public function getTransTestInterne(){
        $trans = DB::select("SELECT id, operation, numclient as num_receveur, phonevendeur as num_emeteur, content as operateur, montant as montant_a_payer FROM historiquetrans g where phonevendeur = '074201507' and operation in ('data_credit','achat_credit') order by id desc limit 1");
        return $trans;
    }
    
    public function getCustomerAction(Request $request){
        try{
            $customer = ProjetAction::where('projet_action',13)->orderby('id', 'desc')->get();
            if($request->option == 'my_action'){
                $customer = ProjetAction::where('customer',$request->id)->whereNotIn('projet_action',[13])->orderby('id', 'desc')->get();
            }
            
            return response()->json([
                'statut' => true,
                'body' => $customer
              ]);
        }catch (\Throwable $th) {
            return response()->json([
                'statut' => false,
                'body' => null
              ]);
        }
    }
    
    public function getForfaitDataCredit(Request $request)
    {
        $forfaitAM = ForfaitDataCredit::where('operateur', 'airtel')->get();
        $forfaitMoov = ForfaitDataCredit::where('operateur', 'moov')->get();
        $forfaitData = ForfaitDataCredit::whereNotIn('operateur', ['moov','airtel'])->get();
        return response()->json([
            'statut' => true,
            'body' => ['airtel' => $forfaitAM, 'moov' => $forfaitMoov,'link'=>'https://gampay.org/gamclients/public/api/newCallBackAMOfflineAppRequest','internet'=>$forfaitData, 'code' => [
                '0_airtel_achat_credit' => 1,
                '0_moov_achat_credit' => 2,
                '1_airtel_achat_credit' => 3,
                '1_moov_achat_credit' => 4,
                '0_airtel_data_credit' => 5,
                '0_moov_data_credit' => 6,
                '1_airtel_data_credit' => 7,
                '1_moov_data_credit' => 8,
                 'achat_wifi'=> 9
            ]]
        ]);
    }
}
