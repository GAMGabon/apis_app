<?php

namespace App\Http\Controllers;

use App\Models\Carte;
use App\Models\Cadeau;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Client2;
use App\Models\Customer;
use App\Models\ConfirmTrans;
use App\Models\CustomerCommande;
use App\Models\CustomerComplement;
use App\Models\ForfaitInternational;
use App\Models\ErrorApp;
use App\Models\GamElectriciteHist;
use App\Models\GamRechargeHist;
use App\Models\Historiquetrans;
use App\Models\Kyc;
use App\Models\Menu;
use App\Models\MenuCustomer;
use App\Models\Payment;
use App\Models\PaymentCustomer;
use App\Models\ProgramSubscription;
use App\Models\Notif;
use App\Models\Rapport;
use App\Models\Reclammation;
use App\Models\Soldes;
use App\Models\TemoinPartner;
use App\Models\WhatsAppVerify;
use App\Models\WhatsAppBackUp;
use App\Models\Wifi;
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

class OptionController extends Controller
{

    public function getStatsTransDay(Request $request){
        $toDay = date('Y-m-d');
        $result = [];
        $delayOk = 0;$delayko = 0; $notConfirm = 0;
        if(!empty($request->day)){ $toDay = $request->day; }
        $trans = Historiquetrans::where('created_at', 'like', "{$toDay}%")->whereNotIn('etat', ['attend', 'en_agence_attend'])->get();
        //return $trans;
        if(count($trans)>0){
            foreach ($trans as $tran){
                $etat = 'not_confirm'; $operation = $tran->operation;
                //recuperation du delay
                $delayOperation = $this->deleyOperation($tran->operation);
                //verification de l'etat de la transaction
                if(in_array($tran->etat, ['CONFIRMEE', 'confirme'])){
                    $delayTraitmentOk = date('Y-m-d H:i:s', strtotime("$tran->created_at + $delayOperation minutes"));
                    if($delayTraitmentOk >= $tran->updated_at){
                        $etat = 'confirmOk';
                        $delayOk = $delayOk+1;
                    }else{
                        $etat = 'confirmKo';
                        $delayko = $delayko+1;
                    }
                }else{
                    $notConfirm = $notConfirm+1;
                }

                // operation rendu de monnaie
                if($tran->param8 == 'partenaire'){
                    $operation = 'rm';
                }

                // Mise à jour du tableau final
                if(array_key_exists($operation, $result)){
                    //return [ $operation, $result];
                    $result["$operation"]["$etat"] = $result["$operation"]["$etat"] +1;
                    $result["$operation"]["all"] = $result["$operation"]["all"] +1;
                }else{
                    $ok = 0; $ko= 0; $not = 0; $all = 1;
                    if($etat == 'confirmOk'){
                        $ok = 1;
                    }elseif ($etat == 'confirmKo'){
                        $ko= 1;
                    }else{
                        $not = 1;
                    }
                    $tab = array("$operation" => [
                        'confirmOk' => $ok,
                        'confirmKo' => $ko,
                        'not_confirm' => $not,
                        'all'=> $all
                    ]);
                    if(count($result) == 0){
                        $result = $tab;
                    }else{
                        $result = array_merge($result, $tab);
                    }

                }
            }

            $rapport = Rapport::where('day', $toDay)->first();
            if($rapport){
                Rapport::where('day', $toDay)->update([
                    'total' => count($trans) + $rapport->reclams,
                    'delay_ok' => $delayOk + $rapport->reclams_delay_ok,
                    'delay_ko' => $delayko + $rapport->reclams_delay_ko,
                    'not_confirm' => $notConfirm + $rapport->reclams_not_confirm,
                    'trans' => count($trans),
                    'trans_delay_ok' => $delayOk,
                    'trans_delay_ko'=> $delayko,
                    'trans_not_confirm' => $notConfirm,
                    'trans_datas' => json_encode($result)
                ]);
            }else{
                $myRapport = new Rapport([
                    'day' => $toDay,
                    'total' => count($trans),
                    'delay_ok' => $delayOk,
                    'delay_ko' => $delayko,
                    'not_confirm' => $notConfirm,
                    'trans' => count($trans),
                    'trans_delay_ok' => $delayOk,
                    'trans_delay_ko'=> $delayko,
                    'trans_not_confirm' => $notConfirm,
                    'trans_datas' => json_encode($result)
                ]);
                $myRapport->save();
            }
        }
        $this->getStatsReclamsDay($toDay);
        return count($trans);
    }

