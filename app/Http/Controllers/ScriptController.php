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
use App\Models\WifiUdm;
use App\Models\ProjetAction;
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

class ScriptController extends Controller
{
    /* Customer Sleeping with pay */

    public function sendTemplateForCustomerUssdSleepingWithPay($segmentation)
    {
        //$segmentation == 'in_customer_and_have_solde'
       $customers = DB::select("SELECT * from client2 where post_Balance is not NULL and transaction_ID is NULL limit 30");
       if(count($customers)>0){
           foreach($customers as $customer){
               $now = date('Y-m-d');
               $messageId = 'communication_for_sleeping_customer_with_pay_start_' . $customer->num . '_' . time();
               $msg2 = "Cher client, en raison de vos transactions d'achat de crÃ©dit non abouties, GAM double votre compte de remboursement de frais et les 100 Fcfa de frais habituellement prÃ©levÃ©s vous seront automatiquement remboursÃ©sðŸ˜Ž. Alors, nâ€™attendez plus, profitez de cette opportunitÃ© dÃ¨s maintenant ðŸ¥³!";


               $check1 = $this->checkNumWhatsapp('241' . substr($customer->numclient, 1));
               $check2 = $this->checkNumWhatsapp('241' . $customer->numexpediteur);

               if ($check1 == 'success') {
                    $status = 'WH';
                    $goodWhatsapp = '241' . substr($customer->numclient, 1);
                    $this->templateForCustomerUssdSleepingVideoWithPay('241' . substr($customer->numclient, 1), $msg2, 'https://gampay.org/audio_flow/videoaudio_sleeping_ussd_with_pay_jour.MP4', $messageId);

                }
                if ($check2 == 'success') {
                     $status = 'WH_L';
                     $goodWhatsapp = '241' . $customer->numexpediteur;
                    $this->templateForCustomerUssdSleepingVideoWithPay('241' . $customer->numexpediteur, $msg2, 'https://gampay.org/audio_flow/videoaudio_sleeping_ussd_with_pay_jour.MP4', $messageId);
                }

                if ($check1 != 'success' && $check2 != 'success') {
                    $statut = 'false';
                    Client2::where('num', $customer->num)->update([
                        'service_Type' => $now . '_NOK',
                        'whatsapp' => 'no_whatsapp',
                        'transaction_ID' => 'no_send',
                        'status' => 'no_WH',
                        'reference_Number' => 'no_send'
                    ]);
                } else {
                    Client2::where('num', $customer->num)->update([
                        'service_Type' => 'remboursement',
                        'transaction_ID' => $messageId,
                        'whatsapp' => $goodWhatsapp,
                        'status' => $status,
                        'reference_Number' => 'send'
                    ]);
                }
           }
       }
    }

    //Template Customer Sleeping with pay

