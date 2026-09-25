<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
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
use Illuminate\Support\Facades\DB;
use DateTime;
use App\Models\Comment;
use Illuminate\Support\Facades\Redirect;

class GeneralController extends Controller
{
    public function getInfoNumberIn($num, $num1, $num2, $type){
        $info = CustomerComplement::whereIn('phoneclient', [$num, $num1, $num2, '0'.$num])->first();
        if($info){
            if(!empty($info->nom) && !str_contains($info->nom, 'trer PIN') && $info->nom!=''){
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
    
    
    public function notifFirebase($token,$titre, $message, $app){
        if($app == 'android'){
            $tokenKey = Http::get('http://api.gampay.org/')->body();
            $link = "https://fcm.googleapis.com/v1/projects/app-gampay/messages:send";
        }else{
            $tokenKey = Http::get('http://ios.gampay.org/')->body();
            $link = "https://fcm.googleapis.com/v1/projects/app-new-gampay-ios/messages:send";
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
            return  $response;
        }else{
            return 10;
        }
    }
    
    public function notifFirebaseHttp(Request $request){
        /*if($request->app == 'android'){
            $tokenKey = Http::get('http://api.gampay.org/')->body();
            $link = "https://fcm.googleapis.com/v1/projects/status-9986a/messages:send";
        }else{
            $tokenKey = Http::get('http://ios.gampay.org/')->body();
            $link = "https://fcm.googleapis.com/v1/projects/status-9986a/messages:send";
        }*/
        
        $tokenKey = Http::get('http://api.gampay.org/')->body();
        $link = "https://fcm.googleapis.com/v1/projects/status-9986a/messages:send";
        
        //return $tokenKey;

        if(!empty($request->token)){
            // 1er envoi
           $data =[
                "message"=>[
                    "token"=> $request->token,
                    "notification"=>[
                        "body"=>$request->message,
                        "title"=>$request->titre
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
            return $response;
        }else{
            return 10;
        }
    }
    
    public function sendMessageWithMeta($whatsapp, $message){
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
                "recipient_type": "individual",
                "to": "' . $whatsapp . '",
                "type": "text",
                "text": {
                    "preview_url": true,
                    "body": "' . $message . '"
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
            if (!empty($response['messages'][0]['id'])) {
            $comment = new BackupMeta([
                'account_ID' => 'sendeur' . time(),
                'message_Id' => $response['messages'][0]['id'],
                'message_body' => $message,
                'display_phone_number' => '24105949831',
                'recipient_id' => $whatsapp,
            ]);
            $comment->save();

            $conversation = Conversation::where('customer_whatsapp', $whatsapp)->first();
            if ($conversation) {
                Conversation::where('id', $conversation->id)->update([
                'last_message' => $message,
                'last_message_time' => date("Y-m-d H:i:s"),
                'no_read' => $conversation->no_read + 1
                ]);
            } else {
                $conversation = new Conversation([
                'customer_whatsapp' => $whatsapp,
                'last_message' => $message,
                'last_message_time' => date("Y-m-d H:i:s"),
                'no_read' => 1
                ]);
                $conversation->save();
            }
            return $response['messages'][0]['id'];
            } else {
            return [10, $result];
            }
        } else {
            return [100, $result];
        }
        // Close handle
        curl_close($curl);
        }
    }

    public function alerteAgents($message){
        $agents = DB::select("SELECT * from customer where username like %gam% and deleted_at is not null");
        if (count($agents)>0){
            foreach( $agents as $agent) {
                $this->sendMessageWithMeta($agent->whatsapp, $message);
            }
        }
    }
    
    public function go(){
       return 'go';
    }
    
    /*public function getUpdateLauncher(){
       //return '1.0.0';
    }*/


}