    public function getStatsReclamsDay($toDay){
        $delayOk = 0;$delayko = 0; $notConfirm = 0;
        $reclams = Reclammation::where('created_at', 'like', "{$toDay}%")->get();
        //return $trans;
        if(count($reclams)>0){
            foreach ($reclams as $reclam){
                //recuperation du delay
                $delayOperation = $this->deleyOperation('reclamation');
                //verification de l'etat de la transaction
                if($reclam->statut == 'closed'){
                    $delayTraitmentOk = date('Y-m-d H:i:s', strtotime("$reclam->created_at + $delayOperation hour"));
                    if($delayTraitmentOk >= $reclam->updated_at){
                        $delayOk = $delayOk+1;
                    }else{
                        $delayko = $delayko+1;
                    }
                }else{
                    $notConfirm = $notConfirm+1;
                }
            }
            $rapport = Rapport::where('day', $toDay)->first();
            if($rapport){
                Rapport::where('day', $toDay)->update([
                    'total' => count($reclams) + $rapport->trans,
                    'delay_ok' => $delayOk + $rapport->trans_delay_ok,
                    'delay_ko' => $delayko + $rapport->trans_delay_ko,
                    'not_confirm' => $notConfirm + $rapport->trans_not_confirm,
                    'reclams' => count($reclams),
                    'reclams_delay_ok' => $delayOk,
                    'reclams_delay_ko'=> $delayko,
                    'reclams_not_confirm' => $notConfirm
                ]);
            }else{
                $myRapport = new Rapport([
                    'day' => $toDay,
                    'total' => count($reclams),
                    'delay_ok' => $delayOk,
                    'delay_ko' => $delayko,
                    'not_confirm' => $notConfirm,
                    'reclams' => count($reclams),
                    'reclams_delay_ok' => $delayOk,
                    'reclams_delay_ko'=> $delayko,
                    'reclams_not_confirm' => $notConfirm
                ]);
                $myRapport->save();
            }
        }
        return count($reclams);
    }

    public function deleyOperation($data){
        $delay = 2;
        switch ($data){
            case 'transfert_visa': case 'recharge_visa_uba': case 'achat_visa_uba':
            $delay = 15;
            break;
            case 'gam_transfert': case 'recharge_compte_gam':
            $delay = 1;
            case 'reclamation':
                $delay = 1;
                break;
        }
        return $delay;
    }

    public function getLastFiveDaysRapport(Request $request){
        $now = date('Y-m-d');
        $day = date('Y-m-d', strtotime("$now -1 day"));
        if(!empty($request->day)){ $day = $request->day; }
        $minusFiveDays = date('Y-m-d', strtotime("$day -7 days"));
        $rapports = DB::select("select * from rapports where day between '".$minusFiveDays."' and '".$day."' and deleted_at is null order by day ");
        return response()->json([
            'statut' => true,
            'body' => $rapports,
            'day' => $day
        ]);
    }