    public function templateForCustomerUssdSleepingVideoWithPay($whatsapp, $message, $video, $messageId)
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
                        "templateName": "communication_for_sleeping_customer_with_pay",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "VIDEO",
                            "mediaUrl": "' . $video . '"
                          },
                          "buttons": [
                            {"type": "QUICK_REPLY", "parameter": "send_me_the_procedure"},
                            {"type": "QUICK_REPLY", "parameter": "assistance_rembourse"},
                            {"type": "URL", "parameter": "' . $whatsapp . '&abonne=remboursement&messageId=' . $messageId . '"}
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

    /* End Customer Sleeping with pay */

    /* Remboursement Customer Sleeping with pay ussd */

    //script remboursement Customer Sleeping with pay ussd

    public function remboursementOfFraisForCustomerSleepingWithPay(Request $request)
    {
        $customer = Client2::where('numclient', $request->phone)->where('service_Type','remboursement')->first();
        if ($customer) {

            $trans = new Historiquetrans([
                'operation' => 'transfert_mobile',
                'reference' => 'remboursement_ussd',
                'etat' => 'atraiter',
                'numclient' => $customer->numclient,
                'phonevendeur' => $customer->numclient,
                'content' => 'AIRTEL MONEY',
                'montant' => '100',
                'montant_sans_frais' => '100',
                'frais' => '0',
                'solde' => $customer->post_Balance,
                'origine_operation' => 'scyd_remboursement_ussd',
                'id_customer' => $customer->num
            ]);

            $trans->save();

            if ($trans) {
                $newMontant = $customer->post_Balance - 100;
                if ($newMontant == '0') {
                    Client2::where('num', $customer->num)->update([
                        'post_Balance' => $newMontant,
                        'service_Type' => 'stop'
                    ]);
                } else{
                    if (!str_contains($customer->reference_Number, 'remboursement')) {
                        $service_Type = 'remboursement';
                        $message = "Cher client nous venons de vous rembourser vos frais de transaction, continuez à trafiquer et recevez 100% des frais de transaction. Retrouvez l'historique de vos transactions et votre solde de remboursement en cliquant sur *Voir mon historique*";
                        $messageId = 'recap_for_sleeping_customer_with_pay_' . $customer->num . '_' . time();

                        $messageAlerte = "Le *$customer->numclient* du remboursement a reÃ§u ses frais de 100Fcfa";

                        if($customer->whatsapp == 'no_whatsapp' || empty($customer->whatsapp)){
                            $messageAlerte = "Le *$customer->numclient* du remboursement n'a pas reÃ§u son template du rÃ©cap car il n'a pas de whatsapp";
                        }else{
                            $this->templateRecapForCustomerWithPay($customer->whatsapp, $message, 'https://gampay.org/image_flow/remboursement_with_pay.jpeg', $messageId);
                        }
                            Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=work&message=".$messageAlerte);

                    }
                    Client2::where('num', $customer->num)->update([
                        'post_Balance' => $newMontant,
                        'reference_Number' => empty($service_Type) ? $customer->reference_Number : $customer->reference_Number . '_' . $service_Type
                    ]);
                }
            }
        } // service_Type = 'endormi' and previous_Balance is not null and post_Balance is NULL
    }

    // Template remboursement Customer Sleeping with pay ussd

    public function templateRecapForCustomerWithPay($whatsapp, $message, $image, $messageId)
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
                        "templateName": "recap_for_sleeping_customer_with_pay",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "IMAGE",
                            "mediaUrl": "' . $image . '"
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "' . $whatsapp . '&recap=remboursement&messageId=' . $messageId . '"}
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

    /* Fin Remboursement Customer Sleeping with pay ussd */

    /* Customer new ussd */

    //Alerte nouveau client ussd
    public function sendAlertNewCustomerUssd(Request $request)
    {
        $client2 = Client2::where('numclient', $request->phone)->first();

        if($client2){

        if (intval($client2->grade) == 100) {
            $montant = 80;
        } else {
            $montant = intval($client2->grade) - 100;
        }

        $messageId = 'new_communication_ussd_image' . time();
        $message1 = "Cher client, Bienvenue chez Gam !🥳 Vous venez d'envoyer $montant Fcfa de crédit au 0$client2->numexpediteur. Vous faites désormais partie d’une communauté où simplicité et efficacité sont au rendez-vous. ☺ Pour découvrir encore plus de services pratiques. Avec GamPay, tout devient plus facile !  Suivez-nous sur notre chaîne WhatsApp 👉🏽: https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c";
        $message2 = "Cher client, Bienvenue chez Gam !🥳 Vous venez de recevoir $montant Fcfa de crédit du $request->phone. Vous faites désormais partie d’une communauté où simplicité et efficacité sont au rendez-vous. ☺ Pour découvrir encore plus de services pratiques. Avec GamPay, tout devient plus facile !  Suivez-nous sur notre chaîne WhatsApp 👉🏽: https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c";

        $check1 = $this->checkNumWhatsapp('241' . substr($request->phone, 1));
        $check2 = $this->checkNumWhatsapp('241' . $client2->numexpediteur);

        if ($check1 == 'success') {
            $status = 'WH';
            $goodWhatsapp = '241' . substr($request->phone, 1);
            $this->newTemplateForCustomerUSSD($goodWhatsapp, $message1,'https://gampay.org/image_flow/affiche_communication.jpeg',$messageId);
        }

        if ($check2 == 'success') {
            $status = 'WH_L';
            $goodWhatsapp = '241' . $client2->numexpediteur;
            $this->newTemplateForCustomerUSSD($goodWhatsapp, $message2,'https://gampay.org/image_flow/affiche_communication.jpeg',$messageId);
        }
            Client2::where('num', $client2->num)->update([
                'whatsapp' => $goodWhatsapp,
                'status' => $status
            ]);

        $customer = Customer::where('phoneclient', $request->phone)->first();
        if ($customer) {
            $option = 'ussd';
            if (empty($customer->option1)) {
                $newOption1 = $option;
            } else {
                if (str_contains($customer->option1, $option)) {
                    $newOption1 = $customer->option1;
                } else {
                    $newOption1 = $customer->option1 . '_' . $option;
                }
            }
            Customer::where('id', $customer->id)->update([
                'option1' => $newOption1,
                'code_confirm' => empty($customer->code_confirm) ? $option : $customer->code_confirm,
            ]);
        } else {
            $newCustomer = new Customer([
                'nom' => 'client',
                'prenom' => 'GAM',
                'pays' => '241',
                'phoneclient' => $request->phone,
                'solde' => 0,
                'mdpclient' => '1234',
                'option1' => 'ussd',
                'code_confirm' => 'ussd',
                'satus' => 0
            ]);
            $newCustomer->save();
        }
      }
      $message = "Nouveau client Ussd *$request->phone*";
      //$this->sendWhatsAppGroupMessage($message, 'GAM Service client (4)', 'other');
    }

    // Fin alerte nouveau client ussd

    // Template nouveau client ussd
    public function newTemplateForCustomerUSSD($whatsapp, $message, $image,$messageId)
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
                        "templateName": "new_communication_ussd_image",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "IMAGE",
                            "mediaUrl": "' . $image . '"
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
                'Authorization: App 6528aa653d96610451e2d8054ecb3358-1207d4b9-3758-42e8-9097-19e50a29f00d',
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
    // Fin template nouveau client ussd

    /* Fin Customer new ussd */

    /* Lancement communication ussd */
    public function sendAlertEnchere(Request $request)
    {
         $this->sendTemplateForCustomerUssdSleeping('not_in_customer');
         //$send = $this->sendTemplateForCustomerUssdSleepingWithPay('in_customer_and_have_solde');
         //$send1 = $this->sendTemplateForCustomerUssdSleeping('');
         //$this->takeCustomerClient2ForVerifWhatsapp();

        if(!empty($request->appli)){
            $whatsapp = substr($request->whatsapp,4);
            $customer = WhatsAppBackUp::where('messageId', 'like', 'communication_enchere_image%')->where('recipeint', 'like', '%'. $whatsapp.'%')->first();
            if($customer){
                WhatsAppBackUp::where('id',$customer->id)->update([
                    'app' => 'appli'
                ]);
            }
            $message = "Le client *$request->whatsapp* pour les enchères a commencé la procédure d'installation";
            Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=".$message);
        }
    }

    /* Fin lancement communication ussd */

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


    // Template com
    public function shareCom(Request $request) {
        $typeFile = 'image';
        $message = 'Decouvrez les produits et services de GAM';
        $templateName = 'com_sharecom_image';
        $file = 'https://gampay.org/image_flow/accueil_flow_white.png';

        if(!empty($request->typeFile)){
            $typeFile = $request->typeFile;
            $file = $request->file;
        }

        if(!empty($request->message)){
            $message = $request->message;
        }

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
                                  "type": "' . $typeFile . '",
                                  "' . $typeFile . '": {
                                      "link": "' . $file . '"
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
                      },
                      {
                         "type": "button",
                         "sub_type": "URL",
                         "index": "1",
                         "parameters": [
                            {
                                "type": "text",
                                "text": "' . $phone . '"
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
              Http::get("https://gampay.org/clients/public/api/createAccount?phone=$phone&parrain=$request->parrain&whatsapp=$request->whatsapp&option=shareCom");

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
               return response()->json([
                'statut' => false,
                'body' => [10, $result]
              ]);
          }
        } else {
             return response()->json([
                'statut' => false,
                'body' => [100, $result]
              ]);
        }
        // Close handle
        curl_close($curl);
    }

    /* script wifi */

    public function sendTemplateInCustomer($phone, $message)
    {
        $customer = Customer::where('phoneclient', $phone)->first();
        if ($customer) {
            $codes = WifiUdm::where('param1', $phone)->first();
            if ($codes) {
                $messageId = "ticket_wifi_ussd_flow" . time();
                $this->templateMessageCustomerWifiPlanFlow($customer->whatsapp, $messageId, $message);
            }
        }
    }


    public function validetranswifi(Request $request)
    {
        try {
            $getSummary = Http::get("https://gampay.org/gamclients/public/api/summaryPushAM?limit=10")->body();
            $data = json_decode($getSummary, true);
            if ($data['status']['success'] == true) {
                foreach ($data['data']['transactions'] as $item) {
                    $airtelId = $item['transaction']['airtel_money_id'] ?? '';
                    // 1. Filtrer uniquement ceux qui commencent par MP2
                    //return $airtelId;
                    if (str_starts_with($airtelId, 'MP2') && $item['transaction']['status'] == 'TS' && $item['transaction']['id'] == null) {
                        $alreadyExists = Historiquetrans::where('param6', $airtelId)->get();
                        if (count($alreadyExists) == 0) {
                            $customer = Customer::where('payments', 'like', "%{$item['payee']['msisdn']}%")->orWhere('config_sims', 'like', "%{$item['payee']['msisdn']}%")->first();
                            if ($customer) {
                                $trans = Historiquetrans::where('phonevendeur', $customer->phoneclient)->where('operation', 'achat_wifi')->where('montant', intval($item['transaction']['amount']))->where('etat', 'attend')->orderBy('id', 'desc')->first();

                                $goodTicket = 000000000;
                                $status = "ticket_Nosend";
                                $ticket = Http::get("https://gampay.org/gamclients/public/api/createdVoucher?number=$trans->phonevendeur")->body();
                                $data = json_decode($ticket, true);
                                if ($data['status'] == true) {
                                    $goodTicket = $data['code'];
                                    $status = "ticket_send";
                                    $registerTicket = new WifiUdm([
                                        'code' => $goodTicket,
                                        'dure' => $trans->content,
                                        'statut' => 'used',
                                        'param1' => $trans->phonevendeur,
                                        'param2' => 'payment_wifi_' . $trans->id
                                    ]);
                                    $registerTicket->save();
                                }

                                $customerWifi = CustomerWifi::where('phone', $customer->phoneclient)->first();
                                if($customerWifi){
                                    if($customerWifi->end_subscription <= date("Y-m-d") && $customerWifi->end_ticket >= date("Y-m-d")){
                                        $goodTicket = $customerWifi->num_ticket;
                                    }
                                }

                                if ($trans) {
                                    $trans->update([
                                        'etat' => 'CONFIRMEE',
                                        'numclient' => $goodTicket,
                                        'param6' => $airtelId,
                                        'code_validation' => $status
                                    ]);
                                } else {
                                    if (in_array($item['transaction']['amount'], [500, 2000, 5000])) {
                                        $content = "day";
                                        if ($item['transaction']['amount'] == 2000) {
                                            $content = "week";
                                        } elseif ($item['transaction']['amount'] == 5000) {
                                            $content = "month";
                                        }
                                        $trans = new Historiquetrans([
                                            'operation' => "achat_wifi",
                                            'reference' => $airtelId,
                                            'etat' => "CONFIRMEE",
                                            'numclient' => $goodTicket,
                                            'phonevendeur' => $customer->phoneclient,
                                            'content' => $content,
                                            'montant' => intval($item['transaction']['amount']),
                                            'montant_sans_frais' => intval($item['transaction']['amount']),
                                            'frais' => 0,
                                            'origine_operation' => "wifi_market",
                                            'param6' => $airtelId,
                                            'code_validation' => $status
                                        ]);
                                        $trans->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return "ok";
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    public function validetranswifiOld(Request $request)
    {
        //Http::get("https://gampay.org/clients/public/api/validetranswifi");
        //Http::get("https://gampay.org/clients/public/api/validetranswifi?wifi=ok&phonevendeur=074071340&montant=500");

        if (!empty($request->wifi)) {
            $transwifi = GamRechargeHist::where('etat', 'sent	tonumbergam')->where('port', '7A')->where('numeroclient', '556')->take(5)->get();
            if (count($transwifi) > 0) {
                foreach ($transwifi as $trans) {
                    if ($trans->montant == 500) {
                        $ticket = 'day';
                    }
                    if ($trans->montant == 2000) {
                        $ticket = 'week';
                    }
                    if ($trans->montant == 5000) {
                        $ticket = 'month';
                    }

                    $codes = WifiUdm::where('statut', 'unused')->where('dure', $ticket)->first();
                    if ($codes) {

                        $trans->update([
                            'numclient' => $codes->code,
                            'etat' => 'data_status_wifi',
                            'param1' => json_encode($codes),
                        ]);
                        $codes->update([
                            'statut' => 'used',
                            'param1' => $trans->phonevendeur,
                            'param2' => 'ussd'
                        ]);
                        $messageId = "ticket_wifi_ussd_flow" . time();
                        $whatsapp = '241' . substr($trans->phonevendeur, 1);
                        $message = "Votre transaction a été effectuée avec succès ✅. Vous venez d'acheter un ticket *WIFI GAM $ticket*, votre ticket est *$codes->code*";
                        $this->templateMessageCustomerWifiPlanFlow($whatsapp, $messageId, $message);
                        $this->sendTemplateInCustomer($trans->phonevendeur, $message);


                        $valideCodes = WifiUdm::where('statut', 'unused')->where('dure', $ticket)->get();
                        if (($ticket == 'day' || $ticket == 'week') && count($valideCodes) <= 20) {
                            $messageAlerte = "Les tickets wifi $ticket sont à moins de 20";
                            $agents = DB::select("SELECT * from customer where username like '%gamSC%'");
                            foreach ($agents as $agents) {
                                Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$agents->unlock_token&app=android&message=$messageAlerte&titre=AlerteWifi");
                            }
                        }
                        if ($ticket == 'month' && count($valideCodes) <= 10) {
                            $messageAlerte = "Les tickets wifi $ticket sont à moins de 10";
                            $agents = DB::select("SELECT * from customer where username like '%gamSC%'");
                            foreach ($agents as $agents) {
                                Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$agents->unlock_token&app=android&message=$messageAlerte&titre=AlerteWifi");
                            }
                        }
                        //$messageAlerte = "Le *$trans->phonevendeur* vient d'acheté un ticket *WIFI GAM $trans->content*";
                        //Http::get("https://gampay.org/gamclients/public/api/sendMessageWithMeta?whatsapp=24104201507&message=$messageAlerte");
                        //Http::get("https://gampay.org/gamclients/public/api/sendMessageWithMeta?whatsapp=$whatsapp&message=$message");
                    }
                }
            }
        }

        $transwifi = Historiquetrans::where('etat', 'atraiter')->where('operation', 'achat_wifi')->whereIn('content', ['jour', 'semaine', 'mois', 'day', 'week', 'month'])->take(5)->get();
        //return 100;
        if (count($transwifi) > 0) {
            foreach ($transwifi as $trans) {
                $codes = WifiUdm::where('statut', 'unused')->where('dure', $trans->content)->first();
                if ($codes) {
                    $trans->update([
                        'numclient' => $codes->code,
                        'etat' => 'CONFIRMEE',
                        'param1' => json_encode($codes),
                    ]);

                    $codes->update([
                        'statut' => 'used',
                        'param1' => $trans->phonevendeur
                    ]);
                    $messageId = "ticket_wifi_flow" . time();
                    $whatsapp = '241' . substr($trans->phonevendeur, 1);
                    $message = "Votre transaction a été effectuée avec succès ✅. Vous venez d'acheter un ticket *WIFI GAM $trans->content*, votre ticket est *$codes->code*";
                    $this->templateMessageCustomerWifiPlanFlow($whatsapp, $messageId, $message);
                    $this->sendTemplateInCustomer($trans->phonevendeur, $codes->code);

                    $valideCodes = WifiUdm::where('statut', 'unused')->where('dure', $trans->content)->get();
                    if (($trans->content == 'day' || $trans->content == 'week') && count($valideCodes) <= 20) {
                        $messageAlerte = "Les tickets wifi $trans->content sont à moins de 20";
                        $agents = DB::select("SELECT * from customer where username like '%gamSC%'");
                        foreach ($agents as $agents) {
                            Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$agents->unlock_token&app=android&message=$messageAlerte&titre=AlerteWifi");
                        }
                    }
                    if ($trans->content == 'month' && count($valideCodes) <= 10) {
                        $messageAlerte = "Les tickets wifi $trans->content sont à moins de 10";
                        $agents = DB::select("SELECT * from customer where username like '%gamSC%'");
                        foreach ($agents as $agents) {
                            Http::get("https://gampay.org/clients/public/api/notifFirebaseHttp?token=$agents->unlock_token&app=android&message=$messageAlerte&titre=AlerteWifi");
                        }
                    }
                    //$messageAlerte = "Le *$trans->phonevendeur* vient d'acheté un ticket *WIFI GAM $trans->content*";
                    //Http::get("https://gampay.org/gamclients/public/api/sendMessageWithMeta?whatsapp=24104201507&message=$messageAlerte");
                    //Http::get("https://gampay.org/gamclients/public/api/sendMessageWithMeta?whatsapp=$whatsapp&message=$message");

                }
            }
            return $trans;
        }
        return 0;
    }

    public function createTransWifi(Request $request)
    {
        //Http::get("https://gampay.org/clients/public/api/createTransWifi?montant=$request->montant");
        if ($request->montant == 500) {
            $url = "*150*3*10*746*$request->montant*556";
        }
        if ($request->montant == 2000) {
            $url = "*150*3*10*746*$request->montant*556";
        }
        if ($request->montant == 5000) {
            $url = "*150*3*10*746*$request->montant*556";
        }

        return redirect("tel:" . $url . "%23");
    }

    public function templateMessageCustomerWifiPlanFlow($whatsapp, $messageId,$message)
  {
    $phone = $this->getNumByWhatsApp($whatsapp);
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
            "name": "msg_wifi",
            "language": {
                "code": "fr"
            },
            "components": [
                {
                    "type": "body",
                    "parameters": [
                        {
                            "type": "text",
                            "text": "' . $message . '"
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
            Http::get("https://gampay.org/gamclients/public/api/createFlowBackupRequest?messageId=$id&accoundId=$messageId&phone=$phone&parrain=");
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

  public function solveOperationRechargeVisa()
    {
        try {
            $getSummary = Http::get("https://gampay.org/gamclients/public/api/summaryPushAM?limit=10")->body();
            $data = json_decode($getSummary, true);
            if ($data['status']['success'] == true) {
                foreach ($data['data']['transactions'] as $item) {
                    $airtelId = $item['transaction']['airtel_money_id'] ?? '';
                    // 1. Filtrer uniquement ceux qui commencent par MP2
                    //return $airtelId;
                    if (str_starts_with($airtelId, 'MP2') && $item['transaction']['status'] == 'TS' && $item['transaction']['id'] == null) {
                        $alreadyExists = Historiquetrans::where('param6', $airtelId)->get();
                        if (count($alreadyExists) == 0) {

                            $customer = Customer::where('payments', 'like', "%{$item['payee']['msisdn']}%")->orWhere('config_sims', 'like', "%{$item['payee']['msisdn']}%")->first();
                            if ($customer) {
                                $trans = Historiquetrans::where('operation', 'recharge_visa_uba')->where('phonevendeur', $customer->phoneclient)->where('montant', intval($item['transaction']['amount']))->where('etat', 'attend')->orderBy('id', 'desc')->first();
                                if ($trans) {
                                    $trans->update([
                                        'etat' => 'en_attente',
                                        'param6' => $airtelId
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            return "trans ok";
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getTransTestInterne(){
        $trans = DB::select("SELECT id, operation, numclient as num_receveur, phonevendeur as num_emeteur, content as operateur, montant as montant_a_payer FROM historiquetrans g where phonevendeur = '074201507' and operation in ('data_credit','achat_credit') order by id desc limit 1");
        return $trans;
    }

    public function getCustomerAction(Request $request){
        try{
            $customer = ProjetAction::where('projet_action',13)->get();
            if($request->option == 'my_action'){
                $customer = ProjetAction::where('customer',$request->id)->get();
            }

            return response()->json([
                'statut' => true,
                'body' => $customer
              ]);
        }catch (\Throwable $th) {
            //throw $th;
        }
    }


}
