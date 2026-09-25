<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\CustomerWifi;
use App\Models\WifiUdm;
use App\Models\Customer;
use App\Models\Historiquetrans;
use App\Models\SubscriptionWifi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class CustomerWifiController extends Controller
{

    public function getNumByWhatsApp($whatsapp)
    {
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
        return $result;
        curl_close($curl);
    }
    public function createCustomerWifi(Request $request)
    {
        $customerWifi = CustomerWifi::where("mac_adress", $request->mac_adress)->first();
        if ($customerWifi) {
            return '0';
        }
        $customerWifi = new CustomerWifi([
            'phone' => !empty($request->phone) ? $request->phone : null,
            'whatsapp_wifi' => !empty($request->whatsapp) ? $request->whatsapp : null,
            'name' => $request->name,
            'dure_ticket' => $request->dure_ticket,
            'mac_adress' => $request->mac_adress,
            'start_ticket' => $request->start_ticket,
            'end_ticket' => $request->end_ticket,
            'statut' => $request->statut,
            'num_ticket' => $request->num_ticket,
            'amount_subscription' => $request->dure_ticket == 'jour' ? 500 : ($request->dure_ticket == 'semaine' ? 2000 : 5000),
            'start_subscription' => date("Y-m-d", strtotime($request->start_ticket . "+2 days"))
        ]);

        $customerWifi->save();
        
        if($customerWifi){
            $subscriptionWifi = new SubscriptionWifi([
                'id_customer_wifi' => $customerWifi->id,
                'type_subscription' => $customerWifi->dure_ticket,
                'subscription_start' => $customerWifi->start_subscription,
                'last_amount_subscription' => $customerWifi->amount_subscription,
                'num_ticket_last' => $customerWifi->num_ticket,
                'status' => 'in_progress'
            ]);
            $subscriptionWifi->save();
        }

        return 1;
    }

    public function enableOrDisablebleCustomerWifi(Request $request)
    {
        $editCustomerWifi = CustomerWifi::where('id', $request->id)->first();
        if ($editCustomerWifi) {
            CustomerWifi::where('id', $request->id)->update([
                'phone' => ($editCustomerWifi->phone == $request->phone) ? $editCustomerWifi->phone : $request->phone,
                'whatsapp_wifi' => ($editCustomerWifi->whatsapp_wifi == $request->whatsapp) ? $editCustomerWifi->whatsapp_wifi : $request->whatsapp,
                'name' => $request->name,
                'dure_ticket' => $request->dure_ticket,
                'mac_adress' => $request->mac_adress,
                'start_ticket' => $request->start_ticket,
                'end_ticket' => $request->end_ticket,
                'statut' => $request->statut,
                'last_paiement' => $request->last_paiement,
                'start_subscription' => $request->start_subscription,
                'avance_subscription' => $editCustomerWifi->start_ticket <= $request->start_ticket ? 0 : $editCustomerWifi->avance_subscription,
                'amount_subscription' => $request->dure_ticket == 'jour' ? 500 : ($request->dure_ticket == 'semaine' ? 2000 : 5000),
                'end_subscription' => $request->end_subscription,
                'num_ticket' =>  $request->num_ticket,
                'id_payment' => $request->id_payment
            ]);

            if ($request->end_subscription > $editCustomerWifi->end_subscription) {
                $this->registerSubscriptionWifiIn($editCustomerWifi->id, $editCustomerWifi->dure_ticket, $editCustomerWifi->start_subscription, $editCustomerWifi->end_subscription, $editCustomerWifi->amount_subscription, $editCustomerWifi->last_paiement, $editCustomerWifi->num_ticket, 'exhausted');
                $this->registerSubscriptionWifiIn($editCustomerWifi->id, $editCustomerWifi->dure_ticket, $request->start_subscription, $request->end_subscription, $editCustomerWifi->amount_subscription, $editCustomerWifi->last_paiement, $editCustomerWifi->num_ticket, 'subscription_in_progress');
            } else {
                $this->registerSubscriptionWifiIn($editCustomerWifi->id, $editCustomerWifi->dure_ticket, $request->start_subscription, $request->end_subscription, $editCustomerWifi->amount_subscription, $editCustomerWifi->last_paiement, $editCustomerWifi->num_ticket, 'subscription_in_progress');
            }
            //$editCustomerWifi->save();
            return response()->json([
                'status' => 1
            ]);
            //return redirect()->back(); 
        }
    }

    public function launchPushCustomerWifi(Request $request)
    {
        $customerWifi = CustomerWifi::where('id', $request->id)->first();
        if ($customerWifi) {
            $trans = $this->paymentNewWifiIn($customerWifi->phone, $customerWifi->dure_ticket, $customerWifi->amount_subscription);
            if (str_starts_with(trim($customerWifi->phone), '07')) {
                $transPush = Http::get('https://gampay.org/gamclients/public/api/createTransPushAM', [
                    'montant' => $customerWifi->amount_subscription,
                    'phone' => $customerWifi->phone,
                    'object' => 'WIFIGAM'
                ]);
            } else {
                $transPush = Http::get("https://gampay.org/clients/public/api/getPaymentPvit", [
                    'montant' => $customerWifi->amount_subscription,
                    'phone' => $customerWifi->phone,
                    'ref' => $trans
                ]);
            }

            if ($transPush->successful()) {
                $customerWifi->update(['param1' => $trans]);
                return 1;
            } else {
                return 0;
            }
        }
    }

    public function getCustomerWifi()
    {
        //Http::get("https://gampay.org/gamclients/public/api/sendTemplateMessageCustomerWifiPlan");
        $customerWifis = CustomerWifi::whereNotNull('statut')->orderByDesc('id')->get();
        $customerWifiActif = CustomerWifi::where('statut', 'actif')->orderByDesc('id')->get();
        $customerWifiPushWait = CustomerWifi::where('statut', 'wait_push_payment')->orderByDesc('id')->get();
        $customerWifiRecouvrementWait = CustomerWifi::where('statut', 'wait_recouvrement')->orderByDesc('id')->get();
        $customerWifiRelanceWait = CustomerWifi::where('statut', 'wait_payment')->orderByDesc('id')->get();
        return view('wifiCustomer', compact('customerWifis', 'customerWifiActif', 'customerWifiPushWait', 'customerWifiRecouvrementWait', 'customerWifiRelanceWait'));
    }

    public function insertTicketWifi(Request $request)
    {
        $now = date("Y-m-d H:i:s");
        if ($request->nameTicket == 'day') {
            $amount = 500;
        } elseif ($request->nameTicket == 'week') {
            $amount = 2000;
        } else {
            $amount = 5000;
        }

        $tabTicket = [$request->ticket];

        if (str_contains($request->ticket, ',')) {
            $tabTicket = explode(',', $request->ticket);

            for ($i = 0; $i < count($tabTicket); $i++) {
                $goodTicket = $tabTicket[$i];

                $oldTicket = WifiUdm::where('code', $goodTicket)->first();
                if ($oldTicket) {
                    $message = "Le code $goodTicket est déjà enregistré";
                } else {
                    $newTicket = new WifiUdm([
                        'code' => $goodTicket,
                        'montant' => $amount,
                        'statut' => 'unused',
                        'dure' => $request->nameTicket,
                        'created_at' => $now
                    ]);
                    $newTicket->save();
                    $message = "Le code $goodTicket a été enregistré";
                }
            }
        } else {
            $oldTicket = WifiUdm::where('code', $request->ticket)->first();
            if ($oldTicket) {
                $message = "Le code $request->ticket est déjà enregistré";
            } else {
                $newTicket = new WifiUdm([
                    'code' => $request->ticket,
                    'montant' => $amount,
                    'statut' => 'unused',
                    'dure' => $request->nameTicket,
                    'created_at' => $now
                ]);
                $newTicket->save();
                $message = "Le code $request->ticket a été enregistré";
            }
        }


        return $message;
    }

    public function getCustomerConnected()
    {
        $connected = $this->getConnected();
        return $connected;
        $customerWifiActif = DB::select("select * from customer_wifi where statut ='actif' and (param2 is NULL or param2 != 'off') and deleted_at is null order by id desc limit 1");
        //return $customerWifiActif;
        if (count($customerWifiActif) > 0) {
            foreach ($customerWifiActif as $customerWifi) {
                $onligne = 'off';
                $connected = $this->getConnected();
                foreach ($connected as $connecte) {
                    $onligne = 'off';
                    if ($customerWifi->mac_adress == $connecte['macAddress']) {
                        $onligne = 'on';
                    }
                    CustomerWifi::where('id', $customerWifi->id)->update([
                        'param2' => $onligne
                    ]);
                    return $connected;
                }
            }
        }
    }

    public function getConnected()
    {
        $baseUrl = "https://api.ui.com/v1/connector/consoles/6C63F88C8FA100000000092C833C0000000009AA92FC0000000068453ADC:867554590/proxy/network/integration/v1/sites/88f7af54-98f8-306a-a1c7-c9349722b1f6/clients?offset=0&limit=500&filter=name.eq(%27RESEAU_GAM27%)";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'X-API-Key: JuJ5THpo6QOa7_CI3WVfvcM7Vi_PLgdR'
            ),
        ));

        $result = curl_exec($curl);
        // Close handle
        if (!curl_errno($curl)) {
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($http_code == 200 ||  $http_code == 201) {
                $response = json_decode($result, true);
                return response()->json([
                    'count' => $response['totalCount'],
                    'connected' => $response['data']
                ]);
            } else {
                return [10, $result];
            }
        } else {
            return [100, $result];
        }
        // Close handle
        curl_close($curl);
    }

    // recouvrement for wifi

    public function paymentNewWifiIn($phone, $typeSubscription, $amount)
    {
        $historiqueTrans = new Historiquetrans([
            'operation' => "achat_wifi",
            'reference' => 'AUTO',
            'etat' => "attend",
            'numclient' => $phone,
            'phonevendeur' => $phone,
            'content' => $typeSubscription,
            'montant' => strval($amount),
            'montant_sans_frais' => $amount,
            'frais' => 0,
            'origine_operation' => "MontBouet_wifi"
        ]);

        $historiqueTrans->save();
        if ($historiqueTrans) {
            $historiqueTrans->update(['reference' => 'WIFIGAM' . $historiqueTrans->id]);
        }
        return 'WIFIGAM' . $historiqueTrans->id;
    }

    public function sendTemplatePaymentNewWifiIn()
    {
        $delai = date("Y-m-d", strtotime("+1 days"));
        $customers = DB::select("select * from customer_wifi where end_ticket <= '$delai' and statut = 'wait_payment' and whatsapp_wifi is not null and deleted_at is null order by id desc limit 5");
        foreach ($customers as $customer) {
            $messageId = "payment_new_wifi_flow" . time();
            $phone = $this->getNumByWhatsApp($customer->whatsapp_wifi);
            $mess = "🚨Vous êtes arrivé à la fin de votre abonnement !!!";
            $mess1 = "Effectuer le paiement en cliquant sur le bouton ci-dessous :";
            $this->templatePaymentWifi($customer->whatsapp_wifi, $mess, $mess1, $messageId);
            CustomerWifi::where('id', $customer->id)->update([
                'param1' => $messageId,
                'phone' => $phone
            ]);
        }
    }

    public function getSubscriptionNewWifiIn($subscription, $whatsapp, $phone)
    {
        $delai = 0;
        $montant = 0;
        $statut = 'wait_payment';
        $conf_mess = "";
        if ($subscription == 'yes_I_accept') {
            $montant = 500;
            $delai = 1;
            $subscription = 'day';
            $conf_mess = "Félicitations, votre abonnement journalier a été enregistré avec succès";
        } elseif ($subscription == 'try_again_later') {
            $statut = 'wait_subscription';
            $subscription = 'relance';
        } elseif ($subscription == 'week') {
            $montant = 2000;
            $delai = 7;
            $subscription = 'week';
            $conf_mess = "Félicitations, votre abonnement hebdomadaire a été enregistré avec succès";
        } elseif ($subscription == 'month') {
            $montant = 5000;
            $delai = 30;
            $subscription = 'month';
            $conf_mess = "Félicitations, votre abonnement mensuel a été enregistré avec succès";
        } else {
            $statut = 'wait_subscription';
            $mess = "🚨Vous êtes arrivés à la fin de votre période d'abonnement !!!!";
            $mess1 = "Sélectionnez votre type d'abonnement :";
            $subscription = "payment_subscription_new_wifi_flow" . time();
            $this->templateSubscriptionWifi($whatsapp, $mess, $mess1, $subscription);
        }
        $customer = CustomerWifi::where('phone', $phone)->first();

        if ($customer) {
            $end_subsciption = date($customer->start_subscription, strtotime("+$delai days"));
            CustomerWifi::where('id', $customer->id)->update([
                'dure_ticket' => $subscription,
                'statut' => $statut,
                'end_subscription' => $end_subsciption,
                'amount_subscription' => $montant
            ]);

            if (!empty($conf_mess)) {
                $messageId = "msg_wifi" . time();
                $msg = $conf_mess . "du $customer->start_subscription au $customer->end_subscription";
                $this->templateMessageCustomerWifiPlanFlow($customer->whatsapp_wifi, $msg, $messageId);
            }
        }
    }

    public function sendTemplateSubsriptionNewWifiIn()
    {
        $delai = date("Y-m-d", strtotime("+2 days"));
        $customers = DB::select("select * from customer_wifi where start_ticket <= '$delai' and whatsapp_wifi is not null and deleted_at is null order by id desc limit 5");
        foreach ($customers as $customer) {
            $messageId = "choice_subscription_new_wifi_flow" . time();
            $phone = $this->getNumByWhatsApp($customer->whatsapp_wifi);
            $mess = "🚨Vous êtes arrivés à la fin de votre période de gratuité!!!!";
            $mess1 = "En cas de satisfaction de notre service, veuillez choisir votre type d'abonnement ci-dessous.";
            $this->templateSubscriptionWifi($customer->whatsapp_wifi, $mess, $mess1, $messageId);
            CustomerWifi::where('id', $customer->id)->update([
                'param1' => $messageId,
                'phone' => $phone
            ]);
        }
    }

    public function sendTemplateSubsriptionNewWifiRequest(request $request)
    {
        if (!empty($request->phone)) {

            $messageId = "choice_subscription_new_wifi_flow" . time();
            $mess = "🚨Vous êtes arrivés à la fin de votre période de gratuité!!!!";
            $mess1 = "En cas de satisfaction de notre service, veuillez choisir votre type d'abonnement ci-dessous.";
            $send = $this->templateSubscriptionWifi('241' . substr($request->phone, 1), $mess, $mess1, $messageId);
            return $send;
        }

        $delai = date("Y-m-d", strtotime("+2 days"));
        $customers = DB::select("select * from customer_wifi where start_ticket <= '$delai' and whatsapp_wifi is not null and deleted_at is null order by id desc limit 5");
        foreach ($customers as $customer) {
            $messageId = "choice_subscription_new_wifi_flow" . time();
            $phone = $this->getNumByWhatsApp($customer->whatsapp_wifi);
            $mess = "🚨Vous êtes arrivés à la fin de votre période de gratuité!!!!";
            $mess1 = "En cas de satisfaction de notre service, veuillez choisir votre type d'abonnement ci-dessous.";
            $this->templateSubscriptionWifi($customer->whatsapp_wifi, $mess, $mess1, $messageId);
            CustomerWifi::where('id', $customer->id)->update([
                'param1' => $messageId,
                'phone' => $phone
            ]);
        }
    }

    public function templateSubscriptionWifi($whatsAppNum, $message, $message1, $messageId)
    {
        // pack_com_parrain.png 
        $phone = $this->getNumByWhatsApp($whatsAppNum);
        try {
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
                        "name": "subscription_wifi",
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
                                    },
                                    {
                                        "type": "text",
                                        "text": "' . $message1 . '"
                                    }
                                ]
                            },
                            {
                                "type": "button",
                                "sub_type": "quick_reply",
                                "index": "0",
                                "parameters": [
                                    {
                                        "type": "payload",
                                        "payload": "yes_I_accept"
                                    }
                                ]
                            },
                            {
                                "type": "button",
                                "sub_type": "quick_reply",
                                "index": "1",
                                "parameters": [
                                    {
                                        "type": "payload",
                                        "payload": "week"
                                    }
                                ]
                            },
                            {
                                "type": "button",
                                "sub_type": "quick_reply",
                                "index": "2",
                                "parameters": [
                                    {
                                        "type": "payload",
                                        "payload": "month"
                                    }
                                ]
                            },
                            {
                                "type": "button",
                                "sub_type": "quick_reply",
                                "index": "3",
                                "parameters": [
                                    {
                                        "type": "payload",
                                        "payload": "try_again_later"
                                    }
                                ]
                            }
                        ]
                    }
                }',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer EAAVx2scjZBYIBR4uThXtZCZB4CtqJZC9tFrlb0N00x2JQbZAq2FsStuR2fXxarThAi3Gc1wX3MmS6ixOo8afIvMNp2uE8YZCELofRirHWUP25KCkcBbP8uDKkyahXV98vh1BcFquFR0DBVv0xLklfuCrcPmv29MSnW1pyZB6cXE3hVWEANntf1WGQ6k8iqqrZBSZCbZB0tMOetTqBOZBYB9ywZDZD',
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
                        $response = 1;
                    }
                }
            } else {
                return [10, $result];
            }
        } catch (\Throwable $th) {
            $response = 0;
        }
        // Close handle
        curl_close($curl);
    }

    public function templatePaymentWifi($whatsAppNum, $message, $message1, $messageId)
    {
        // pack_com_parrain.png 
        $phone = $this->getNumByWhatsApp($whatsAppNum);
        try {
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
                        "name": "payment_wifi",
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
                                    },
                                    {
                                        "type": "text",
                                        "text": "' . $message1 . '"
                                    }
                                ]
                            },
                            {
                                "type": "button",
                                "sub_type": "quick_reply",
                                "index": "0",
                                "parameters": [
                                    {
                                        "type": "payload",
                                        "payload": "buy_wifi"
                                    }
                                ]
                            },
                            {
                                "type": "button",
                                "sub_type": "quick_reply",
                                "index": "1",
                                "parameters": [
                                    {
                                        "type": "payload",
                                        "payload": "choose_my_subscription"
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
                        $response = 1;
                    }
                }
            }
        } catch (\Throwable $th) {
            $response = 0;
        }
        // Close handle
        curl_close($curl);
    }

    public function editAmountCustomerWifi(Request $request)
    {
        $editCustomerWifi = CustomerWifi::where('id', $request->id)->first();
        if ($editCustomerWifi) {
            if (intval($editCustomerWifi->reste_subscription) == 0) {
                $montant = $editCustomerWifi->amount_subscription - $request->avance_subscription;
            } else {
                $montant = $editCustomerWifi->reste_subscription - $request->avance_subscription;
            }
            CustomerWifi::where('id', $request->id)->update([
                'avance_subscription' => ($editCustomerWifi->avance_subscription == $request->avance_subscription) ? $editCustomerWifi->avance_subscription : $request->avance_subscription,
                'reste_subscription' => $montant
            ]);
            return response()->json([
                'status' => 1
            ]);
        }
    }

    public function registerSubscriptionWifiIn($idCustomerWifi, $dureTicket, $startSubscription, $endSubscription, $amountSubscription, $lastPaiement, $numTicket, $status)
    {
        $subscriptionWifi = SubscriptionWifi::where('id_customer_wifi', $idCustomerWifi)->orderBy('id', 'desc')->first();
        
        if ($subscriptionWifi) {
            if ($status == 'exhausted') {
            
                $subscriptionWifi->update([
                    'status' => $status
                ]);

                if ($status != 'exhausted') {
                    $subscriptionWifi = new SubscriptionWifi([
                        'id_customer_wifi' => $idCustomerWifi,
                        'type_subscription' => $dureTicket,
                        'subscription_start' => $startSubscription,
                        'subscription_end' => $endSubscription,
                        'last_amount_subscription' => $amountSubscription,
                        'paiement_last' => $lastPaiement,
                        'num_ticket_last' => $numTicket,
                        'status' => $status
                    ]);

                    $subscriptionWifi->save();
                }
            } else {
                    if ($subscriptionWifi->status == 'in_progress' || $subscriptionWifi->status == 'subscription_in_progress') {
                        $subscriptionWifi->update([
                            'type_subscription' => $dureTicket,
                            'subscription_start' => $startSubscription,
                            'subscription_end' => $endSubscription,
                            'last_amount_subscription' => $amountSubscription,
                            'paiement_last' => $lastPaiement,
                            'num_ticket_last' => $numTicket,
                            'status' => $status
                        ]);
                } 
            }
        } 
    }

    public function registerSubscriptionWifi(Request $request)
    {
        $subscriptionWifi = SubscriptionWifi::where('id_customer_wifi', $request->id_customer_wifi)->orderBy('id', 'desc')->first();
        if ($request->status == 'exhausted') {
            if ($subscriptionWifi) {
                $subscriptionWifi->update([
                    'status' => $request->status
                ]);
            }
        } else {

            if ($subscriptionWifi) {
                if ($subscriptionWifi->status == 'in_progress' || $subscriptionWifi->status == 'subscription_in_progress') {
                    $subscriptionWifi->update([
                        'type_subscription' => $request->dure_ticket,
                        'subscription_start' => $request->start_subscription,
                        'subscription_end' => $request->end_subscription,
                        'last_amount_subscription' => $request->amount_subscription,
                        'paiement_last' => $request->last_paiement,
                        'num_ticket_last' => $request->num_ticket_last,
                        'status' => $request->status
                    ]);
                }
            } else {
                return 1;
                $subscriptionWifi = new SubscriptionWifi([
                    'id_customer_wifi' => $request->id_customer_wifi,
                    'type_subscription' => $request->dure_ticket,
                    'subscription_start' => $request->start_subscription,
                    'subscription_end' => $request->end_subscription,
                    'last_amount_subscription' => $request->amount_subscription,
                    'paiement_last' => $request->last_paiement,
                    'num_ticket_last' => $request->num_ticket_last,
                    'status' => $request->status
                ]);
                $subscriptionWifi->save();
            }
        }
    }

    public function createdVoucher(Request $request)
    {
        $baseUrl = "https://api.ui.com/v1/connector/consoles/6C63F88C8FA100000000092C833C0000000009AA92FC0000000068453ADC:867554590/proxy/network/integration/v1/sites/88f7af54-98f8-306a-a1c7-c9349722b1f6/hotspot/vouchers";

        $time = 129600; // 90 days in minutes
        if(!empty($request->type)){
            $time = 1440; // 1 day in minutes
        }
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
             "name": "' . $request->number . '",
             "authorizedGuestLimit": 1,
             "timeLimitMinutes": '.$time.'
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
                return response()->json([
                    'status' => true,
                    'code' => $response['vouchers'][0]['code'],
                    'numero' => $response['vouchers'][0]['name'],
                    'duree' => $response['vouchers'][0]['timeLimitMinutes'] . " minutes"
                ]);
            } else {
                return [10, $result];
            }
        } else {
            return [100, $result];
        }
    }

    /********************Payment trasability ***********************/

    public function createTokenPushAM($tokenPush)
    {
        if (!empty($tokenPush)) {
            //accès test Gampay
            $client_id = "58381cd4-06b2-4cb1-8cfe-0c415d4c9e57";
            $client_secret = "8ec1e093-f1bb-4c23-bef2-1e4aed9be021";
            $url = "https://openapiuat.airtel.ga/auth/oauth2/token";
        } else {
            // accès prod gampay
            $client_id = "1ff01e5a-78f3-431f-ac06-b01457562650";
            $client_secret = "be0c6692-eaa4-4863-9085-dac8d01cd853";
            $url = "https://openapi.airtel.ga/auth/oauth2/token";
        }



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
    public function createTransPushAM($idTrans, $message, $montant)
    {

        // accès prod gampay
        $tokenPush = "";
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
        //return $montant;

        $token = $this->createTokenPushAM($tokenPush);
        if ($token == null) {
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
                    "msisdn": "074084184"
                },
                "transaction": {
                    "amount": ' . $montant . ',
                    "country": "GA",
                    "currency": "CFA",
                    "id": "' . $idTrans . '"
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

    public function paymentNewWifiRequest(Request $request)
    {
        $customerWifi = CustomerWifi::where('phone', $request->phone)->first();
        if ($customerWifi) {
            $historiqueTrans = new Historiquetrans([
                'operation' => "achat_wifi",
                'reference' => 'AUTO',
                'etat' => "atraiter",
                'numclient' => $customerWifi->num_ticket,
                'phonevendeur' => $request->phone,
                'content' => $customerWifi->dure_ticket,
                'montant' => strval($request->amount),
                'montant_sans_frais' => $request->amount,
                'frais' => 0,
                'origine_operation' => "wifi_market"
            ]);

            $historiqueTrans->save();
            if ($historiqueTrans) {
                $push = $this->createTransPushAM($historiqueTrans->id, "WIFIGAM", $request->amount);
                $data = json_decode($push, true);
                if ($data['status']['success'] == true) {
                    $historiqueTrans->update([
                        'reference' => 'WIFI' . $data['data']['transaction']['id']
                    ]);
                }else{
                    $codePaiement = "*150*3*10*746*$request->amount*553$historiqueTrans->id";
                    return redirect("tel:" . $codePaiement . "%23");
                }
            }
            return response()->json([
                'status' => 1,
                'message' => "Transaction en cours de traitement"
            ]);
        }else{
           return response()->json([
                'status' => false,
                'message' => "Utilisateur non trouvé !!!"
            ]); 
        }
    }
    
    public function getInfoCustomerPortailCaptif(Request $request) {
    $customer = Customer::where('phoneclient', '074071340')->first();
    
        if ($customer) {
            $json = file_get_contents('php://input');
            try{
                if (!empty($json)) {
                    $dataPost = json_decode($json, true);
                    $data = json_encode($dataPost);
                } else {
                    $data = "9 Aucune donnée";
                }
                
                
            }catch (\Throwable $th) {
                $data = $th;
            }
            $customer->update([
                'grade' => $data
            ]); 
            
        }
        
        // Renvoyer une réponse ou rediriger vers l'écran suivant du portail
        return response()->json(['status' => 'success', 'data_captured' => $data]);
    }


}