    public function getMenu(Request $request){
        $customer = Customer::where('id', $request->customer)->orWhere('phoneclient',$request->customer)->first();
        if($customer){
            $myMenu = DB::select("select * from menus m inner join menu_customer mc where m.id = mc.menu and mc.customer ='".$customer->id."' and mc.status = 1 and m.status = 1 order by score desc ");
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
                $myMenu = DB::select("select * from menus m inner join menu_customer mc where m.id = mc.menu and mc.customer ='".$customer->id."' and mc.status = 1 and m.status = 1 order by score desc ");
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

    public function storeCustomerCommande(Request $request){
        $account = Customer::where('id', intval($request->customer))->orWhere('phoneclient',$request->customer)->first();
        if(empty($account)){
            $customer = new Customer([
                'nom'=> $request->nom,
                'prenom'=> $request->prenom,
                'pays'=> '241',
                'phoneclient'=> $request->customer,
                'mdpclient'=> '1234',
                'otp'=> 'open',
                'customer_type' => $request->typeSubscribe,
            ]);
            $customer->save();
            $account = $customer;
        }else{
            Customer::where('id', $account->id)->update([
                'customer_type' => $request->typeSubscribe
            ]);
        }
        $customer = CustomerCommande::where('customer', $account->id)->first();
        if($customer){
            return response()->json([
                'statut'=> false,
                'message' => 'Client déjà éligible aux commandes'
            ]);
        }else{
            $newCustomer = new  CustomerCommande([
                'customer' => $account->id,
                'nom_commande' => $request->nom. ' '.$request->prenom,
                'vendeur' => $request->vendeur,
                'post' => $request->post,
                'details' => $request->details,
                'plafond' => $request->platfond,
                'status_commande' => $request->status,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude
            ]);
            $newCustomer->save();

            $customerCotroller = new CustomerController();
            $vendeur = Customer::where('id', intval($request->vendeur))->first();
            $customerCotroller->sendNotificationInprogram($vendeur->phoneclient, $vendeur->phoneclient, 'info', '', 'Nouveau client dans votre zone', '' ,'');
            return response()->json([
                'statut'=> true,
                'message' =>  $request->status == 1?'Client éligible aux commandes' : 'Votre requête est encours de verification'
            ]);
        }
    }
    
    public function getOrSetCustomerCommande(REquest $request){
        $customer = CustomerCommande::where('customer', $request->customer)->first();
        if($customer){
            if($request->action == 'set'){
                Customer::where('id', $customer->id)->update([
                    'status_commande' => $customer->status == 1? 0 : 1
                ]);
                return response()->json([
                    'statut'=> true,
                    'body' => $customer,
                    'plafond'=> $customer->plafond,
                    'message' => '',
                    'messagePlafond'=> 'Votre plafond actuel est de ' .
                        $customer->plafond. ' FCFA'
                ]);
            }else{
                if($customer->status_commande == 1){
                    $dettes = 0;
                    $oldCommandes = Historiquetrans::where('id_customer', $customer->customer)->where('param8', 'commande')->get();
                    if(count($oldCommandes)>0){
                        foreach ($oldCommandes as $oldCommande){
                            $dettes = $dettes + intval($oldCommande->montant_sans_frais);
                        }
                    }
                    $myPlafond = $customer->plafond - $dettes;
                    return  response()->json([
                        'statut'=> true,
                        'body' => $customer,
                        'plafond'=> $myPlafond,
                        'message' => '',
                        'messagePlafond'=> 'Votre plafond actuel est de ' .
                            $myPlafond. ' FCFA'
                    ]);
                }else{
                    return response()->json([
                        'statut'=> false,
                        'message' => 'Votre demande est encours de verification'
                    ]);
                }
            }

        }else{
            return response()->json([
                'statut'=> false,
                'message' => "0"
            ]);
        }
    }
    
    public function getVendeurCommande(REquest $request){
        $vendeurs = Customer::where('customer_type', 'vigil')->get();
        $customer = Customer::where('id', $request->customer)->get();
        if(count($vendeurs)>0){
            return  response()->json([
                'statut'=> true,
                'body' => $vendeurs,
                'customer' => $customer,
                'message' => ''
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'message' => "0"
            ]);
        }
    }
    
    public function getOrSetCommande(Request $request){
        if($request->action == 'get') {
            $commandes = Historiquetrans::where('param8','commande')->where('id_customer', $request->customer)->orderBy('id', 'desc')->get();
            if($request->user == 'vigil'){
                $commandes = DB::select("select * from historiquetrans where cast(id_customer as int) in (select customer from customers_commandes where vendeur = '".$request->customer."' and deleted_at is null) and param8 = 'commande' and deleted_at is null order by id desc ");
            }elseif ($request->user == 'agent'){
                $commandes = Historiquetrans::where('param8','commande')->orderBy('id', 'desc')->get();
            }
            $commande = CustomerCommande::where('customer', $request->customer)->first();
            return response()->json([
                'statut'=> true,
                'body' =>$commandes,
                'commande' => $commande
            ]);
        }else{
            $commande = Historiquetrans::where('id', $request->commande)->first();
            if($commande){
                Historiquetrans::where('id', $commande->id)->update([
                    'param8' => 'commande_validée'
                ]);
                return response()->json([
                    'statut'=> true,
                    'message' => "Opération réussie"
                ]);
            }else{
                return response()->json([
                    'statut'=> false,
                    'message' => "0"
                ]);
            }
        }
    }
    
    public function getCustomerCommande(Request $request){
        if($request->action == 'set'){
            CustomerCommande::where('id', $request->commande)->update([
                'status_commande' => 1, 'plafond' => 500
            ]);
            return response()->json([
                'statut'=> true,
                'message' => 'Option commande activée',
            ]);
        }else{
            $gains = 0;
            $payment = 0;
            $enable = 0;
            $disable = 0;
            $customers = DB::select("select * from customer c inner join customers_commandes cc on c.id = cc.customer where  c.deleted_at is null and cc.deleted_at is null and cc.customer != '".$request->customer."' order by cc.created_at  desc ");
            if($request->user == 'vigil'){
                $customers = DB::select("select * from customer c inner join customers_commandes cc on c.id = cc.customer where  c.deleted_at is null and cc.deleted_at is null and cc.vendeur = '".$request->customer."' and cc.customer != '".$request->customer."' order by cc.created_at  desc ");
            }
            $customer = Customer::where('id', $request->customer)->get();
            $commande = CustomerCommande::where('customer', $request->customer)->first();
            if(count($customers)>0){
                foreach ($customers as $custom){
                    $gains =!empty($custom->montant)? $gains + $custom->montant: $gains;
                    if($custom->plafond > 0 && $custom->montant > 0 && $custom->status_commande == 1){
                        $payment = $payment + 1;
                    }else if($custom->plafond == 0 && $custom->status_commande == 0){
                        $enable = $enable +1;
                    }else if($custom->plafond > 0 && $custom->status_commande == 0){
                        $disable = $disable+1;
                    }
                }
            }
            
            $myCustomer = Customer::where('id', $request->customer)->first();
            //return $myCustomer;
            $numeroZero = $myCustomer->phoneclient;
            $collects = Historiquetrans::where(function($q) use($numeroZero) {
                $q->where('numclient', $numeroZero)->orWhere('phonevendeur',$numeroZero);
            })->where('etat','CONFIRMEE')->where('operation', 'collect_commande')->orderBy('created_at', 'desc')->take(25)->get();

            return response()->json([
                'statut'=> true,
                'collects'=>$collects,
                'payment' => $payment,'enable'=>$enable,'disable'=>$disable,
                'body' => $customers,
                'indication'=> 'Retrouvez la liste de vos clients ici',
                'customer' => $customer,
                'commande' => $commande,
                'gains' => $gains
                
            ]);
        }
    }
    
    public function getCountCustomerOrCommande(Request $request){
        $myCount = Historiquetrans::where('param8', 'commande')->where('id_customer', strval($request->customer))->get();
        if($request->user == 'vigil'){
            $myCount = CustomerCommande::where('vendeur', $request->customer)->where(function($q) {
                $q->where('montant','>', 0)->orWhere('status_commande', 0);})->get();
        }else if(in_array($request->user, ['agent', 'collecteur'])){
            $myCount = CustomerCommande::where('montant','>', 0)->orWhere('status_commande', 0)->get();
        }
        $commande = CustomerCommande::where('customer', $request->customer)->first();
        return response()->json([
            'statut'=> true,
            'commande' => $commande,
            'body' => count($myCount)
        ]);
    }
    
    
    public function recupCommande(Request $request){
        // recup data customer
        $commande = CustomerCommande::where('customer', intval($request->customer))->first();
        if(empty($commande)){
            return response()->json([
                'statut'=> false,
                'message' => 'Opération échouée'
            ]);
        }else{
            // update agent
            $commandeAgent =  CustomerCommande::where('customer', $request->agentId)->first();
            if($commandeAgent){
              $agent = Customer::where('id',$commandeAgent->customer)->first();
                $customer = Customer::where('id',$commande->customer)->first();
                
                CustomerCommande::where('customer', $commandeAgent->customer)->update([
                    'montant' => $commandeAgent->montant + $commande->montant
                ]);

                // update client
                CustomerCommande::where('customer', $commande->customer)->update([
                    'montant' => 0
                ]);
                Historiquetrans::where('id_customer', strval($commande->customer))->where('param8', 'commande')->update([
                    'param8'=> 'commande_'.$request->agentType.'_'.$request->agentId
                ]);
                
                $historiqueTrans = new Historiquetrans([
                    'operation' => 'collect_commande',
                    'reference' => 'AUTO',
                    'etat' => 'CONFIRMEE',
                    'numclient' => $customer->phoneclient,
                    'phonevendeur' => $agent->phoneclient,
                    'content'=> $agent->customer_type,
                    'montant' => strval($commande->montant),
                    'montant_sans_frais' => strval($commande->montant),
                    'frais' => '0',
                    'solde' => $agent->solde,
                    'origine_operation' => empty($request->app)? 'flutterApp' : $request->app,
                    'id_customer' => $agent->id,
                    'tentative_effectue' => '0',
                ]);
                $historiqueTrans->save();
            }

            return response()->json([
                'statut'=> true,
                'message' => 'Opération réussie'
            ]);
        }
    }
    
    public function paymentCommande(Request $request){
        // recup data customer
        if(empty($request->trans)){
           $transactions = Historiquetrans::where('etat', 'atraiter')->where('operation', 'payment_commande')->get();
        }else{
            $transactions = Historiquetrans::where('id',$request->trans)->get();
        }

        if(count($transactions)>0){
            foreach ($transactions as $transaction){
                $customerCommade = CustomerCommande::where('customer', intval($transaction->id_customer))->first();
                if($transaction->content == 'all'){
                    $commandes = Historiquetrans::where('id_customer', $transaction->id_customer)->where('param8', 'commande')->get();
                    $montant = 0;
                }else{
                    $commandes = Historiquetrans::where('id', intval($transaction->content))->get();
                    $montant = $customerCommade->montant - $transaction->montant_sans_frais;
                }
                if(count($commandes)>0){
                   foreach ($commandes as $commande) {
                      Historiquetrans::where('id', $commande->id)->update([
                          'param8' => 'commande_ok_by_customer'
                      ]);
                   }
                }

                CustomerCommande::where('id', $customerCommade->id)->update([
                    'montant'=> $montant
                ]);
                Historiquetrans::where('id', $transaction->id)->update([
                    'etat'=> 'CONFIRMEE'
                ]);
            }
        }
        return [$transactions, $request->trans];
    }
}
