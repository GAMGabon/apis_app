<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Cadeau;
use App\Models\CustomerComplement;
use App\Models\CustomerCommande;
use App\Models\ForfaitInternational;
use App\Models\GamElectriciteHist;
use App\Models\GamRechargeHist;
use App\Models\Historiquetrans;
use App\Models\Notif;
use App\Models\Orders;
use App\Models\ProgramSubscription;
use App\Models\Reclammation;
use App\Models\Solde;
use App\Models\Carte;
use App\Models\Partage;
use App\Models\Vue;
use App\Models\WhatsAppVerify;
use App\Models\WhatsAppBackUp;
use App\Models\SimInternal;
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
use Throwable;


class HistoriquetransController extends Controller
{
    //
    public function customerHistoryTrans(Request $request){

        $numSansZero = substr($request->numCustomer, 1);
        $numeroZero = $request->numCustomer;
        $id= $request->idCustomer;
        $now = date("Y-m-d");
        if(!empty($request->option) && $request->option == 'new' ){
            if($request->operation == '' || empty($request->operation)){
                
                if($numeroZero =='074582442' || $numeroZero =='077794475' ){
                    $hisrory = Historiquetrans::whereIn('operation',['achat_credit', 'rendu_monnaie_simple', 'rendu_monnaie_credit','recharge_compte_gam'])->whereNotIn('etat',['attend'])->where(function($q) {
                    $q->whereIn('numclient', ['076367021','077873364','077092169','066044255','062535686','074572270','074872801','076413186','076411185','077526460','077212706','074667212'])->orWhere('phonevendeur', '066044255');
                    })->where('montant_sans_frais', '500')->orderBy('created_at', 'desc')->take(150)->get();
                }else{
                    $hisrory = Historiquetrans::where(function($q) use($numeroZero,$numSansZero) {
                    $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero);
                    })->whereNotIn('etat',['attend'])->whereNotIn('operation',['paiement_partenaire', 'debit'])->orderBy('created_at', 'desc')->take(100)->get();  
                }
                
                /*$hisrory = Historiquetrans::where(function($q) use($numeroZero,$numSansZero) {
                    $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero);
                })->whereNotIn('etat',['attend'])->whereNotIn('operation',['paiement_partenaire', 'debit'])->orderBy('created_at', 'desc')->take(100)->get();*/
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
                    $hisrory = Historiquetrans::whereIn('operation',['achat_credit', 'rendu_monnaie_simple', 'rendu_monnaie_credit','recharge_compte_gam'])->whereNotIn('etat',['attend'])->where(function($q) use($id,$num, $numSansZero) {
                    $q->whereIn('numclient', [$num, $numSansZero])->orWhere('phonevendeur', $num)->orWhere('id_customer',$id);
                    })->where('created_at','like', ''.strval($now).'%')->orderBy('id', 'desc')->get();
                }else {
                    $hisrory = Historiquetrans::whereIn('operation',['achat_credit', 'rendu_monnaie_simple', 'rendu_monnaie_credit','recharge_compte_gam'])->whereNotIn('etat',['attend'])->where(function($q) use($id,$num, $numSansZero) {
                    $q->whereIn('numclient', [$num, $numSansZero])->orWhere('phonevendeur', $num)->orWhere('id_customer',$id);
                    })->orderBy('created_at', 'desc')->take(100)->get();
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
        }else{
            if($request->operation == ''){
                $hisrory = Historiquetrans::where('phonevendeur', $request->numCustomer)->whereNotIn('etat',['attend'])->whereNotIn('operation',[ 'recharge_by_card'])->where('etat','!=','QR')->orderBy('created_at', 'desc')->take(100)->get();
            }elseif ($request->operation == 'visa'){
                $hisrory = Historiquetrans::where('phonevendeur', $request->numCustomer)->whereNotIn('etat',['attend'])->whereIn('operation',[ 'recharge_visa_uba', 'achat_visa_uba','livraison_visa_uba' ])->where('etat','!=','QR')->orderBy('created_at', 'desc')->take(25)->get();
            }elseif ($request->operation == 'edan'){
                $hisrory = Historiquetrans::where('phonevendeur', $request->numCustomer)->whereNotIn('etat',['attend'])->where('operation', 'achat_unites_electrique')->where('etat','!=','QR')->orderBy('created_at', 'desc')->take(50)->get();
                //$hisrory = GamElectriciteHist::where('num_client', $request->numCustomer)->where('etat','!=','QR')->orderBy('created_at', 'desc')->get();
            }else if($request->operation == 'partenaire'){
                $id= $request->idCustomer;
                $num= $request->numCustomer;
                $hisrory = Historiquetrans::whereIn('operation',[ 'achat_credit', 'rendu_monnaie_simple', 'rendu_monnaie_credit', 'recharge_compte_gam'])->whereNotIn('etat',['attend'])->where(function($q) use($id,$num,$numSansZero) {
                    $q->whereIn('numclient', [$num, $numSansZero])->orWhere('phonevendeur', $num)->orWhere('id_customer',$id);
                })->where('created_at','like', ''.strval($now).'%')->orderBy('id', 'desc')->get();
            }else if($request->operation == 'forfait'){
                $hisrory = Historiquetrans::where('id_customer',$request->idCustomer)->whereNotIn('etat',['attend'])->whereIn('operation',[ 'esim_international', 'sim_international', 'achat_forfait', 'forfait_international'])->orderBy('id', 'desc')->take(25)->get();
            }
            else if($request->operation == 'gamtv'){
                $hisrory = Historiquetrans::where('id_customer',$request->idCustomer)->whereNotIn('etat',['attend'])->whereIn('operation',[ 'achat_box', 'abonnement_tv'])->orderBy('id', 'desc')->take(50)->get();
            } else if($request->operation == 'transfert'){
                $hisrory = Historiquetrans::where('id_customer',$request->idCustomer)->whereNotIn('etat',['attend'])->whereIn('operation',['rendu_monnaie_simple', 'gam_transfert', 'transfert_mobile', 'transfert_visa'])->orderBy('id', 'desc')->take(25)->get();
            }else if($request->operation == 'paymentWait'){
                $hisrory = Historiquetrans::where('phonevendeur',$numeroZero)->whereIn('etat',['attend', 'en_agence_attend'])->orderBy('created_at', 'desc')->take(25)->get();
            }
            else{
                $hisrory = Historiquetrans::where('phonevendeur', $request->numCustomer)->whereNotIn('etat',['attend'])->where('operation', $request->operation)->where('etat','!=','QR')->orderBy('created_at', 'desc')->take(25)->get();
            }
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

    public function remEdan(){
        $remboursement = $this->createTrans('recharge_compte_gam', 'RECHARGE GAMPAY', 'CONFIRMEE','077008627', '077008627', 'recharge_compte_gam', '9900', '9900', '0', '9900', 'BackOffice', 5198144, '');
        if($remboursement == 1){
            Customer::where('id', 5198144)->update([
                'solde'=> '9900'
            ]);
        }
        return 1;
    }

    public function newHistoriqueTrans(Request $request){
        
        $server =  $request->ip();
        if(str_contains(strval($server), "41.202.207") || str_contains(strval($server),  "102.244.223")){
            return response()->json([
                'statut'=> false,
                'message'=> 'Le montant doit être inférieur à 1000 FCFA',
                'type'=> 'normal'
            ]);
        }

        $listeNoire = ['077641174','066202122', '074405969', '077641174', '066666666', '066303132', '066323334', '066333435', '077251198', '077251198', '076467594', '074629390', '076346949'];

        if($request->histOperation == 'transfert_ria' || $request->histPaiement == 'visaIwomi' || in_array(strval($request->histPhonevendeur), $listeNoire)){
            return response()->json([
                'statut'=> false,
                'message'=> 'Service indisponible pour le moment'
            ]);
        }else{
            // mettre à jour le champs unlocktoken

            $client = Customer::where('phoneclient',$request->histPhonevendeur)->first();


            switch ($request->histOperation){
                case 'recharge_visa_uba':
                    // if($request->histEtat != "en_agence_attend"){
                    //     return response()->json([
                    //         'statut'=> false,
                    //         'message'=> 'Service actuellement en maintenance'
                    //     ]);
                    // }

                    // verification de la carte
                    $carteVerification = Carte::where('id_client', $request->histOperateur)->get();
                    if(count($carteVerification)==0){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Carte inexistante'
                        ]);
                    }

                    if( $request->histEtat == ""){
                        $etat = 'attend';
                    }else{
                        $etat = $request->histEtat;
                    }
                    $ref = 'AUTO';
                    $param7 = $request->histPaiementYT;
                    $param4 = $request->histPaysCodeJT;
                    if( $request->histEtat == "en_agence_attend" || $client->statut == 'free'){
                        $frais = 0;
                    }else{
                        $frais = doubleval($request->histMontantSannsFrais)*0.025;
                    }
                    $mymontant = doubleval($request->histMontantSannsFrais) + $frais;
                    $carte = Carte::where('id_client', $request->histOperateur)->first();
                    if(empty($carte)){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Carte introuvable'
                        ]);
                    }
                    if($carte->owner != 'UBA'){
                        $montantSansFrais = doubleval($request->histMontantSannsFrais) - ((doubleval($request->histMontantSannsFrais) * 0.075) + 595);
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Service en maintenance'
                        ]);
                    }else{
                        $montantSansFrais = $request->histMontantSannsFrais;
                    }

                    // alerte Agents
                    $agentsVisa = Customer::whereIn('username', ['agent_gam_sigma', 'Cp_gam_solde_gam'])->get();// recupère la liste des agents
                    if($agentsVisa){
                        foreach ($agentsVisa as $agent){
                            $message = "Une recharge Visa de $request->histMontantSannsFrais FCFA pour le compte $request->histOperateur a été initié par le $request->histPhonevendeur";
                            $this->sendNotificationInprogram($agent['phoneclient'], $agent['phoneclient'], 'info', '',$message, '', '');
                        }
                    }

                    break;
                case 'achat_visa_uba':
                    if( $request->histEtat == ""){
                        $etat = 'attend';
                    }else{
                        $etat = $request->histEtat;
                    }
                    $ref = 'AUTO';
                    $param7 = $request->histPaiementJT;
                    $param4 = $request->histPaysCodeJT;
                    if( $request->histEtat == "en_agence_attend" || $client->statut == 'free'){
                        $frais = 0;
                    }else{
                        $frais = 0;
                        //$frais = doubleval($request->histMontantSannsFrais)*0.025;
                    }
                    $mymontant = doubleval($request->histMontantSannsFrais) + $frais;
                    $montantSansFrais = $request->histMontantSannsFrais;

                    break;
                default:
                    $etat = $request->histEtat;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    if($request->histOperation == 'achat_wifi' && $request->histEtat =='atraiter'){
                        $ref = "bonus" ;
                    }elseif ($request->histOperation == 'achat_action') {
                        $ref = $request->histPaysCodeIso;
                    }elseif ($request->histPaiement == 'bfm') {
                        $ref = "bfm" ;
                    }
                    else{
                        $ref = rand(1,6);
                    }
                    if( $request->histEtat == "en_agence_attend" || $client->statut == 'free'){
                        $frais = 0;
                    }else{
                        $frais =  $request->histFrais;
                    }
                    $mymontant = $request->histMontantfrais;
                    $montantSansFrais = $request->histMontantSannsFrais;
            }

            $param8 = $request->histToken;
            if($request->histEtat == "commande" || $request->histPaiement == "commande"){
                $param8 = 'commande';
                $etat = 'atraiter';
            }

            $now = date("Y-m-d");
            
            
            $veriTrans = Historiquetrans::where('etat', 'attend')
                ->where('phonevendeur', $request->histPhonevendeur)
                ->where('id', '>' , 37322554)
                ->where('id_customer', $request->histIdCustomer)
                ->where('created_at','like', ''.strval($now).'%')
                ->get();

            if(count($veriTrans)>=2 && count($veriTrans)<4){
                $messageNotif = "Vous recontrez des difficultés à effectuer vos opérations? Contactez notre service client via l'icône WhatsApp dans votre application";
                $message = "Plusieurs tentatives effectuées par le *$request->histPhonevendeur* Aujourd'hui. La dernière *$request->histOperation* de *$request->histMontantSannsFrais* pour le *$request->histNumclient*";
               
                Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=".$message);
                $this->sendNotificationInprogram($request->histPhonevendeur, $request->histPhonevendeur, 'info', '',$messageNotif, '', '');
            }

            /*if(!empty($request->device)){
                $dataDevice = $request->device;
                if(!empty($dataDevice['myDevice']) && empty($client['my_device'])){
                    $this->recupDataDevice($dataDevice, $request->histIdCustomer);
                }
                $myDevice = json_encode($request->device);
                if(str_contains($myDevice, 'DOCOMO\/F04K') || str_contains($myDevice, '398D728F-8C79-47A3-89A1-6B2EEDB4E4EA')){
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Service indisponible, contacter le service client',
                        'type'=> 'normal'
                    ]);
                }
            }*/

            // On virifie s'il n'y a pas la même transaction en base de données

            if(str_contains(strval($mymontant), '.') ){
                $tabAmount = explode('.', strval($mymontant));
                $mymontant = $tabAmount[0];
            }

            if(str_contains(strval($mymontant), ',')){
                $tabAmount = explode(',', strval($mymontant));
                $mymontant = $tabAmount[0];
            }
            
            $myContent = $request->histOperateur;
            
            if($request->histOperation == 'achat_wifi'){
                $myContent = 'jour'; 
                if($mymontant >= 5000 ){
                    $myContent = 'mois'; 
                }else if($mymontant >= 2000 && $mymontant < 5000 ){
                     $myContent = 'semaine'; 
                }
            }

            $historiqueTrans = new Historiquetrans([
                'operation' => $request->histOperation,
                'reference' => $ref,
                'etat' => $etat,
                'numclient' => str_replace(" ","",$request->histNumclient),
                'phonevendeur' => $request->histPhonevendeur,
                'content'=> $myContent,
                'montant' =>strval($mymontant),
                'montant_sans_frais' => $montantSansFrais,
                'frais' =>strval($frais),
                'solde' => $request->histSoldeCustomer,
                'origine_operation' => $request->origine == null? 'FlutterApp': $request->origine,
                'id_customer' => $request->histIdCustomer,
                'tentative_effectue' => empty($request->device)? '0' : json_encode($request->device),
                'param2' =>( $request->histOperation == 'recharge_visa_uba'
                    ||  $request->histOperation == 'esim_international'||  $request->histOperation == 'sim_international'
                    ||  $request->histOperation == 'forfait_international') ? $request->histPaysCodeIso : $request->histPaysCodeNull,
                'param4' => $param4,
                'param3' => $request->histPaiement == 'bfm'? 'BFM : ' .$request->histToken : $request->histPaiementNull,
                'param7' => $param7,
                'param8' => $param8,
                'param9' =>  ($request->histOperation == 'esim_international'||  $request->histOperation == 'sim_international'
                    ||  $request->histOperation == 'forfait_international' || $request->histOperation == 'achat_wifi') ?$request->histPaysCodeNull:'customer',
                'id_grand_parent' => $this->recupInfoCustomers($request->histPhonevendeur, $request->histNumclient)
            ]);

          

            $historiqueTrans->save();

            if (!empty($historiqueTrans->id)) {

                if($request->histEtat == "commande" || $request->histPaiement == "commande"){
                    $customerCommande = CustomerCommande::where('customer', intval($request->histIdCustomer))->first();
                    if($customerCommande){
                        CustomerCommande::where('customer', $customerCommande->customer)->update([
                            'montant' => $customerCommande->montant + intval($mymontant)
                        ]);
                    }
                }

                if($param7 =='bfm'){
                    $this->storePriority($request->histPhonevendeur, 'bfm');
                    $this->sendNotificationInprogram($request->histPhonevendeur, $request->histPhonevendeur, 'info', '',$request->histToken, '', '');
                }else{
                    $this->storePriority($request->histPhonevendeur, $request->histOperation);
                }
                
                if($request->histPaiement == 'am' && $request->histPhonevendeur == '074582442'){
                    $this->sendNotificationInprogram($request->histPhonevendeur, $request->histPhonevendeur, 'info', '',"Cher client, pour effectuer vos paiements via AIRTEL MONEY, assurez-vous d'avoir configuré la question de sécurité d'AIRTEL GABON. Composez *150# ...", '', '');
                }
                
                return response()->json([
                    'statut'=> true,
                    'idTrans'=> $historiqueTrans->id
                ]);
            }else {
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Service indisponible pour le moment'
                ]);
            }

        }
    }
    
    public function newHistoriqueTransReplication (Request $request){
        
        //Http::get("https://gampay.app/gamclients/public/api/newHistoryTransCustomerGam?histOperation=$request->histOperation&histOperateur=$request->histOperateur&histEtat=$request->histEtat&histNumclient=$request->histNumclient&histPhonevendeur=$request->histPhonevendeur&histMontantSannsFrais=$request->histMontantSannsFrais&histFrais=$request->histFrais&histSoldeCustomer=$request->histSoldeCustomer&histPaiement=$request->histPaiement&device=$request->device&origine=$request->origine&histToken=$request->histToken&histPaysCodeIso=$request->histPaysCodeIso");
        
        $server =  $request->ip();
        if(str_contains(strval($server), "41.202.207") || str_contains(strval($server),  "102.244.223")){
            return response()->json([
                'statut'=> false,
                'message'=> 'Le montant doit être inférieur à 1000 FCFA',
                'type'=> 'normal'
            ]);
        }

        $listeNoire = ['077641174','066202122', '074405969', '077641174', '066666666', '066303132', '066323334', '066333435', '077251198', '077251198', '076467594', '074629390', '076346949'];

        if($request->histOperation == 'achat_wifi' || $request->histOperation == 'transfert_ria' || $request->histPaiement == 'visaIwomi' || in_array(strval($request->histPhonevendeur), $listeNoire)){
            return response()->json([
                'statut'=> false,
                'message'=> 'Service indisponible pour le moment'
            ]);
        }else{
            // mettre à jour le champs unlocktoken

            $client = Customer::where('phoneclient',$request->histPhonevendeur)->first();


            switch ($request->histOperation){
                case 'recharge_visa_uba':

                    // verification de la carte
                    $carteVerification = Carte::where('id_client', $request->histOperateur)->get();
                    if(count($carteVerification)==0){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Carte inexistante'
                        ]);
                    }

                    if( $request->histEtat == ""){
                        $etat = 'attend';
                    }else{
                        $etat = $request->histEtat;
                    }
                    $ref = 'AUTO';
                    $param7 = $request->histPaiementYT;
                    $param4 = $request->histPaysCodeJT;
                    if( $request->histEtat == "en_agence_attend" || $client->statut == 'free'){
                        $frais = 0;
                    }else{
                        $frais = doubleval($request->histMontantSannsFrais)*0.025;
                    }
                    $mymontant = doubleval($request->histMontantSannsFrais) + $frais;
                    $carte = Carte::where('id_client', $request->histOperateur)->first();
                    if(empty($carte)){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Carte introuvable'
                        ]);
                    }
                    if($carte->owner != 'UBA'){
                        $montantSansFrais = doubleval($request->histMontantSannsFrais) - ((doubleval($request->histMontantSannsFrais) * 0.075) + 595);
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Service en maintenance'
                        ]);
                    }else{
                        $montantSansFrais = $request->histMontantSannsFrais;
                    }

                    // alerte Agents
                    $agentsVisa = Customer::whereIn('username', ['agent_gam_sigma', 'Cp_gam_solde_gam'])->get();// recupère la liste des agents
                    if($agentsVisa){
                        foreach ($agentsVisa as $agent){
                            $message = "Une recharge Visa de $request->histMontantSannsFrais FCFA pour le compte $request->histOperateur a été initié par le $request->histPhonevendeur";
                            $this->sendNotificationInprogram($agent['phoneclient'], $agent['phoneclient'], 'info', '',$message, '', '');
                        }
                    }

                    break;
                case 'achat_visa_uba':
                    if( $request->histEtat == ""){
                        $etat = 'attend';
                    }else{
                        $etat = $request->histEtat;
                    }
                    $ref = 'AUTO';
                    $param7 = $request->histPaiementJT;
                    $param4 = $request->histPaysCodeJT;
                    if( $request->histEtat == "en_agence_attend" || $client->statut == 'free'){
                        $frais = 0;
                    }else{
                        $frais = 0;
                        //$frais = doubleval($request->histMontantSannsFrais)*0.025;
                    }
                    $mymontant = doubleval($request->histMontantSannsFrais) + $frais;
                    $montantSansFrais = $request->histMontantSannsFrais;

                    break;
                default:
                    $etat = $request->histEtat;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    if($request->histOperation == 'achat_wifi' && $request->histEtat =='atraiter'){
                        $ref = "bonus" ;
                    }elseif ($request->histOperation == 'achat_action') {
                        $ref = $request->histPaysCodeIso;
                    }elseif ($request->histPaiement == 'bfm') {
                        $ref = "bfm" ;
                    }
                    else{
                        $ref = rand(1,6);
                    }
                    if( $request->histEtat == "en_agence_attend" || $client->statut == 'free'){
                        $frais = 0;
                    }else{
                        $frais =  $request->histFrais;
                    }
                    $mymontant = $request->histMontantfrais;
                    $montantSansFrais = $request->histMontantSannsFrais;
            }

            $param8 = $request->histToken;
            if($request->histEtat == "commande" || $request->histPaiement == "commande"){
                $param8 = 'commande';
                $etat = 'atraiter';
            }

            $now = date("Y-m-d");
            
            
            $veriTrans = Historiquetrans::where('etat', 'attend')
                ->where('phonevendeur', $request->histPhonevendeur)
                ->where('id', '>' , 37322554)
                ->where('id_customer', $request->histIdCustomer)
                ->where('created_at','like', ''.strval($now).'%')
                ->get();

            if(count($veriTrans)>=2 && count($veriTrans)<4){
                $messageNotif = "Vous recontrez des difficultés à effectuer vos opérations? Contactez notre service client via l'icône WhatsApp dans votre application";
                $message = "Plusieurs tentatives effectuées par le *$request->histPhonevendeur* Aujourd'hui. La dernière *$request->histOperation* de *$request->histMontantSannsFrais* pour le *$request->histNumclient*";
               
                Http::get("https://gampay.org/gamclients/public/api/sendWhatsappMessageInGroupWaAPIRequest?service=service_client&message=".$message);
                $this->sendNotificationInprogram($request->histPhonevendeur, $request->histPhonevendeur, 'info', '',$messageNotif, '', '');
            }

            /*if(!empty($request->device)){
                $dataDevice = $request->device;
                if(!empty($dataDevice['myDevice']) && empty($client['my_device'])){
                    $this->recupDataDevice($dataDevice, $request->histIdCustomer);
                }
                $myDevice = json_encode($request->device);
                if(str_contains($myDevice, 'DOCOMO\/F04K') || str_contains($myDevice, '398D728F-8C79-47A3-89A1-6B2EEDB4E4EA')){
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Service indisponible, contacter le service client',
                        'type'=> 'normal'
                    ]);
                }
            }*/

            // On virifie s'il n'y a pas la même transaction en base de données

            if(str_contains(strval($mymontant), '.') ){
                $tabAmount = explode('.', strval($mymontant));
                $mymontant = $tabAmount[0];
            }

            if(str_contains(strval($mymontant), ',')){
                $tabAmount = explode(',', strval($mymontant));
                $mymontant = $tabAmount[0];
            }

            $historiqueTrans = new Historiquetrans([
                'operation' => $request->histOperation,
                'reference' => $ref,
                'etat' => $etat,
                'numclient' => str_replace(" ","",$request->histNumclient),
                'phonevendeur' => $request->histPhonevendeur,
                'content'=> $request->histOperateur,
                'montant' =>strval($mymontant),
                'montant_sans_frais' => $montantSansFrais,
                'frais' =>strval($frais),
                'solde' => $request->histSoldeCustomer,
                'origine_operation' => $request->origine == null? 'FlutterApp': $request->origine,
                'id_customer' => $request->histIdCustomer,
                'tentative_effectue' => empty($request->device)? '0' : json_encode($request->device),
                'param2' =>( $request->histOperation == 'recharge_visa_uba'
                    ||  $request->histOperation == 'esim_international'||  $request->histOperation == 'sim_international'
                    ||  $request->histOperation == 'forfait_international') ? $request->histPaysCodeIso : $request->histPaysCodeNull,
                'param4' => $param4,
                'param3' => $request->histPaiement == 'bfm'? 'BFM : ' .$request->histToken : $request->histPaiementNull,
                'param7' => $param7,
                'param8' => $param8,
                'param9' =>  ($request->histOperation == 'esim_international'||  $request->histOperation == 'sim_international'
                    ||  $request->histOperation == 'forfait_international' || $request->histOperation == 'achat_wifi') ?$request->histPaysCodeNull:'customer',
                'id_grand_parent' => $this->recupInfoCustomers($request->histPhonevendeur, $request->histNumclient)
            ]);

            $historiqueTrans->save();

            if (!empty($historiqueTrans->id)) {

                if($request->histEtat == "commande" || $request->histPaiement == "commande"){
                    $customerCommande = CustomerCommande::where('customer', intval($request->histIdCustomer))->first();
                    if($customerCommande){
                        CustomerCommande::where('customer', $customerCommande->customer)->update([
                            'montant' => $customerCommande->montant + intval($mymontant)
                        ]);
                    }
                }

                if($param7 =='bfm'){
                    $this->storePriority($request->histPhonevendeur, 'bfm');
                    $this->sendNotificationInprogram($request->histPhonevendeur, $request->histPhonevendeur, 'info', '',$request->histToken, '', '');
                }else{
                    $this->storePriority($request->histPhonevendeur, $request->histOperation);
                }
                
                if($request->histPaiement == 'am' && $request->histPhonevendeur == '074582442'){
                    $this->sendNotificationInprogram($request->histPhonevendeur, $request->histPhonevendeur, 'info', '',"Cher client, pour effectuer vos paiements via AIRTEL MONEY, assurez-vous d'avoir configuré la question de sécurité d'AIRTEL GABON. Composez *150# ...", '', '');
                }
                
                return response()->json([
                    'statut'=> true,
                    'idTrans'=> $historiqueTrans->id
                ]);
            }else {
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Service indisponible pour le moment'
                ]);
            }

        }
    }

    // transaction gampay

    public function newHistoriqueTransGam(Request $request){
        
        $server = $request->ip();
        if( $request->histOperation == 'transfert_ria' || str_contains(strval($server), "41.202.207") || str_contains(strval($server),  "102.244.223") || str_contains(strval($server),  "102.244.45") ){
            return response()->json([
                'statut'=> false,
                'message'=> 'Service indisponible, contacter le service client',
                'type'=> 'normal'
            ]);
        }
        
        $now = date('Y-m-d');
        $listeNoire = ['066367176','077235455','077810267','077808995','065434773','074747020','076235553','077641174', '066202122', '074405969', '077641174', '066666666', '066303132', '066323334', '066333435', '077251198', '076467594', '074629390', '076346949'];

        if(in_array($request->histPhonevendeur, $listeNoire) || in_array($request->histNumclient, $listeNoire) ){
            return response()->json([
                'statut'=> false,
                'message'=> 'Service indisponible, contacter le service client',
                'type'=> 'normal'
            ]);
        }
        
        if(doubleval($request->histMontantSannsFrais)<100 && !in_array($request->histOperation, ['recharge_compte_gam', 'gam_transfert'])){
            return response()->json([
                'statut'=> false,
                'message'=> 'Le montant doit être superieur à 100 FCFA',
                'type'=> 'normal'
            ]);
        }
        
        if(doubleval($request->histMontantSannsFrais)>500 && $request->histOperation == 'rendu_monnaie_simple'){
            return response()->json([
                'statut'=> false,
                'message'=> 'Le montant doit être inférieur à 500 FCFA',
                'type'=> 'normal'
            ]);
        }
        
        /*$numLocked = Historiquetrans::where('numclient', $request->histNumclient)->where('etat', 'locked')->get();
        if(count($numLocked)>0){
            return response()->json([
                'statut'=> false,
                'message'=> 'Opération non autorisée, contacter le service client',
                'type'=> 'normal'
            ]);
        }*/
        
      

        $clients = Customer::where('phoneclient',$request->histPhonevendeur)->get();
        $tabCode = ['non'];
        /*$codes = DB::select("select distinct option1 from customer c where (c.type = 'prix_import' or c.type = 'pharmacie')");
        foreach($codes as $code){
            array_push($tabCode, $code->option1);
        }*/

        foreach ($clients as $client){
            
            if(strval($client['statut']) == 'closed' || $client['customer_type'] =='developper'){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Opération non autorisée, contactez le service client',
                    'type'=> 'normal'
                ]);
            }
            
            if($client['status'] == 1111 ){
                if(!in_array($request->histOperation , ['gam_transfert', 'recharge_compte_gam'])){
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Opération non autorisée, contactez le service client',
                        'type'=> 'normal'
                    ]); 
                }else{
                    $caisse = Customer::where('phoneclient',$request->histNumclient)->where('code_confirm', $client['code_confirm'])->where('username', $client['username'])->get();
                    if(count($caisse) == 0){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Opération non autorisée, contactez le service client',
                            'type'=> 'normal'
                        ]); 
                    }
                }
            }
            
            if(empty($request->device) && $request->histToken == 'partenaire' && !in_array($request->histPhonevendeur, ['076334457'])){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Opération non autorisée, contactez le service client',
                    'type'=> 'normal'
                ]); 
            }
            
            
            if(!empty($request->device) && $request->origine != 'flutterApp_RM_APK'){
                $dataDevice = $request->device;
                
                if(!empty($client['my_device']) ){
                    if(empty($dataDevice['myDevice'])){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Appareil non enregistré, contacter le service client',
                            'type'=> 'normal'
                        ]);
                    }else{
                        if($dataDevice['myDevice'] != $client['my_device']){
                            return response()->json([
                                'statut'=> false,
                                'message'=> 'Appareil inconnu, contacter le service client',
                                'type'=> 'normal'
                            ]);
                        }  
                    }
                }
                
                if(!empty($dataDevice['myDevice']) && empty($client['my_device'])){
                    $this->recupDataDevice($dataDevice, $client['id']);
                }
                
                $myDevice = json_encode($request->device);
                if(str_contains($myDevice, 'DOCOMO\/F04K') || str_contains($myDevice, 'DOCOMO SH-53A') || str_contains($myDevice, '398D728F-8C79-47A3-89A1-6B2EEDB4E4EA')){
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Service indisponible, contacter le service client',
                        'type'=> 'normal'
                    ]);
                }
            }
            
            //if($request->histOperation == 'transfert_mobile' && $client['status'] != 1000 ){
            /*if($request->histOperation == 'transfert_mobile'){
                $messageAlerteTransfert = "Transfert mobile de $request->histMontantSannsFrais FCFA initié par le $request->histPhonevendeur vers $request->histNumclient via GamPay";
                $this->sendWhatsAppMessage('24174582442',$messageAlerteTransfert, 'rm');
                $this->sendWhatsAppMessage('24102507006',$messageAlerteTransfert, 'rm');
            }*/

            // mettre à jour le champs unlocktoken
            if(!empty($request->origine) && $request->origine == 'flutterPwa'){
                if($client['unlock_token'] != 'pwa'){
                    //Customer::find(intval($client['id']))->update(['unlock_token'=>'pwa']);
                }
                $origine = 'flutterPwa2.2.0';
            }elseif($request->origine == "scyd" || $request->device == "whatsapp_flow"){
                $origine = 'scyd';
            }else{
                $origine = 'flutterApp2.2.0';
            }
            // fin mise à jour
            
            // verification whatsapp
            if($client['nom_mobile'] == "whatsappLockSuper" && $request->histToken == 'partenaire'){
            //if(str_contains($client['username'], '_gam')){
                $verifWhatsApp = $this->checkNumberUseWhatsapp($request->histNumclient);
                //$verifWhatsApp = 1;
            }else{
                $verifWhatsApp = 0;
            }
            switch ($request->histOperation){
                case 'recharge_visa_uba':
                    // verification de la carte
                    $carteVerification = Carte::where('id_client', $request->histOperateur)->get();
                    if(count($carteVerification)==0){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Carte inexistante',
                            'type'=> 'normal'
                        ]);
                    }

                    $etat = 'en_attente';
                    $ref = 'AUTO';
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    if($request->origine == 'scyd' || $client['statut'] == 'free'){
                        $frais = '0';
                        $mymontant = doubleval($request->histMontantSannsFrais);
                    }else{
                        $frais = doubleval($request->histMontantSannsFrais)*0.025;
                        $mymontant = doubleval($request->histMontantSannsFrais) + $frais;
                    }
                    $contenVisa = strval($request->histOperateur);
                    $carte = Carte::where('id_client', $request->histOperateur)->first();
                    if(empty($carte)){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Carte introuvable',
                            'type'=> 'normal'
                        ]);
                    }
                    if($carte->owner != 'UBA'){
                        $montantSansFrais = doubleval($request->histMontantSannsFrais) - ((doubleval($request->histMontantSannsFrais) * 0.075) + 595);
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Service en maintenance',
                            'type'=> 'normal'
                        ]);
                    }else{
                        $montantSansFrais = $request->histMontantSannsFrais;
                    }
                    $param7 = $request->histPaiementNull;
                    $param4 = $request->histPaysCodeNull;
                    $param2 = $request->histPaysCodeIso;
                    $param9 = 'customer';


                    // alerte Agents
                    $agentsVisa = Customer::whereIn('username', ['agent_gam_sigma', 'Cp_gam_solde_gam'])->get();// recupère la liste des agents
                    if($agentsVisa){
                        foreach ($agentsVisa as $agent){
                            $message = "Une recharge Visa de $request->histMontantSannsFrais FCFA pour le compte $request->histOperateur a été effectué par le $request->histPhonevendeur via GamPay";
                            $this->sendNotificationInprogram($agent['phoneclient'], $agent['phoneclient'], 'info', '',$message, '', '');
                        }
                    }
                    break;
                case 'achat_visa_uba':
                    $etat = 'en_attente';
                    $ref = 'AUTO';
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                   
                    if($request->origine == 'scyd' || $client['statut'] == 'free'){
                        $frais = '0';
                        $mymontant = doubleval($request->histMontantSannsFrais);
                    }else{
                        $frais = 0;
                        //$frais = doubleval($request->histMontantSannsFrais)*0.025;
                        $mymontant = doubleval($request->histMontantSannsFrais) + $frais;
                    }
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiementNull;
                    $param4 = $request->histPaysCodeNull;
                    $param2 = $request->histPaysCodeIso;
                    $param9 = 'customer';
                    break;
                case 'renduQR':
                    $ref = 'QR';
                    $phonevendeur = "0";
                    $numclient = "0";
                    $etat = 'QR';
                    if($request->origine == 'scyd' || $client['statut'] == 'free'){
                        $frais = '0';
                        $mymontant = doubleval($request->histMontantSannsFrais);
                    }else{
                        $frais = doubleval($request->histMontantSannsFrais)*0.025;
                        $mymontant = doubleval($request->histMontantSannsFrais) + $frais;
                    }
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiementNull; // pour que le champ soit null
                    // génération du code alphanumérique
                    $str = '1234567890abcefghijklmnopqrstuvwxyz';
                    $param4 = substr(str_shuffle($str), 0, 5);
                    $param2 = $request->histPaysCodeIsoNull;
                    $param9 = $request->histPaysCodeNull;
                    break;
                case 'achat_credit':
                    $param2 = $request->histPaysCodeIso;
                    $etat = 'atraiter';
                    $ref = rand(1,6);
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    if($request->histPaysCodeIso == 'rm' || $request->histToken == 'partenaire'){
                        $frais = "0";
                        $mymontant = $request->histMontantSannsFrais;
                    }else{
                        if($request->origine == 'scyd' || $client['statut'] == 'free'){
                            $frais = '0';
                            $mymontant = doubleval($request->histMontantSannsFrais);
                        }else{
                            $frais = 100;
                            $mymontant = doubleval($request->histMontantSannsFrais)+$frais;
                        }
                    }
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    $param9 = $request->histPaysCodeNull;
                    break;

                case 'rendu_monnaie_simple': case 'transfert_mobile': case 'transfert_visa':
               
                $param2 = $request->histPaysCodeIsoNull;
                $etat = 'atraiter';
                $ref = rand(1,6);
                $numclient =  $request->histNumclient;
                $phonevendeur = $request->histPhonevendeur;
                if($request->histToken == 'partenaire' || $request->histPaysCodeIso == 'rm'){
                    // $frais = "0";
                    // $mymontant = $request->histMontantSannsFrais;
                    $frais = doubleval($request->histMontantSannsFrais)*0.025;
                    $mymontant = doubleval($request->histMontantSannsFrais)+$frais;
                }else{
                    if($request->origine == 'scyd' || $client['statut'] == 'free'){
                        $frais = '0';
                        $mymontant = doubleval($request->histMontantSannsFrais);
                    }else{
                        $frais = doubleval($request->histMontantSannsFrais)*0.025;
                        $mymontant = doubleval($request->histMontantSannsFrais)+$frais;
                    }
                }
                $this->getDataRmNumclient($numclient);
                $montantSansFrais = $request->histMontantSannsFrais;
                $param7 = $request->histPaiementNull;
                $param4 = $request->histPaysCodeNull;
                $param9= "banking";
                break;
                case 'achat_forfait':
                    $param2 = $request->histPaysCodeIso;
                    $etat = 'atraiter';
                    $ref = 'FORFAIT_AIRTEL';
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    $frais = 0;
                    $mymontant = doubleval($request->histMontantSannsFrais);
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    $param9 = $request->histPaysCodeNull;
                    break;
                case 'recharge_compte_gam':
                case 'gam_transfert':
                    $param2 = $request->histPaysCodeIso;
                    $etat = 'CONFIRMEE';
                    $ref = 'AUTO';
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    $frais = '0';
                    $mymontant = $request->histMontantSannsFrais;
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    $param9 = $request->histPaysCodeNull;
                    break;

                case 'paiement_partenaire': case 'achat_status': case 'paiement':
                $param2 = $request->histPaysCodeIso;
                $etat = 'atraiter';
                $ref = rand(1,6);
                $numclient =  $request->histNumclient;
                $phonevendeur = $request->histPhonevendeur;
                $frais = 0;
                $mymontant = doubleval($request->histMontantSannsFrais);
                $montantSansFrais = $request->histMontantSannsFrais;
                $param7 = $request->histPaiement;
                $param4 = $request->histPaysCode;
                $param9 = $request->histPaysCodeNull;
                break;
                default:
                    $param2 = $request->histPaysCodeIso;
                    $etat = 'atraiter';
                    $ref = rand(1,6);
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    if($request->origine == 'scyd' || $client['statut'] == 'free' || $request->histOperation == 'achat_wifi'){
                        $frais = '0';
                        $mymontant = doubleval($request->histMontantSannsFrais);
                    }else{
                        $frais = 100;
                        $mymontant = doubleval($request->histMontantSannsFrais)+100;
                    }
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    $param9 = $request->histPaysCodeNull;
            }
            
            
            if(($request->histOperation == "gam_transfert" || $request->histOperation == "recharge_compte_gam") && $numclient == $phonevendeur){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Entrez un compte GamPay différent du votre',
                     'type' => 'normal',
                ]);
            }

            // verification de solde
            $verifMySoldeCustomer = 'ko';

            /*if($request->origine != 'flutterApp_RM_APK'){
                if(doubleval($client['solde'])>= doubleval($mymontant)){
                    $verifMySoldeCustomer = 'ok';
                }
            }else{
                if(doubleval($client['solde'])-doubleval($mymontant) >= -1000){
                    $verifMySoldeCustomer = 'ok';
                }
            }*/
            
            if(doubleval($client['solde'])>= doubleval($mymontant)){
                $verifMySoldeCustomer = 'ok';
            }

            if($verifMySoldeCustomer == 'ok'){

                /*if(in_array($request->histPhonevendeur, ['', '077847829','076381774', '062939010', '077663471', '074698292', '066516769', '074322712'] )){
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Paiement via GamPay desactivé, contactez le service client'
                    ]);
                }*/
                
                if($request->histToken == 'partenaire' || $request->histPaysCodeIso == 'rm'){
                    // store activity partenaire
                    $lastActivity = $this->storeActivityPartner($client['id'], $client['last_transaction_at'], $client['stock_gab']);
                }
                
                //$this->storePriority($request->histPhonevendeur, $request->histOperation);
                $newSolde = doubleval($client['solde']) - doubleval($mymontant);
                Customer::where('id', $client['id'])->update([
                    'solde' =>strval($newSolde)
                ]);

                if($request->histOperation == "gam_transfert"){
                    if($numclient == $phonevendeur){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Entrez un compte GamPay différent du votre',
                             'type' => 'normal',
                        ]);
                    }
                    $receiver = Customer::whereIn('phoneclient',[$numclient, '0'.$numclient])->first();
                    if($receiver){
                        $montanttranfert = doubleval($receiver->solde) + doubleval($request->histMontantSannsFrais);
                        Customer::where('id',$receiver->id)->update([
                            'solde' => $montanttranfert,
                            'statut'=> $phonevendeur == '062934942'? 'free': $receiver->statut,
                        ]);
                        $this->sendNotificationInprogram($phonevendeur,$receiver->phoneclient,'gam_transfert', strval($mymontant),null,null,null);

                    }else{
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Le '.$numclient.' ne possède pas de compte GamPay. ',
                             'type' => 'normal',
                        ]);
                    }
                }else if($request->histOperation == "paiement_partenaire" || $request->histOperation == 'paiement'){

                    $veriTrans = Historiquetrans::where('id', intval($request->histOperateur))->first();
                    if($veriTrans){
                        Historiquetrans::where('id',$veriTrans->id)->update([
                            'etat' => 'atraiter',
                            'param2'=> 'gampay'
                        ]);
                        
                        return response()->json([
                            'statut'=> true,
                            'idTrans'=> $veriTrans->id,
                            'code'=>$veriTrans->param4,
                            'message'=> 'Transaction en cours',
                             'type' => 'normal',
                        ]);
                    }else{
                        return response()->json([
                            'statut'=> false,
                            'message'=> "Nous n'avons pas pu retrouver votre transaction",
                             'type' => 'normal',
                        ]); 
                    }
                   

                }else if($request->histOperation == "recharge_compte_gam"){
                    if($numclient == $phonevendeur){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Entrer un compte GamPay différent du votre',
                            'type'=> 'normal'
                        ]);
                    }
                    $ref = 'rm_3.0';
                    $receiver = Customer::whereIn('phoneclient',[$numclient, '0'.$numclient])->first();
                    if($receiver){
                        $parrain = Customer::where('phoneclient',$phonevendeur )->first();
                        $montanttranfert = doubleval($receiver->solde) + doubleval($request->histMontantSannsFrais);
                        Customer::where('id',$receiver->id)->update([
                            'solde' => $montanttranfert,
                            'statut'=> $phonevendeur == '062934942'? 'free': $receiver->statut,
                            'code_confirm'=> in_array($receiver->code_confirm, ['gam', null])?  $parrain->option1 : $receiver->code_confirm
                        ]);

                    }else{
                        $parrain = Customer::where('phoneclient',$phonevendeur )->first();

                        $customer = new Customer([
                            'nom'=> 'client',
                            'prenom'=> 'GAM',
                            'pseudo'=> 'client '. $parrain->nom,
                            'pays'=> '+241',
                            'phoneclient'=> $numclient,
                            'whatsapp'=> $numclient,
                            'solde'=> $request->histMontantSannsFrais,
                            'mdpclient'=> '1234',
                            'phoneparent'=> $parrain->phoneclient,
                            'option1'=> $parrain->option1,
                            'code_confirm'=> $parrain->code_confirm,
                            'satus'=>1
                        ]);
                        $customer->save();
                        Http::get('http://gampay.app/gamclients/public/api/recupIdCustomer');
                        $this->sendNotificationInprogram($numclient,$numclient,'info', strval($mymontant),"Bienvenu sur GamPay, Votre compte a été créé par $parrain->nom",null,null);
                    }

                    $this->sendNotificationInprogram($phonevendeur,$numclient,'recharge_compte_gam', strval($mymontant),null,null,null);

                }
                
                $nowCountTrans = date("Y-m-d");
                $veriCountTrans = Historiquetrans::where('phonevendeur', $request->histPhonevendeur)
                ->where('numclient', $request->histNumclient)
                ->where('operation', $request->histOperation)
                ->where('created_at','like', ''.strval($nowCountTrans).'%')
                ->get();
                
                
                if($origine == "scyd"){
                    $myOperateur =  $this->getOperateur($request->histOperation, $numclient, '241');
                }else{
                    $myOperateur = $request->histOperateur;
                    if((in_array($request->histNumclient, $listeNoire) && !in_array($request->histOperation , ['gam_transfert', 'recharge_compte_gam'])) || $client['etat'] == 'lock' || $client['etat'] == 'closed' || ($request->histOperation == 'transfert_mobile' && $client['username'] == 'partenaire')){
                        $etat = 'locked';
                    }elseif(($request->histOperation == 'transfert_mobile' && $client['status'] != 1000) || count($veriCountTrans)>=2 && $request->histOperation != 'achat_wifi'){
                        $verifEtat = 'validation';
                        $lastTrans = Historiquetrans::where('numclient', $request->histPhonevendeur)->orWhere('phonevendeur',$request->histPhonevendeur)->orderBy('id', 'desc')->first();
                        if($lastTrans){
                            if(in_array($lastTrans->operation, ['gam_transfert', 'recharge_compte_gam']) && doubleval($lastTrans->montant) >= doubleval($mymontant) ){
                                $verifEtat = $etat;
                            }
                        }
                        $etat = $verifEtat;
                    } 
                }
                
               
                if($request->histOperation == 'achat_wifi'){
                    $myOperateur = 'jour'; 
                    if($mymontant >= 5000 ){
                        $myOperateur = 'mois'; 
                    }else if($mymontant >= 2000 && $mymontant < 5000 ){
                         $myOperateur = 'semaine'; 
                    }
                }

                $historiqueTrans = new Historiquetrans([
                    'operation' => $request->histOperation,
                    'reference' =>strval($ref),
                    'etat' => $etat,
                    'numclient' =>str_replace(" ","",$numclient),
                    'phonevendeur' => $phonevendeur,
                    'content'=> $myOperateur,
                    'montant' =>strval($mymontant),
                    'montant_sans_frais' => $montantSansFrais,
                    'frais' =>strval($frais),
                    'solde' => $client['solde'],
                    'origine_operation' => $origine == null? 'FlutterApp': $origine,
                    'id_customer' => $request->histIdCustomer,
                    'tentative_effectue' => empty($request->device)? '0' : json_encode($request->device),
                    'param2' =>( $request->histOperation == 'recharge_visa_uba'
                        ||  $request->histOperation == 'esim_international'||  $request->histOperation == 'sim_international'
                        ||  $request->histOperation == 'forfait_international') ? $request->histPaysCodeIso : $request->histPaysCodeNull,
                    'param4' => $param4,
                    'param7' => $param7,
                    'param8' =>($request->histToken != 'partenaire' && $request->histOperation == "rendu_monnaie_simple")? 'transfert': $request->histToken,
                    'param9' => $param9,
                    'id_grand_parent' =>($request->histOperation =='recharge_compte_gam' || $request->histOperation == 'gam_transfert' || $request->histToken == 'partenaire')? $this->recupInfoCustomers($phonevendeur, $numclient): $request->histPaysCodeNull
                ]);
                $historiqueTrans->save();
                
                if($request->histPaiement == 'scyd'){
                    $trans = Historiquetrans::where('operation', 'recharge_compte_gam')->where('numclient', $request->histPhonevendeur)->where('content', 'whatsapp')->where('tentative_effectue', '0')->where('id','>',36724370)->first();
                    if($trans){
                        Historiquetrans::where('id', $trans->id)->update([
                            'tentative_effectue'=> '1'
                        ]);
                    }
                }

                return response()->json([
                    'statut'=> true,
                    'idTrans'=> $historiqueTrans->id,
                    'code'=>$historiqueTrans->param4,
                    'message'=> $request->histOperation == "gam_transfert"? 'Effectuée sans frais': 'Transaction en cours',
                     'type' => 'normal',
                ]);

            }else{
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Votre solde GamPay est insuffisant',
                     'type' => 'normal',
                ]);
            }
        }
    }


    public function newHistoriqueTransGab(Request $request){

        $listeNoire = ['077641174', '066202122', '074405969', '077641174', '066666666', '066303132', '066323334', '066333435', '077251198', '076467594', '074629390', '076346949'];

        if(in_array($request->histPhonevendeur, $listeNoire) || $request->histPhonevendeur == '077251198' ){
            return response()->json([
                'statut'=> false,
                'message'=> 'Service indisponible, contacter le service client'
            ]);
        }
        $clients = Customer::where('phoneclient',$request->histPhonevendeur)->get();
        $frais=0;
        foreach ($clients as $client){
            switch ($request->histOperation){
                case 'recharge_visa_uba':
                    // verification de la carte
                    $carteVerification = Carte::where('id_client', $request->histOperateur)->get();
                    if(count($carteVerification)==0){
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Carte inexistante'
                        ]);
                    }
                    $etat = 'en_attente';
                    $ref = 'AUTO';
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    $mymontant =$request->histMontantSannsFrais;
                    $contenVisa = strval($request->histOperateur);
                    //if($request->histPaysCodeIso == 'ORABANK'){

                    $montantSansFrais = $request->histMontantSannsFrais;
                    $frais = (intval($request->histMontantSannsFrais) * 0.03);
                    $param7 = $request->histPaiementNull;
                    $param4 = $request->histPaysCodeNull;
                    $param2 = $request->histPaysCodeIso;
                    $param9 = 'GAB';
                    break;
                case 'achat_visa_uba':
                    $etat = 'en_attente';
                    $ref = 'AUTO';
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    $mymontant =$request->histMontantSannsFrais;
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiementNull;
                    $param4 = $request->histPaysCodeNull;
                    $param2 = $request->histPaysCodeIso;
                    $param9 = 'GAB';
                    break;
                case 'renduQR':
                    $ref = 'QR';
                    $phonevendeur = "0";
                    $numclient = "0";
                    $etat = 'QR';
                    $mymontant =$request->histMontantSannsFrais;
                    $mymontant =$request->histMontantSannsFrais;
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiementNull; // pour que le champ soit null
                    // génération du code alphanumérique
                    $str = '1234567890abcefghijklmnopqrstuvwxyz';
                    $param4 = substr(str_shuffle($str), 0, 5);
                    $param2 = $request->histPaysCodeIsoNull;
                    $param9 = $request->histPaysCodeNull;
                    break;
                case 'achat_credit':
                    $param2 = $request->histPaysCodeIso;
                    $etat = 'atraiter';
                    $ref = rand(1,6);
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    $mymontant = $request->histMontantSannsFrais;
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    $param9 = $request->histPaysCodeNull;
                    break;

                case 'rendu_monnaie_simple': case 'transfert_mobile': case 'transfert_visa':
                $param2 = $request->histPaysCodeIsoNull;
                $etat = 'atraiter';
                $ref = rand(1,6);
                $numclient =  $request->histNumclient;
                $phonevendeur = $request->histPhonevendeur;
                $mymontant = $request->histMontantSannsFrais;
                $montantSansFrais = $request->histMontantSannsFrais;
                $param7 = $request->histPaiement;
                $param4 = $request->histPaysCodeNull;
                $param9= "banking";
                break;
                case 'achat_forfait':
                    $param2 = $request->histPaysCodeIso;
                    $etat = 'atraiter';
                    $ref = 'FORFAIT_AIRTEL';
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    $mymontant = doubleval($request->histMontantSannsFrais);
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    $param9 = $request->histPaysCodeNull;
                    break;
                case 'gam_transfert':
                    $param2 = $request->histPaysCodeIso;
                    $etat = 'CONFIRMEE';
                    $ref = 'AUTO';
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    $mymontant = $request->histMontantSannsFrais;
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    $param9 = $request->histPaysCodeNull;
                    break;
                default:
                    $param2 = $request->histPaysCodeIso;
                    $etat = 'atraiter';
                    $ref = rand(1,6);
                    $numclient =  $request->histNumclient;
                    $phonevendeur = $request->histPhonevendeur;
                    $mymontant =$request->histMontantSannsFrais;
                    $montantSansFrais = $request->histMontantSannsFrais;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    $param9 = $request->histPaysCodeNull;
            }

            if($request->histOperation == "gam_transfert"){
                if($numclient == $phonevendeur){
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Entrer un compte GamPay différent du votre'
                    ]);
                }
                $receiver = Customer::whereIn('phoneclient',[$numclient, '0'.$numclient])->first();
                if($receiver){
                    $montanttranfert = doubleval($receiver->solde) + doubleval($request->histMontantSannsFrais);
                    Customer::where('id',$receiver->id)->update([
                        'solde' => $montanttranfert
                    ]);
                    $this->sendNotificationInprogram($phonevendeur,$receiver->phoneclient,'gam_transfert', strval($mymontant),null,null,null);

                }else{
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Le '.$numclient.' ne possède pas de compte GamPay.'
                    ]);
                }
            }
            $montantSansFrais = strval(doubleval($montantSansFrais) - $frais);
            $historiqueTrans = new Historiquetrans([
                'operation' => $request->histOperation,
                'reference' =>strval($ref),
                'etat' => $etat,
                'numclient' =>str_replace(" ","",$numclient),
                'phonevendeur' => $phonevendeur,
                'content'=> $request->histOperateur,
                'montant' =>strval($mymontant),
                'montant_sans_frais' => $montantSansFrais,
                'frais' => strval($frais),
                'solde' => $request->histSoldeCustomer,
                'origine_operation' => 'GAB',
                'id_customer' => $request->histIdCustomer,
                'param2' =>( $request->histOperation == 'recharge_visa_uba'
                    ||  $request->histOperation == 'esim_international'||  $request->histOperation == 'sim_international'
                    ||  $request->histOperation == 'forfait_international') ? $request->histPaysCodeIso : $request->histPaysCodeNull,
                'param4' => $param4,
                'param7' => $param7,
                'param8' => $request->histToken,
                'param9' => 'GAB',
                'id_grand_parent' => $this->recupInfoCustomers( $phonevendeur, $numclient)

            ]);
            $historiqueTrans->save();

            return response()->json([
                'statut'=> true,
                'idTrans'=> $historiqueTrans->id,
                'code'=>$historiqueTrans->param4,
                'message'=>'Transaction en cours'
            ]);
        }
    }

    // recupérer les notifications
    public function mesNotifications(Request $request){
        $historiquestrans = Historiquetrans::where('phonevendeur', $request->numclient)->where('origine_operation','flutterApp')->where('etat','CONFIRMEE')->where('param1', null)->get();
        $edans = GamElectriciteHist::where('num_client', $request->numclient)->where('param4','!=', 'Notifié')->where('param10','flutterApp')->get();
        $notifications= Notif::where('receiver', $request->numclient)->where('notified', 0)->get();


        $tabMessNotifications = array();
        if(count($historiquestrans)>0 || count($edans)>0 || count($notifications)>0){
            if(count($notifications)>0){
                foreach ($notifications as $notif){
                    if( $notif['type'] == 'pass'){
                        $message = $notif['content'];
                    }else{
                        switch ($notif['operation']){
                            case 'ae':
                                $message = $notif['nom'].' souhaite que vous effectuez son '.strtolower($notif['titre']). ' de '. $notif['montant']. ' FCFA pour le compteur'. $notif['compteur'];
                                break;

                            default :
                                $message = $notif['nom'].' souhaite que vous effectuez son '.strtolower($notif['titre']). ' de '. $notif['montant']. ' FCFA pour le '. $notif['demandeur'];
                                break;
                        }
                    }


                    $notification = [
                        'type'=>  $notif['type'],
                        'titre'=>  $notif['titre'],
                        'message'=>  $message,
                    ];
                    array_push($tabMessNotifications, $notification);
                    Notif::find($notif['id'])->update([
                        'notified' =>1
                    ]);
                }
            }
            if(count($historiquestrans)>0){
                foreach ($historiquestrans as $transaction){


                    switch ($transaction['operation']){
                        case "achat_credit":
                            $titre = 'achat crédit';
                            $message= "Votre achat crédit de ".$transaction['montant']." pour le ".$transaction['numclient']. " a été réalisé avec succès." ;
                            break;
                        case "achat_forfait"||"achat_forfait_profit" ||"achat_forfait_profit_parrainage"||"achat_forfait_bonus" :
                            $titre = 'achat forfait';
                            $message= "Votre achat forfait de ".$transaction['montant']." pour le ".$transaction['numclient']. " a été réalisé avec succès." ;
                            break;
                        case "achat_unites_electrique":
                            $titre = 'achat edan';
                            $message= "Votre achat edan de ".$transaction['montant']." pour le compteur ".$transaction['numclient']. " a été réalisé avec succès." ;
                            break;
                        case "recharge_visa_uba":
                            $titre = 'recharge visa';
                            $message= "Votre achat visa de ".$transaction['montant']." pour le compteur ".$transaction['numclient']. " a été réalisé avec succès." ;
                            break;

                        case "emit_gam_transfert"||"recieve_gam_transfert":
                            $titre = 'transfert';
                            $message= "Votre transfert de ".$transaction['montant']." vers le ".$transaction['numclient']. " a été réalisé avec succès." ;

                            break;

                        case "rendu_monnaie_simple":
                            $titre = 'rendu money';
                            $message= "Votre rendu money de ".$transaction['montant']." vers le ".$transaction['numclient']. " a été réalisé avec succès." ;
                            break;
                    }
                    $notification = [
                        'type'=>  0,
                        'titre'=>  $titre,
                        'message'=>  $message,
                    ];
                    array_push($tabMessNotifications, $notification);
                    Historiquetrans::find($transaction['id'])->update([
                        'param1' =>'Notifié'
                    ]);
                }
            }
            if (count($edans)>0){
                foreach ($edans as $transactionEdan){

                    $titre = 'achat edan';
                    $message= "Votre achat edan de ".$transactionEdan['montant']." pour le compteur ".$transactionEdan['num_compteur']. " a été réalisé avec succès, vous recevrez vos codes sous peu." ;
                    $notification = [
                        'type'=>  0,
                        'titre'=>  $titre,
                        'message'=>  $message,
                    ];
                    array_push($tabMessNotifications, $notification);
                    Historiquetrans::find($transactionEdan['id'])->update([
                        'param4' =>'Notifié'
                    ]);
                }
            }
            return response()->json([
                'statut'=> true,
                'notifications'=> $tabMessNotifications
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'historique'=>$historiquestrans,
                'edan'=>$edans,
                'notification'=>$notifications,
            ]);
        }
    }


    // Enregistré le l'Id Ywomi
    public function updateIdIwomy(Request $request){
        Historiquetrans::find($request->idTrans)->update([
            'param5' =>$request->idIwomy
        ]);
        return 1;
    }

    // verifié le statut de la trasaction iwomi
    public function getStatut(){
        $tab = Array();
        $transactions = Historiquetrans::where('etat', 'en_attente')->where('param5', '!=', null)->where('param7', 'mtn')->orWhere('param7', 'orange')->orWhere('param7', 'visaIwomi')->orderBy('id', 'desc')->take(10)->get();
        if(count($transactions)>0){

            foreach($transactions as $transaction){

                if($transaction['param7'] == 'visaIwomi'){
                    $veriStatutTransaction = Http::withHeaders([
                        'Content-Type' => 'application/json',
                        'AccountKey' => 'NWUwMTZmYjgtZWYxMC00NThjLTgwYzQtNDQyZTk0NzA4ZWVlOjZlZjM5NjVmLWNjYjktNGY3MC04ODY0LTFjYTFkZThlN2M3ZA=='
                    ])->get('https://www.pay.iwomitechnologies.com:8443/iwomipay_sandbox/iwomipayStatus/'.$transaction['param5']);

                }else{
                    $token = $this->getTokenIwomi();
                    if($token !='0'){
                        if($transaction['param7'] == 'orange'){
                            $veriStatutTransaction = Http::withHeaders([
                                'Accept'=>  'application/json',
                                'Authorization'=>  'Bearer '.$token,
                                'AccountKey'=>'YjMxNzA0MTAtZjRlNS00N2YzLWE3OTItZGZmNzAzY2YyNDAwOmIwMDRjNDYyLTA1MTUtNGQ0ZC1iYmRmLWFlOTZlNTQ0ZDA0Nw=='
                            ])->get('https://www.pay.iwomitechnologies.com:8443/iwomipay_sandbox/iwomipayStatus/'.$transaction['param5']);
                        }else {
                            $veriStatutTransaction = Http::withHeaders([
                                'Accept'=>  'application/json',
                                'Authorization'=>  'Bearer '.$token,
                                'AccountKey'=>'ZTY2MTBjNjItMDk1YS00NDBiLWI5N2QtOTIzY2JlNDkxNzI5OjY1ZmJhMzNiLTVhNmUtNDI1My1iOGYzLTkzNzNmZjg2MTVlYQ=='
                            ])->get('https://www.pay.iwomitechnologies.com:8443/iwomipay_prodv1/iwomipayStatus/'.$transaction['param5']);
                        }
                    }else{
                        return [
                            'statut'=>false,
                            'message'=>'une erreur c\'est produite lors de la génération du token de sécurité',
                        ];
                    }

                }


                if($veriStatutTransaction->status() == 200 ){

                    $resut = json_decode($veriStatutTransaction->body());
                    if($resut->status == '01'){
                        if($transaction['param4']!= '+241'){
                            Historiquetrans::find($transaction['id'])->update([
                                'etat' =>'succes'
                            ]);
                        }else{
                            Historiquetrans::find($transaction['id'])->update([
                                'etat' =>'atraiter'
                            ]);
                        }

                    }else if($resut->status == '100'){
                        Historiquetrans::find($transaction['id'])->update([
                            'etat' =>'echec'
                        ]);
                    }else if($resut->status == '1000'){
                        Historiquetrans::find($transaction['id'])->update([
                            'etat' =>'en_attente'
                        ]);

                    }
                    else if($resut->status == '404 '){
                        Historiquetrans::find($transaction['id'])->update([
                            'etat' =>'introuvable'
                        ]);
                    }else{
                        Historiquetrans::find($transaction['id'])->update([
                            'etat' =>'inconnu'
                        ]);
                    }

                    $trans = [
                        "statut"=> '1',
                        "provider"=> $transaction['param7'],
                        "statutResponse"=> $veriStatutTransaction->status(),
                        "body"=>$veriStatutTransaction->body(),
                    ];

                }else{
                    $trans = [
                        "statut"=> '2',
                        "statutResponse"=> $veriStatutTransaction->status(),
                        "body"=>$veriStatutTransaction->body(),
                    ];

                }
                array_push($tab,$trans);
            }

            $reponse =$tab;

        }else{
            $reponse = [
                "statut"=> '0',
                "body"=>"aucunes transaction iwomi"
            ];
        }

        return $reponse;
    }

    // créer une notification
    public function createNotification(Request $request){

        if($request->type == "pass"){
            $clients = Customer::where('phoneclient',$request->receiver)->get();
            if(count($clients) >0){
                foreach($clients as $client){
                    $newNotification = new Notif([
                        'titre' => $request->titre,
                        'content' =>  'Votre mot de passe est '. $client['mdpclient']. ', '.$client['nom']. ' ' .$client['prenom'],
                        'icon' => $request->icon,
                        'type' => $request->type,
                        'operation' => 'recuperation',
                        'receiver' => $request->receiver,
                        'nom' =>  $client['nom']. ' ' .$client['prenom'],
                        'demandeur' =>  $request->receiver,
                        'montant' => '',
                        'pays' => $request->pays,
                        'codepays' => $request->codepays,
                        'operateur' => ' '
                    ]);
                    $newNotification->save();
                }
                return '1';

            }else{
                return '2';
            }

        }else{
            $newNotification = new Notif([
                'titre' => $request->titre,
                'content' => $request->message,
                'icon' => $request->icon,
                'type' => $request->type,
                'operation' => $request->operation,
                'receiver' => $request->receiver,
                'nom' => $request->nom,
                'demandeur' => $request->demandeur,
                'montant' => $request->montant,
                'pays' => $request->pays,
                'codepays' => $request->codepays,
                'operateur' => $request->operateur,

            ]);
            $newNotification->save();
            return '1';
        }



    }

    public function getNotification(Request $request){
        /*$notification = Notif::where('receiver', $request->receiver)->get();

        if(count($notification)>0){
            return response()->json($notification);
        }else{
            return '0';
        }*/

        $notifications = Notif::where('receiver', $request->receiver)->orderBy('id', 'desc')->take(20)->get();
        $tabNotifications = Array();
        if(count($notifications)>0){
            foreach ($notifications as $notification){
                if($notification['type'] == 'bfm'){
                    switch ($notification['operation']){
                        case 'ae':
                            $message = $notification['nom'].' souhaite que vous effectuez son '.strtolower($notification['titre']). ' de '. $notification['montant']. ' FCFA pour le compteur'. $notification['compteur'];
                            break;

                        default :
                            $message = $notification['nom'].' souhaite que vous effectuez son '.strtolower($notification['titre']). ' de '. $notification['montant']. ' FCFA pour le '. $notification['demandeur'];
                            break;
                    }
                }else{
                    $message = $notification['content'];
                }

                // construire le tableau des infos de la notification
                $maNotification = [
                    'notification'=>$notification,
                    'message'=>$message,
                ];

                // Ajouter la notification au tableau à envoyer
                array_push($tabNotifications,$maNotification );
                // mettre à jour le champ notified

                Notif::find($notification['id'])->update([
                    'notified'=> 1
                ]);

            }

            return response()->json($tabNotifications);
        }else{
            return '0';
        }

    }


    // recupérer le token d'iwomi
    public function getTokenIwomi(){
        $getToken = Http::withHeaders([
            'Content-Type'=>'application/json',
            'Authorization'=>
                'key=AAAADMhTY64:APA91bEBxcFfV3l8FpSHvEtUar_TJRHPs3AGirIeOeF044OOO5nJpOQst2H0pu-NtTBCV4leYFibV7L1TAN1ECSF1CIHZ6eXRNFGtQx_t1C_m7UDlPcD41h_YtTpTm3bbMi-zhj9xy-a'

        ])->post('https://www.pay.iwomitechnologies.com:8443/iwomipay_prodv1/authenticate',[
            "username"=> "2021GAM1005",
            "password"=>"21GAB2068689"
        ]);

        if($getToken->status() == 200 ){
            $reponse = json_decode($getToken->body());
            return $reponse->token;
        }else{
            return '0';
        }
    }


    // supprimer une notification
    public function deleteNotification(Request $request){
        $notif = Notif::find($request->id);
        $notif->delete();
    }

// quand l'utilisateur utilise son rendu QR
    public function editTrans(Request $request){
        switch ($request->operation){
            case 'recharge_visa_uba':
                $etat = 'en_attente';
                $param9 = 'customer';
                break;
            case 'rendu_monnaie_simple':
                $etat = 'atraiter';
                $param9 = 'banking';
                break;
            default:
                $etat = 'atraiter';
                $param9 = $request->histPaysCodfdee; // pour que le champ soit null
        }

        Historiquetrans::find(intval($request->idTrans))->update([
            'operation'=>$request->operation,
            'etat'=>$etat,
            'numclient'=>$request->phone,
            'content'=> $request->operateur,
            'param9'=>$param9,
            'param2'=>$request->operation1gh // pour que le champ soit null
        ]);
        return response()->json([
            'statut'=> true,
            'message'=>'Transaction encours',
        ]);
    }

    // qand l'utilisateur scan le qr code ou saisie le code
    public function getTrans(Request $request){
        $trans = Historiquetrans::where('param4',$request->idTrans)->first();
        if(!empty($trans)){
            if($trans['phonevendeur']== '0'){
                Historiquetrans::find($trans['id'])->update([
                    'phonevendeur' => $request->phonevendeur, // il met son numero comme phonevendeur de la transaction
                    'param4'=>$request->phonevendeurghd, // pour que le champ soit null
                    'id_parent'=>'qr'
                ]);

                return response()->json([
                    'statut'=> true,
                    'id'=>$trans['id'],
                    'montant'=>$trans['montant_sans_frais'],
                ]);// On lui renvoit le montrant et l"id de la transaction pour qu'il puis la modifier
            }else{
                return response()->json([
                    'statut'=> false,
                    'message'=>'Ce Qr code à dejà été utilisé',
                    'body'=>$trans
                ]);
            }

        }else{
            return response()->json([
                'statut'=> false,
                'message'=>'Transaction inexistante',
            ]);
        }

    }

    // récupérer les qr code rendu monney
    public function getTransQr(Request $request){
        if($request->type == 'client'){
            $trans = Historiquetrans::where('phonevendeur', $request->numCustomer)->where('reference', 'QR')->where('etat', 'QR')->orderBy('id', 'desc')->take(30)->get();
        }else{
            $trans = Historiquetrans::where('id_customer', $request->idCustomer)->where('reference', 'QR')->whereNotNull('etat')->orderBy('id', 'desc')->take(30)->get();
        }
        if(count($trans)>0){
            return response()->json([
                'statut'=> true,
                'body'=> $trans
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'body'=> 0
            ]);
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
                $message2= "Vous venez de recevoir un rendu monnaie de $montant FCFA sur votre compte GamPay $nom." ;
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

    public function sendNotifOperation(){
        $trans = Historiquetrans::whereIn('operation', ['recharge_visa_uba', 'achat_visa_uba'])->where('notified', 0)->where('id', '>=', 34262696)->get();
        if(count($trans)>0){
            foreach ($trans as $tran){
                $this.$this->sendNotificationInprogram($tran['phonevendeur'], $tran['numclient'], $tran['operation'], $tran['montant_san_frais'],'', $tran['content'], '');
                Historiquetrans::where('id', $tran['id'])->update([
                    'notified'=>1
                ]);
            }
        }
        $reclamations = Reclammation::where('statut', 'closed')->where('notified', 0)->where('id', '>=', 423170)->get();
        if(count($reclamations)>0){
            foreach ($reclamations as $tran){
                $message = "Votre ".$tran['objet']. " a été traité par nos services";
                $this.$this->sendNotificationInprogram($tran['phonevendeur'], $tran['numclient'], 'info', $tran['montant_san_frais'],$message, '', '');
                Reclammation::where('id', $tran['id'])->update([
                    'notified'=>1
                ]);
            }
        }
    }


    public function sendNotification(Request $request){
        $customerVendeurs = Customer::where('phoneclient',$request->phonevendeur)->get();
        $customerReceives = Customer::whereIn('phoneclient',['0'.$request->numclient,$request->numclient])->get();
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

        if($request->phonevendeur == $request->numclient || $request->phonevendeur == '0'.$request->numclient ){
            $nom = "";
            $nom2 = "";
            $nomVisa = "";
        }else{
            if($nomVendeur!=""){
                $nom = "de  $nomVendeur";
                $nomVisa = "par $nomVendeur";
            }else{
                $nom = "du ".$request->phonevendeur;
                $nomVisa = "par le $nomVendeur";
            }

            if($nomReceive!=""){
                $nom2 = " pour $nomReceive ";
            }else{
                $nom2 = " pour le $request->numclient ";
            }
        }

        $montant = $request->montant;

        switch ($request->operation){
            case "achat_credit":
                $titre = 'Achat crédit';
                $message= "Vous avez achetez $montant de crédit$nom2" ;
                $message2= "Vous avez reçu $montant FCFA de crédit $nom" ;
                break;
            case "achat_forfait" :
                $titre = 'Achat forfait';
                $message= "Vous avez achetez un forfait internet de $request->contentHist Mo$nom2" ;
                $message2= "Vous avez reçu un forfait internet de $request->contentHist Mo $nom" ;

                break;
            case "achat_unites_electrique":
                $titre = 'Achat edan';
                $message= "Vous avez achetez des unité edan de $montant FCFA pour le compteur $request->numclient" ;
                $message2= "vous avez reçu des unité edan de $montant FCFA pour le compteur $request->numclient $nom" ;

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

            case "rendu_monnaie_simple":
                $titre = 'Rendu monnaie';
                $message= "Vous avez éffectuez un rendu monnaie de $montant FCFA $nom2." ;
                $message2= "Votre avez reçu un rendu monnaie de $montant FCFA $nom" ;
                break;
            case "achat_status":
                $titre = 'Publication status';
                $message= "Le paiement de votre publication a été effectué avec succès" ;
                $message2= "Le paiement de votre publication a été effectué avec succès" ;
                break;

            case "recharge_compte_gam":
                $titre = 'Recharge GamPay';
                $message2= "Votre recharge GamPay de $montant FCFA$nom2 a été effectuée avec succès" ;
                $message= "Votre compte GamPay a été rechargé de $montant $nomVisa " ;
                break;

            case "mdp":

                $titre = 'Récupération';
                $message2= "Votre mot de passe est $pass , $nomReceive" ;
                $message= "Votre mot de passe est $pass , $nomReceive" ;
                break;

            case "info":

                $titre = 'GamPay';
                $message= $request->message ;
                $message2=  $request->message ;
                break;

            case "incident":
                $titre = 'Incident GamPay';
                $message=  $request->message ;
                $message2=  $request->message ;
                break;

        }

        if($request->phonevendeur == $request->numclient || $request->phonevendeur == '0'.$request->numclient || $request->operation == 'incident' ){
            $notif2= $this->notifFirebase($tokenVendeur,$titre, $message, $appVendeur);
            $this->myNotification($titre, $message,$request->operation, $request->phonevendeur,$nomReceive, $request->phonevendeur,$montant, "" , "", ""  );
            $rep = $notif2;
        }else{
            $notifrecieve = $this->notifFirebase($tokenReceive,$titre, $message2, $appReceive);
            $notif2= $this->notifFirebase($tokenVendeur,$titre, $message, $appVendeur);
            $this->myNotification($titre, $message2,$request->operation, $request->numclient,$nomReceive, $request->phonevendeur,$montant, "" , "", ""  );
            $this->myNotification($titre, $message,$request->operation, $request->phonevendeur,$nomReceive, $request->numclient,$montant, "" , "", ""  );
            if(intval($notifrecieve) == 1 || intval($notif2) == 1){
                $rep = 1;
            }else{
                $rep = 0;
            }
        }

        return $rep;

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

    // supprimer logiquements les notifications
    public function cleanNotification(){
        $lastThreeDays = date('Y-m-d H:i:s', strtotime('-3 day'));
        $notifs = Notif::where('created_at', '<=', $lastThreeDays)->get();
        if(count($notifs)){
            foreach ($notifs as $notif){
                Notif::find($notif->id)->delete();
            }
            return response()->json([
                'statut'=> true,
                'body'=>$notifs,
                'dateSupp'=>$lastThreeDays
            ]);
        }else{
            return "Pas de vieilles notification";
        }
    }


    // supprimer definitivement les notifications
    public function cleanForceNotification(){
        $notifs = Notif::where('created_at', '!=', null)->get();
        if(count($notifs)){
            foreach ($notifs as $notif){
                Notif::find($notif->id)->forceDelete();
            }
            return response()->json([
                'statut'=> true,
                'body'=>$notifs,
            ]);
        }else{
            return "Pas de vieilles notification";
        }
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

    public function visaOrabank(Request $request){
        $response = Http::withHeaders([
            'accept'=>'application/vnd.ni-identity.v1+json',
            'Content-Type'=>'application/vnd.ni-identity.v1+json',
            'Authorization'=> 'Basic MjdlMWNjNDYtNTNjZi00ZjYwLWFiODEtOGVhYzQyNTNiZTI2OjRmMWM0N2VjLWQyN2YtNDhmMC1iMDRlLTJiNzQ4NDgzNWIyYQ=='
        ])->post('https://api-gateway.sandbox.orabankga.ngenius-payments.com/identity/auth/access-token');

        $response->body();
        return $response;
    }


    public function callBackAM(Request $request){
        $data = $request->json;
        $order = new Orders([
            'reference'=> $data['transaction']['airtel_money_id'],
            'transId'=> $data['transaction']['id'],
            'status'=> $data['transaction']['status_code'],
            'message'=> $data['transaction']['message'],
            'partenaire'=> 'airtelMoney',
            'param1'=> $data['hash'],
        ]);
        $order->save();
        return 'ok';
    }

    public function relanceTrans(Request $request){
        $uri = $request->path();
        return view('relanceTrans', compact('uri'));
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
            Http::get('https://gampay.app/gamclients/public/api/getAlerteTransactions');
            Http::get('https://gampay.app/gamclients/public/api/sendTemplateAfterRm'); 
            //Http::get('https://gampay.org/gamclients/public/api/lunchDiffusion'); // lancer les diffusions
            //Http::get('https://gampay.app/gamclients/public/api/notifVisaAndReclam'); // notifications visa et reclamation
            Http::get('https://gampay.app/gamclients/public/api/getAlerteRechargeVisa');
            //Http::get('https://gampay.app/gamclients/public/api/confrimTransTraitStatus');
            //Http::get('https://gampay.app/gamclients/public/api/sendMessageNewCustomer'); //whatsApp new customer
            http::get('https://gampay.app/gamclients/public/api/countScoreCustomer');
            
            Http::get('https://gampay.org/gamclients/public/api/validePaymentAction');
            Http::get('https://gampay.app/gamclients/public/api/commandeCard'); // Commande carte
            //Http::get('https://gampay.org/gamclients/public/api/sendMessageAirtelCustomer?typeCustomer=ussd');
            /*
            Http::get('https://gampay.app/gamclients/public/api/sendNotifForMoovPayment');//payment moov money
            Http::get('https://gampay.app/gamclients/public/api/distributionUssd');// distribution ussd
            Http::get('https://gampay.app/gamclients/public/api/confirmeRecupTransScyd');
            Http::get('https://gampay.app/gamclients/public/api/correctEncodeCode');
            Http::get('https://gampay.app/gamclients/public/api/valideTransApisPartenaire'); // Validation transactions apis partenaires
            Http::get('https://gampay.app/gamclients/public/api/verifTransInternational'); // notifications visa et reclamation

            //Http::get('https://gampay.app/gamclients/public/api/getCommisions'); // calcul commission
            
            //Http::get('https://gampay.app/gamclients/public/api/getTransCaisse'); // Get stats Caisse prix Import
            //Http::get('https://gampay.app/gamclients/public/api/getCustomerPrixImportSecondChallenge'); // Get customers Caisse prix Import
            //Http::get('https://gampay.app/gamclients/public/api/valideCustomerPrixImportSecondChallenge'); // valide customers Caisse prix Import
            Http::get('https://gampay.app/gamclients/public/api/paiement');// valide achat status
            //Http::get('https://gampay.app/gamclients/public/api/disableOrabank?etat=indisponible'); //disable orabank card
            Http::get('https://gampay.app/gamclients/public/api/sendMessageNewCustomer'); //whatsApp new customer

          */
            Http::get('https://gampay.app/gamclients/public/api/verifTransInternational'); // Customer actif ussd

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

    public function orderOrabankTest(Request $request){
        $data = [
            "action"=> "PURCHASE",
            "amount" => [
                "currencyCode" => "XAF",
                "value" => strval($request->montant)
            ]
        ];

        if(!empty($request->token)){
            $token = $request->token;

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

    // Transaction visa orabank
    public function transByVisaOrabank(Request $request){

        $listeNoire = ['077641174', '066202122', '074405969', '077641174', '066666666', '066303132', '066323334', '066333435', '077251198', '076467594', '074629390', '076346949'];

        if(in_array($request->histPhonevendeur, $listeNoire) || $request->histPhonevendeur == '077251198' ){
            return response()->json([
                'statut'=> false,
                'message'=> 'Service indisponible, contacter le service client'
            ]);
        }
        
        if($request->histOperation == 'achat_wifi' || $request->histOperation == 'transfert_ria'){
            return response()->json([
                'statut'=> false,
                'message'=> 'Opération indisponible pour le moment'
            ]);
        }

        // indiquer que le client a utilisé visa
        Customer::where('phoneclient', $request->histPhonevendeur)->update([
            'grade' => 'visa'
        ]);

        // check plafond Visa
        $now = date('Y-m-d');
        $plafondTransfertVisa = Historiquetrans::where('phonevendeur', $request->histPhonevendeur)->where('operation', 'transfert_visa')->whereNotIn('etat', ['attend', 'null'])->where('created_at', 'like', ''.$now.'%')->sum('montant_sans_frais');

        if($plafondTransfertVisa >= 200000){
            $this->sendNotificationInprogram($request->histPhonevendeur, $request->histPhonevendeur, 'info', '',"Cher client, votre quota de transfert VISA journalier est atteint", '', '');
            return response()->json([
                'statut'=> false,
                'message'=> 'Plafond journalier atteint'
            ]);
        }

        $now = date("Y-m-d");
        $agents = Customer::where('username', 'agent_gam_international')->get();// recupère la liste des agents
        $order = $this->orderOrabank(strval($request->histMontantfrais));
        if($request->histOperation == 'achat_edan'){

            $uniqid_trans = uniqid();
            if($order['status'] != 0){

                    $ticketEdan = new GamElectriciteHist([
                        'num_client'=>$request->histPhonevendeur,
                        'nom_compteur'=>$request->histNumclient,
                        'num_compteur'=>$request->histOperateur,
                        'montant_transaction'=>$request->histMontantSannsFrais,
                        'etat'=>'attend',
                        'param8' => $order['body']['_embedded']['payment'][0]['orderReference'],
                        'param1' => $request->origine,
                        'special_key' => $uniqid_trans,
                        'param5' => empty($request->device)? $request->device : json_encode($request->device),
                        'param2'=> 'visaOrabank'
                    ]);
                    $ticketEdan->save();

                    if (!empty($ticketEdan->id)){
                        if($agents){
                            foreach ($agents as $agent){
                                $message = "$request->histOperation de $request->histMontantSannsFrais pour le $request->histPhonevendeur initié via VISA";
                                $this->sendNotificationInprogram($agent['phoneclient'], $agent['phoneclient'], 'info', '',$message, '', '');
                            }
                        }

                        $this->storePriority($request->histPhonevendeur, $request->histOperation);
                        return response()->json([
                            'statut'=> true,
                            'linkBuy'=> $order['body']['_links']['payment']['href'],
                        ]);
                    }else{
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Service indisponible pour le moment'
                        ]);
                    }
                
            }else{
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Service indisponible pour le moment order',
                    'body'=> $order['body']
                ]);
            }

        }else if($request->histOperation == 'paiement_partenaire' || $request->histOperation == 'paiement'){

            if($order['status'] != 0){
                $veriTrans = Historiquetrans::where('id', intval($request->histOperateur))
                    ->first();
                Historiquetrans::where('id',$veriTrans->id)->update([
                    'param8' => $order['body']['_embedded']['payment'][0]['orderReference'],
                ]);

                return response()->json([
                    'statut'=> true,
                    'linkBuy'=> $order['body']['_links']['payment']['href']
                ]);

            }else{
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Service indisponible pour le moment order',
                    'body'=> $order['body']
                ]);
            }
        }else{
            switch ($request->histOperation){
                case 'recharge_visa_uba':
                    $etat = 'attend';
                    $param7 = $request->histPaiementYT;
                    $param4 = $request->histPaysCodeJT;
                    $param9 = 'customer';
                    break;
                case 'achat_visa_uba':
                    $etat = 'attend';
                    $param7 = $request->histPaiementJT;
                    $param4 = $request->histPaysCodeJT;
                    $param9 = 'customer';
                    break;
                case 'rendu_monnaie_simple':
                case 'transfert_mobile':
                case 'transfert_visa':
                    $etat = 'attend';
                    $param7 = $request->histPaiementJT;
                    $param4 = $request->histPaysCodeJT;
                    $param9 = 'banking';
                    break;
                default:
                    $etat = $request->histEtat;
                    $param7 = $request->histPaiement;
                    $param4 = $request->histPaysCode;
                    $param9 = $request->histPaysCodeJT;
            }

            if($order['status'] != 0){
                    $historiqueTrans = new Historiquetrans([
                        'operation' =>$request->histOperateur == 'GAM' || $request->histOperateur == 'gam'?'gam_transfert': $request->histOperation,
                        'reference' => 'AUTO',
                        'etat' => $etat,
                        'numclient' =>str_replace(" ","",$request->histNumclient),
                        'phonevendeur' => $request->histPhonevendeur,
                        'content'=> $request->histOperateur,
                        'montant' =>strval($request->histMontantfrais),
                        'montant_sans_frais' => $request->histMontantSannsFrais,
                        'frais' =>strval($request->histFrais),
                        'solde' => $request->histSoldeCustomer,
                        'origine_operation' => 'FlutterApp2.3.0',
                        'id_customer' => $request->histIdCustomer,
                        //'tentative_effectue' => empty($request->device)? '0' : json_encode($request->device),
                        'param2' => ( $request->histOperation == 'recharge_visa_uba'
                            ||  $request->histOperation == 'esim_international'||  $request->histOperation == 'sim_international'
                            ||  $request->histOperation == 'forfait_international') ? $request->histPaysCodeIso : $request->histPaysCodeNull,
                        'param4' => $param4,
                        'param7' => $param7,
                        'param8' => $order['body']['_embedded']['payment'][0]['orderReference'],
                        'param9' => $param9,
                        'id_grand_parent' => $this->recupInfoCustomers($request->histPhonevendeur, $request->histNumclient),
                    ]);
                    $historiqueTrans->save();
                    if (!empty($historiqueTrans->id)){
                        if($agents){
                            foreach ($agents as $agent){
                                $message = "$request->histOperation de $request->histMontantSannsFrais pour le $request->histPhonevendeur initié via VISA";
                                $this->sendNotificationInprogram($agent['phoneclient'], $agent['phoneclient'], 'info', '',$message, '', '');
                            }
                        }

                        $this->storePriority($request->histPhonevendeur, $request->histOperation);
                        return response()->json([
                            'statut'=> true,
                            'linkBuy'=> $order['body']['_links']['payment']['href']
                        ]);
                    }else{
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Service indisponible pour le moment'
                        ]);
                    }
                

            }else{
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Service indisponible pour le moment order',
                    'body'=> $order['body']
                ]);
            }
        }

    }


    // relancer les transaction des partenaires
    public function restartTrans(Request $request){
        if(empty($request->content)){
            $mycontent = $request->contente;
        }else{
            $mycontent = $request->content;
        }
        $now = date('Y-m-d h:i:s');

        switch ($request->operation){
            case 'recharge_visa_uba':
                $etat = 'en_attente';
                $content = $mycontent;
                break;
            case 'achat_credit':
                $result = substr($request->num, 0, 2);
                if($result == '06' || $result == '66'||$result == '62'|| $result == '65'){
                    $content = 'LIBERTIS';
                }else if ($result == '07' || $result == '76'||$result == '74'|| $result == '77'){
                    $content = 'AIRTEL_GA';
                }else{
                    $content = 'International';
                }
                $etat = 'atraiter';
                break;
            default:
                $etat = 'atraiter';
                $content = $mycontent;
        }

        $trans = Historiquetrans::where('id', intval($request->id))->first();

        $etatRelance = ["CONFIRMEE","confirme","attend", "en_agence_attend"];


        if(in_array($trans->etat, $etatRelance)){
            return response()->json([
                'statut'=> true,
                'message'=> 'Vous ne pouvez pas relancer cette transaction'
            ]);
        }else{
            Historiquetrans::where('id',$request->id)->update([
                'etat'=>$etat,
                'content'=> $content,
                'operation'=> $request->operation,
                'timestamps'=> strval(date('Y_m_d')),
                'param2'=>$request->operation1Null,
                'param1'=>$request->operation1Null,
                'created_at' => $now
            ]);

            return response()->json([
                'statut'=> true,
                'message'=> 'Transaction relancée'
            ]);
        }
    }

    // annuler les transaction des partenaires
    public function annnuleTrans(Request $request){

        Historiquetrans::where('id',$request->id)->update([
            'etat'=>'attend',
            'reference'=> rand(1,6),
            'param2'=>$request->operation1Null,
            'param1'=>$request->operation1Null,
        ]);

        return response()->json([
            'statut'=> true,
            'message'=> 'Transaction annulée'
        ]);

    }

    public function createTrans($operation, $ref, $etat,$numclient, $phonevendeur,
                                $content,$montantPlusFrais, $montant,
                                $frais, $solde,$origine, $idCustomer, $pays)
    {
        $historiqueTrans = new Historiquetrans([
            'operation' => $operation,
            'reference' => $ref,
            'etat' => $etat,
            'numclient' => $numclient,
            'phonevendeur' => $phonevendeur,
            'content'=> $content,
            'montant' =>strval($montantPlusFrais),
            'montant_sans_frais' => strval($montant),
            'frais' =>strval($frais),
            'solde' => $solde,
            'origine_operation' => $origine,
            'id_customer' => $idCustomer,
            'param2' => $pays,
            'id_grand_parent' => $origine == 'BackOffice'? NULL : $this->recupInfoCustomers($phonevendeur, $numclient)
        ]);
        $historiqueTrans->save();
        return 1;

    }

    public function sendForfait(Request $request){
        if($request->produit == 'bancaire' || $request->produit =='international'){
            $forfait = '200';
            $montant = '800';
        }elseif($request->produit == 'Ussd'){
            // $trans = Historiquetrans::where('reference', 'bonus')->where('numclient', $request->num)->where('created_at', '>=', '2023-10-23 00:00:00')->get();
            // if(!empty($trans)){
            //     return 5;
            // }
            $forfait = '200';
            $montant = '800';

        }else{
            $forfait = '200';
            $montant = '800';
        }

        $sendForfait = $this->createTrans('achat_forfait', 'bonus', 'atraiter',$request->num, $request->num, $forfait, $montant, $montant, '0', '', 'BackOffice', $request->id, '');
        if($sendForfait == 1 && $request->produit != 'Ussd'){
            $client = Customer::where('id', intval($request->id))->first();
            if($client){
                if(intval($client['param6']) < 1000 && $forfait != '1000' ){
                    $updateForfait = intval($client['param6']) + intval($forfait);
                }else{
                    $updateForfait = 1000;
                }
                Customer::find(intval($client['id']))->update(['param6'=> strval($updateForfait)]);
            }
            return 1;

        }elseif($sendForfait == 1 && $request->produit == 'Ussd'){
            $whatsapp = "241" .strval(substr($request->num, 1));
            return "https://api.whatsapp.com/send?phone=$whatsapp&text=  Félicitations! Vous venez de recevoir 200Mo de GamGabon. Télécharger l'application GamPay afin de découvrir toutes nos offres. https://my.gampay.app/. Nous restons à votre disposition pour un éventuel accompagnement.";
        }else{
            return 0;
        }
    }


    public function sendForfaitCadeau(Request $request){
        $forfait = '200';
        $montant = '800';
        $whatsapp = "241" .strval(substr($request->numclient, 1));
        $parrain =  Customer::where('phoneclient',$request->phoneclient)->first();
        $sendForfait = $this->createTrans('achat_forfait', 'bonus', 'atraiter',$request->numclient, $request->phoneclient, $forfait, $montant, $montant, '0', '', 'BackOffice', $request->id, strval($request->agent));
        if($parrain->score <= 5){
            $this->createTrans('achat_forfait', 'bonus', 'atraiter',$request->phoneclient, $request->phoneclient, '100', '400', '400', '0', '', 'BackOffice', $request->id, strval($request->agent));
        }
        if($sendForfait == 1){
            $customerverif = Customer::where('phoneclient', $request->numclient)->first();
            if($customerverif){
                if(!empty($customerverif->whatsapp)){
                    $whatsapp = $customerverif->whatsapp;
                }
            }else{
                $customer = new Customer([
                    'nom'=> 'client',
                    'prenom'=> 'GAM',
                    'pays'=> '241',
                    'phoneclient'=> $request->numclient,
                    'mdpclient'=> "1234",
                    'phoneparent'=> $parrain->phoneclient,
                    'phonegrandparent'=> $parrain->phoneparent,
                    'option1'=> $parrain->option1,
                    'satus'=>1
                ]);
                $customer->save();
                Http::get('http://gampay.app/gamclients/public/api/recupIdCustomer');
            }

            return 1;

        }else{
            return 0;
        }
    }

    public function rembourser(Request $request){
        $trans = Historiquetrans::where('id', $request->id)->first();
        if(!empty($trans)){
            $customer = Customer::where('phoneclient', $trans->phonevendeur)->first();
            $remboursement = $this->createTrans('recharge_compte_gam', '$request->id', 'CONFIRMEE',$trans->phonevendeur, $trans->phonevendeur, 'recharge_compte_gam', $trans->id_grand_parent, $trans->id_grand_parent, '0', $customer->solde, 'BackOffice', $trans->id_customer, '');
            if($remboursement == 1){
                $newSolde = doubleval($trans->id_grand_parent) + doubleval($customer->solde);
                $customer= $customer->update([
                    "solde"=> $newSolde,
                ]);
                if($customer){
                    $this->sendNotificationInprogram($trans->phonevendeur,$trans->phonevendeur,'recharge_compte_gam', strval($trans->id_grand_parent),null,null, null  );
                    $trans->update([
                        "id_grand_parent"=> 'OK',
                    ]);
                    return 1;
                }else{
                    return 0;
                }
            }else{
                return 0;
            }
        }
    }

    public function rembourserClientVisa(Request $request){
        $trans = Historiquetrans::where('content', $request->id)->where('id', $request->id_trans)->where('code_validation','no')->first();
        if(!empty($trans)){
            $customer = Customer::where('id', $trans->id_customer)->first();
            if(!empty($customer)){
                $newSolde = doubleval($trans->montant) + doubleval($customer->solde);
                $remboursement = $this->createTrans('recharge_compte_gam', $request->id_trans, 'CONFIRMEE',$trans->phonevendeur, $trans->phonevendeur, 'recharge_compte_gam', $trans->montant, $trans->montant, '0', $newSolde, 'BackOffice', $trans->id_customer, '');
                if($remboursement == 1){
                    $customer= $customer->update([
                        "solde"=> $newSolde,
                    ]);
                    if($customer){
                        $trans->update([
                            'etat'=>'rembourse'
                        ]);
                        $this->sendNotificationInprogram($trans->phonevendeur,$trans->phonevendeur,'recharge_compte_gam', strval($trans->montant),null,null, null  );
                        return 1;
                    }else{
                        return 0;
                    }
                }else{
                    return 0;
                }
            }
        }else{
            return 4;
        }
    }


    public function recapTrans(Request $request){
        $now = new \DateTime();
        $myDate = strval($now->format('Y-m'));



        if(!empty($request->phone)){
            $partenaire = Customer::where('phoneclient', $request->phone)->first();
        }else{
            $id=substr($request->rapport_ref,5);
            $partenaire = Customer::where('id', $id)->first();
        }

        $phone = $partenaire->phoneclient;
        $transOut = Historiquetrans::where('phonevendeur', $phone)->whereNotIn('etat', ['attend'])->where('created_at', 'like', ''.$myDate.'%')->get();
        $montanTransOut = Historiquetrans::where('phonevendeur',$phone)->whereNotIn('etat', ['attend'])->where('created_at', 'like',''.$myDate.'%' )->sum('montant_sans_frais');
        $transIn= Historiquetrans::where('numclient', $phone)->whereNotIn('etat', ['attend'])->where('created_at', 'like', ''.$myDate.'%')->get();
        $montanTransIn = Historiquetrans::where('numclient',$phone)->whereNotIn('etat', ['attend'])->where('created_at', 'like',''.$myDate.'%' )->sum('montant_sans_frais');
        $all = Historiquetrans::where(function($q) use($phone) {$q->where('numclient', $phone)->orWhere('phonevendeur', $phone);})->whereNotIn('etat', ['attend'])->where('created_at', 'like', ''.$myDate.'%')->get();
        $tab = [
            '$transOut'=>$transOut,
            '$montanTransOut'=>$montanTransOut,
            '$transIn'=>$transIn,
            '$montanTransInt'=>$montanTransIn,
        ];

        setlocale(LC_TIME, "fr_FR");
        $periode = strval(strftime("%B %Y", strtotime($myDate)));

        return view('recapTrans', compact(['phone', 'transOut', 'montanTransOut', 'transIn', 'montanTransIn', 'all', 'partenaire', 'periode']));
    }


    public function notifVisaAndReclam(){
        Http::get('https://gampay.app/gamclients/public/api/commandeCard');
        Http::get('https://gampay.app/gamclients/public/api/assistCustomerChallenge');
        $visa = Historiquetrans::where('operation', 'recharge_visa_uba')->whereIn('etat',['confirme', 'CONFIRMEE'])->where('param1', null)->where('id', '>', 34358562)->take(20)->get();
        $reclam = Reclammation::where('statut', 'closed')->whereNull('etiquete')->where('id', 435928)->get();

        if(count($visa)>0){
            foreach ($visa as $trans){
                $this->sendNotificationInprogram($trans['phonevendeur'],$trans['numclient'],$trans['operation'], strval($trans['montant_sans_frais']),null,null, null  );
                $customer = Customer::where('phoneclient', $trans['phonevendeur'])->first();
                $messageclientWhatsApp ="Cher client, votre recharge VISA de ".$trans['montant_sans_frais']."FCFA a été effectuée avec succès. Consultez l'historique du menu *visa* de votre application *GamPay* pour plus de détails.

Suivez-nous sur notre *chaîne WhatsApp* https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c";
                http::get("https://gampay.app/gamclients/public/api/sendWhatsAppMessageGet?service=international&message=$messageclientWhatsApp&pays=$customer->pays&phone=$customer->whatsapp&service=satisfaction");
                Historiquetrans::where('id', $trans['id'])->update([
                    'param1'=>'notified',
                    'param5' => NULL
                ]);
            }
        }

        if(count($reclam)>0){
            foreach ($reclam as $trans){
                $message = "Votre ". strtolower($trans['objet']). " a été traitée par nos services" ;
                $this->sendNotificationInprogram($trans['phoneclient'],$trans['phoneclient'],'info', '',$message,null, null);
                Reclammation::where('id', $trans['id'])->update([
                    'etiquete'=>'notified'
                ]);
            }
        }

        return ['trans'=> $visa, 'Reclamation'=> $reclam];
    }


    // datas internationals
    public function verifTransInternational(){
        $trans = Historiquetrans::whereIn('operation', ['forfait_international', 'sim_international', 'esim_international'])->whereNotIn('etat',['attend', 'CONFIRMEE'])->whereNull('param9')->orderBy('id', 'desc')->take(10)->get();
        $message = $trans;
        if($trans){
            foreach ($trans as $tran){

                switch ($tran['operation']){

                    case 'forfait_international':

                        $forfait = $this->simRechargeData($tran['numclient'], $tran['param2'], 1);
                        if($forfait['status'] == 1){
                            Historiquetrans::where('id', $tran['id'])->update([
                                'param9'=>'recharged',
                                'etat'=>'CONFIRMEE'
                            ]);
                            $this->storePriority($tran['phonevendeur'], $tran['operation']);
                        }else{
                            $message=  $forfait['body'];
                        }

                        break;

                    case 'sim_international':
                        if(empty($tran['param3'])){ // s'il n'y pas de sim liée
                            $vendeur = Customer::where('phoneclient', $tran['phonevendeur'])->first();
                            // attribution du coupon
                            if(empty($tran['param1']) && $vendeur->type != 'travel_agency'){
                                $coupon = Coupon::whereNull('transaction')->where('purchase_amount','5000')->first();
                                if($coupon){
                                    Historiquetrans::where('id',$tran['id'])->update([
                                        'param1' => $coupon->coupon,
                                    ]);

                                    Coupon::where('id',$coupon->id)->update([
                                        'transaction' => $tran['id'],
                                    ]);
                                }
                            }
                        }else{
                            $forfait = $this->simRechargeData($tran['param3'], $tran['param2'], 1);
                            if($forfait['status'] == 1){
                                Historiquetrans::where('id', $tran['id'])->update([
                                    'param9'=>'recharged',
                                    'etat'=>'CONFIRMEE'
                                ]);
                                $this->storePriority($tran['phonevendeur'], $tran['operation']);
                            }else{
                                $message= $forfait;
                            }
                        }
                        break;

                    case 'esim_international':
                        $sim = SimInternal::where('type', 'v')->whereNull('customerId')->whereNull('CustomerNum')->first();
                        if(!empty($sim)){
                            $forfait = $this->simRechargeData($sim->msisdn, $tran['param2'], 1);
                            if($forfait['status'] == 1){

                                $mySim = SimInternal::where('customerNum', $tran['numclient'])->get();
                                $customer = Customer::where('phoneclient', $tran['numclient'])->first();
                                if(count($mySim)>0){
                                    SimInternal::where('msisdn', $sim->msisdn)->update([
                                        'customerId'=> $customer->id,
                                        'CustomerNum'=>$customer->phoneclient,
                                        'param1' => 'disable'
                                    ]);
                                }else{
                                    SimInternal::where('msisdn', $sim->msisdn)->update([
                                        'customerId'=> $customer->id,
                                        'CustomerNum'=>$customer->phoneclient,
                                        'param1' => 'enable'
                                    ]);
                                }

                                Historiquetrans::where('id', $tran['id'])->update([
                                    'param9'=>'recharged',
                                    'param3'=>'http://gampay.org/files/QR_code/'.$sim->qr_code,
                                    'etat'=>'CONFIRMEE'
                                ]);
                                $this->storePriority($tran['phonevendeur'], $tran['operation']);
                            }else{
                                $message= $forfait;
                            }

                        }else{

                            $message= 'Plus de e-sims disponibles';

                        }

                        break;
                }
            }
            return $message;
        }else{
            return response()->json([
                'statut'=> false,
                'message'=> 'pas de transaction'
            ]);
        }

    }


    public function esimAccessToken(){
        $data = [
            "grant_type"=> "client_credentials",
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.transatel.com/authentication/api/token");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded","Authorization: Basic TTJNQV9XV19UU0xfR0FNR0FCT046bFA3RnBzeWtLNnRJRExbeHpFYU5XUllBcU1DclhTXUg="));
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
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
                return ['status'=>0, 'body'=>$result];
            }
        }
        // Close handle
        curl_close($ch);
    }

    public function esimInventor(Request $request){

        $numero = $request ->numero;
        $token = $this->esimAccessToken();
        if($token['status'] != 0){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.transatel.com/ocs/inventory/api/subscriptions/products?msisdn=".$numero);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer ".$token['body']));
            //curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $result = curl_exec($ch);
            // Check HTTP status code
            if (!curl_errno($ch)) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ( $http_code == 200 ||  $http_code == 201) {
                    $response = json_decode($result, true);
                    $actifs = [];
                    foreach ($response['productSubscriptions'] as $forfait){
                        if($forfait['status'] == 'active'){
                            array_push($actifs, $forfait);
                        }
                    }
                    if(count($actifs) >0 ){
                        return response()->json(['status'=>1000, 'body'=>$actifs]);
                    }else{
                        return response()->json(['status'=>1, 'body'=>$response]);
                    }
                }
                else{
                    return response()->json(['status'=>0, 'body'=>$result]);
                }
            }else{
                return response()->json(['status'=>0, 'body' =>$ch ]);
            }
            // Close handle
            curl_close($ch);
        }else{
            return response()->json(['status'=>0, 'body'=>$token ]);
        }

    }

    public function simRechargeData($numero, $forfait, $type){
        if($type = 0){
            $orderType = "preload";
        }else{
            $orderType = "subscribe";
        }
        $data = [
            "bind" => [
                "msisdn" => $numero
            ],
            "product" => [
                "productId" => $forfait
            ],
            "payment" => [
                "provider" => "customer"
            ],
            "source"=> "api",
            "orderType"=> $orderType,
            "mvnoRef"=> "M2MA_WW_TSL_GAMGABON"
        ];
        $token = $this->esimAccessToken();
        if($token['status'] != 0){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.transatel.com/ocs/subscriptions/api/orders/products");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer ".$token['body']));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $result = curl_exec($ch);

            // Check HTTP status code
            if (!curl_errno($ch)) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ( $http_code == 200 ||  $http_code == 201) {

                    $response = json_decode($result, true);
                    if($response['status'] == 'done'){
                        return ['status'=>1, 'body'=>$response] ;
                    }else{
                        return ['status'=>0, 'body'=>$response];
                    }
                }else{
                    return ['status'=>$result, 'body'=>$result, 'bad order response'=> $http_code ];
                }
            }else{
                return ['status'=>$result, 'body'=>$result, 'bad order'=>'token :'.$token['body']];
            }
            // Close handle
            curl_close($ch);
        }else{
            return ['status'=>$result, 'body'=>$token, 'bad'=>'token :'.$token['body']];
        }

    }

    //**************************** App back function ********************************
    public function getDataSupervision(Request $request){

        $hier = date('Y-m-d', strtotime('-1 day'));
        $yesterday = $hier. ' 00:00:00';

        switch ($request->service){
            case 'all':
                // les transaction du service international paiement via visa
                $paiementVisa = DB::select("select * from historiquetrans h where LENGTH(h.param8)=36 and h.created_at >= '2023-03-09 00:00:00' ORDER BY h.id DESC limit 50");
                $transByVisa= $this->detailsTransData($paiementVisa);

                //Data internationale
                $dataInternational = Historiquetrans::whereNotIn('etat',['attend'])->whereIn('operation',[ 'esim_international', 'sim_international', 'forfait_international'])->orderBy('id', 'desc')->take(50)->get();
                $transData = $this->detailsTransData($dataInternational);

                //Service banking
                $agenceSigma = Historiquetrans::where('operation','like', 'recharge_visa_%')->where('created_at', '>', '2023-03-09 00:00:00')->orderBy('id', 'desc')->take(70)->get();
                $transSigma = $this->detailsTransData($agenceSigma);

                //cammande Visa
                $commandeCarte = Carte::whereNotIn('treatment', ['confirme','verification'])->orderBy('id', 'desc')->take(50)->get();
                $commandevalide = 0;
                $commandeenattente = 0;
                if(count($commandeCarte)>0){
                    foreach ($commandeCarte as $commandeCartes)
                    {
                        if($commandeCartes->treatment=='attribution') {
                            $commandevalide =$commandevalide+1;
                        }else{
                            $commandeenattente = $commandeenattente+1;
                        }
                    }
                }
                $transcommande = [
                    'validates' => $commandevalide,
                    'loanding' => $commandeenattente,
                    'errors' => 0,
                ];

                //Service Satisfaction
                $satisfaction = Historiquetrans::whereIn('operation',[ 'achat_credit', 'achat_forfait'])->orderBy('id', 'desc')->take(50)->get();
                $transSatisfaction = $this->detailsTransData($satisfaction);

                //Transfert ria
                $ria = Historiquetrans::where('operation','transfert_ria')->orderBy('id', 'desc')->take(50)->get();
                $transRia = $this->detailsTransData($ria);

                //Réclamtion

                $reclamation = Reclammation::where('objet', 'not like','Reclammation sur%')->orderBy('id', 'desc')->take(50)->get();
                $reclmavalide = 0;
                $reclamattente = 0;
                $reclaerreur = 0;

                if(count($reclamation)>0)
                {
                    foreach ($reclamation as $reclamations){
                        if($reclamations->statut == 'closed')
                        {
                            $reclmavalide = $reclmavalide+1;
                        }elseif ($reclamations->statut == 'treatment'){
                            $reclamattente = $reclamattente+1;
                        }else{
                            $reclaerreur = $reclaerreur+1;
                        }
                    }
                }
                $transReclam = [
                    'validates' => $reclmavalide,
                    'loanding' => $reclamattente,
                    'errors' => $reclaerreur,
                ];

                //support IT
                $reclamationsIT = Reclammation::where('objet', 'like','Support%')->orderBy('id', 'desc')->take(50)->get();
                $reclmavalides = 0;
                $reclamattentes = 0;
                $reclaerreurs = 0;

                if(count($reclamationsIT)>0)
                {
                    foreach ($reclamationsIT as $reclamationsITs){
                        if($reclamationsITs->statut == 'closed')
                        {
                            $reclmavalides = $reclmavalides+1;
                        }elseif ($reclamationsITs->statut == 'treatment'){
                            $reclamattentes = $reclamattentes+1;
                        }else{
                            $reclaerreurs = $reclaerreurs+1;
                        }
                    }
                }
                $transReclamIT = [
                    'validates' => $reclmavalides,
                    'loanding' => $reclamattentes,
                    'errors' => $reclaerreurs,
                ];

                //Electricite
                $electricite = DB::select("SELECT * from gam_electricite_hist geh  WHERE  geh.etat <> 'attend' ORDER BY geh.id DESC limit 50");
                $selectriciteerreurs = 0;
                $selectriciteattentes = 0;
                $selectriciteavalides = 0;
                if(count($electricite)>0){
                    foreach ($electricite as $strans){
                        if($strans->etat == 'CONFIRMEE')
                        {
                            $selectricitevalides = $selectriciteavalides+1;
                        }elseif ($strans->etat == 'attente'){
                            $selectriciteattentes = $selectriciteattentes+1;
                        }else{
                            $selectriciteerreurs = $selectriciteerreurs+1;
                        }
                    }
                }
                $transelectricite = [
                    'validates' => $selectricitevalides,
                    'loanding' => $selectriciteattentes,
                    'errors' => $selectriciteerreurs,
                ];

                //Rendu monnaie
                $rendumonnaie = DB::select("select * from historiquetrans h where (LENGTH(h.param8)<20 or h.param8 IS null) and (h.operation ='rendu_monnaie_simple' or h.reference = 'rm_3.0') and h.created_at > '2023-03-09 00:00:00' order by h.id desc limit 70");

                $transRenduMonnaie = $this->detailsTransData($rendumonnaie);

                return response()->json([
                    'statut'=> true,
                    'body'=> [
                        'paiementVisa' =>$transByVisa,
                        'renduMonanie' =>$transRenduMonnaie,
                        'dataInternational' =>$transData,
                        'banking' =>$transSigma,
                        'commandeCarte' =>$transcommande,
                        'satisfaction' =>$transSatisfaction,
                        'reclamation' =>$transReclam,
                        'reclamationsIT'=>$transReclamIT,
                        'edan'=>$transelectricite,
                        'ria'=>$transRia
                    ],
                ]);

                break;

            case 'byVisa':
                $paiementVisa = DB::select("select * from historiquetrans h where LENGTH(h.param8)=36 and h.created_at >= '2023-03-09 00:00:00' ORDER BY h.id DESC limit 50");
                return response()->json([
                    'statut'=> true,
                    'customer'=> $paiementVisa,
                ]);

            case 'rm':
                $rendumonnaie = DB::select("select * from historiquetrans h where (LENGTH(h.param8)<20 or h.param8 IS null) and (h.operation ='rendu_monnaie_simple' or h.reference = 'rm_3.0') and h.created_at > '2023-03-09 00:00:00' order by h.id desc limit 70");
                return response()->json([
                    'statut'=> true,
                    'customer'=> $rendumonnaie,
                ]);

            case 'dataInternational':
                $dataInternational = Historiquetrans::whereNotIn('etat',['attend'])->whereIn('operation',[ 'esim_international', 'sim_international', 'forfait_international'])->orderBy('id', 'desc')->take(50)->get();
                return response()->json([
                    'statut'=> true,
                    'customer'=> $dataInternational,
                ]);

            case 'ria':
                $satisfaction = Historiquetrans::where('operation','transfert_ria')->orderBy('id', 'desc')->take(50)->get();
                return response()->json([
                    'statut'=> true,
                    'customer'=> $satisfaction,
                ]);
            case 'banking':
                $agenceSigma = Historiquetrans::where('operation','like', 'recharge_visa_%')->where('created_at', '>', '2023-03-09 00:00:00')->orderBy('id', 'desc')->take(70)->get();
                return response()->json([
                    'statut'=> true,
                    'customer'=> $agenceSigma,
                ]);

            case 'shopCard':
                $commandeCarte = Carte::whereNotIn('treatment', ['confirme','verification'])->orderBy('id', 'desc')->take(50)->get();
                return response()->json([
                    'statut'=> true,
                    'body'=> $commandeCarte,
                ]);

            case 'satifaction':
                $satisfaction = Historiquetrans::whereIn('operation',[ 'achat_credit', 'achat_forfait'])->orderBy('id', 'desc')->take(50)->get();
                return response()->json([
                    'statut'=> true,
                    'customer'=> $satisfaction,
                ]);
            case 'SupportIT':
                $reclamationIT = Reclammation::where('objet', 'like','Support%')->orderBy('id', 'desc')->take(50)->get();
                return response()->json([
                    'statut'=> true,
                    'body'=> $reclamationIT,
                ]);

            case 'reclam':
                $reclamation = Reclammation::where('objet', 'not like','Reclammation sur%')->orderBy('id', 'desc')->take(50)->get();
                return response()->json([
                    'statut'=> true,
                    'body'=> $reclamation,
                ]);

            case 'electric':
                $electricite = DB::select("SELECT * from gam_electricite_hist geh  WHERE  geh.etat <> 'attend' ORDER BY geh.id DESC limit 50");
                return response()->json([
                    'statut'=> true,
                    'body'=> $electricite,
                ]);
        }

    }

    public function detailsTransData($data){
        $transValidate = 0;
        $transloading = 0;
        $transError = 0;
        if(!empty($data)){
            foreach ($data as $trans){
                if(in_array($trans->etat, ['CONFIRMEE', 'confirme']) || ($trans->operation == 'recharge_compte_gam' && $trans->etat == 'atraiter') ){
                    $transValidate = $transValidate+1;
                }elseif (in_array($trans->etat, ['atraiter','en_attente','attente','TRAITE','a_valider','traite'])){
                    $transloading = $transloading +1;
                }else{
                    $transError = $transError +1;
                }
            }
        }else{
            $transValidate = 0;
            $transloading = 0;
            $transError = 0;
        }

        return [
            'validates' => $transValidate,
            'loanding' => $transloading,
            'errors' => $transError,
        ];
    }

    //**************************** FIN App back function ********************************


    public function renameDataInternational(){

        $datas = ForfaitInternational::whereNull('nomenclature')->where('param1', 'nat')->take(5)->get();
        foreach ($datas as $data ){
            $decoupe = explode("_",$data->forfait_country);

            $forfait = $decoupe[count($decoupe)-2];
            $day = substr(strval($decoupe[count($decoupe)-1]), 0, -1);

            if($decoupe[count($decoupe)-3] == 'EU' || $decoupe[count($decoupe)-3] == 'EU28PLUS'){
                $region = 'EUROPE';
            }else{
                $region = $decoupe[count($decoupe)-3];
            }

            ForfaitInternational::where('id', $data->id)->update([
                'nomenclature' => "$region $forfait $day days"
            ]);
        }
        return $datas;
    }

    // recuperer les infos des clients lors d'une transaction
    public function recupInfoCustomers($emetteur, $receveur){

        $emetteur = Customer::where('phoneclient',$emetteur)->first();
        $receveur = Customer::whereIn('phoneclient',[$receveur, '0'. $receveur])->first();

        if($receveur){
            $info = [
                'id'=>$receveur->id,
                'phone'=>$receveur->phoneclient,
                'whatsapp'=>$receveur->whatsapp,
                'nom'=>$receveur->nom,
                'prenom'=>$receveur->prenom,
                'pseudo'=>$receveur->pseudo,
            ];
        }else{
            $info = 0;
        }

        return json_encode([
            'emetteur' =>[
                'id'=>$emetteur->id,
                'phone'=>$emetteur->phoneclient,
                'whatsapp'=>$emetteur->whatsapp,
                'nom'=>$emetteur->nom,
                'prenom'=>$emetteur->prenom,
                'pseudo'=>$emetteur->pseudo,
            ],
            'receveur' => $info,
        ]);
    }


    public function testSendNotif(){
        $message = 'envoi la notif toi aussi';
        $this->sendNotificationInprogram('074582442', '074582442', 'info', '',$message, '', '');
    }


    public function rechargeGab(Request $request){
        $customer = Customer::where('id', intval($request->customer))->first();
        $newTrans = new Historiquetrans([
            'operation' => 'Recharge GAB',
            'reference' =>'AUTO',
            'etat' => 'CONFIRMEE',
            'numclient' => $customer->phoneclient,
            'phonevendeur' =>  $customer->phoneclient,
            'content'=> 'GAB',
            'montant' =>$request->montant,
            'montant_sans_frais' => $request->montant,
            'frais' =>'0',
            'solde' => $customer->solde,
            'origine_operation' => 'GAB',
            'id_customer' => $customer->id,
            'id_grand_parent' => $this->recupInfoCustomers($customer->phoneclient, $customer->phoneclient),
        ]);
        $newTrans->save();
        Customer::where('id', intval($request->customer))->update([
            'solde'=> strval(intval($customer->solde) + intval($request->montant))
        ]);
        $message = "Vous avez reçu $request->montant comme différence de votre opération sur GAB";
        $this->sendNotificationInprogram($customer->phoneclient, $customer->phoneclient, 'info', '',$message, '', '');

        return 1;
    }

    public function storeGabStock(Request $request){
        $customer = Customer::where('id', intval($request->customer))->first();

        Customer::where('id', intval($request->customer))->update([
            'stock_gab'=> $customer->stock_gab != null? intval($request->montant) + $customer->stock_gab : intval($request->montant)
        ]);
        $message = "Opération sur GAB échouée; $request->montant FCFA enregistré sur votre compte";
        $this->sendNotificationInprogram($customer->phoneclient, $customer->phoneclient, 'info', '',$message, '', '');

        return 1;
    }

    public function cleanGabStock(Request $request){
        Customer::where('id', intval($request->customer))->update([
            'stock_gab'=>0
        ]);
        return 1;
    }


    public function getTokenTestApp(){
        $tokenGenerate = Http::get('https://3ef541be3555.ngrok.app/gamclients/public/api/storeTokenOrabank')->body();
        return $tokenGenerate;
    }

    public function recupTransVisa(){
        $trans = DB::select("select * from historiquetrans h where LENGTH(h.param8)=36 and id_grand_parent like 'internationa%' and h.created_at >= '2023-03-09 00:00:00' ORDER BY h.id DESC limit 50");
        foreach ($trans as $tran){
            $tabInfo = $this->recupInfoCustomers($tran->phonevendeur, $tran->numclient);
            Historiquetrans::where('id', $tran->id)->update([
                'id_parent' => $tran->id_grand_parent,
                'id_grand_parent' => $tabInfo
            ]);
        }
        return $trans;
    }

    public function transPartenaire(Request $request){
        $now = date("Y-m-d");
        $yesterday = date("Y-m-d", strtotime('-1 day'));
        $transToday  = DB::select(" select * from historiquetrans h where phonevendeur in (select phoneclient from customer where type in ('partenaire', 'prix_import', 'pharmacie','agent') or username = 'partenaire' ) and created_at like '$now%' ");
        $transHier  = DB::select(" select * from historiquetrans h where phonevendeur in (select phoneclient from customer where type in ('partenaire', 'prix_import', 'pharmacie','agent') or username = 'partenaire' ) and created_at like '$yesterday%' ");

        $detailsToday = $this->detailsTransData($transToday);
        $detailsHier = $this->detailsTransData($transHier);

        return response()->json([
            'statut'=> true,
            'today'=>  count($transToday),
            'detailsToday' => $detailsToday,
            'hier'=>  count($transHier),
            'detailsHier' => $detailsHier,
        ]);

    }


    public function recupGain(Request $request){
        $verifCustomer = Customer::where('phoneclient', $request->numClient)->first();
        $day = date('d');
        $mounth = date('m');
        if($verifCustomer){
            if(!empty($request->operation) && $request->operation == 'parrainage'){
                if( $verifCustomer->status == 100 ){
                    if( intval($day) >= 30 ||  strval($mounth) == '02' && intval($day) >= 27 ){
                        if($verifCustomer->score >= 30){
                            Customer::where('id', $verifCustomer->id)->update([
                                'solde'=>  strval(doubleval($verifCustomer->solde) + doubleval($verifCustomer->solde_parrainage)),
                                'solde_parrainage' => '0'
                            ]);
                            $paiement = new Historiquetrans([
                                'operation' => 'Commissions',
                                'reference' => "AUTO",
                                'etat' => 'CONFIRMEE',
                                'numclient' => $verifCustomer->phoneclient,
                                'phonevendeur' => $verifCustomer->phoneclient,
                                'content'=> "parrainage",
                                'montant' =>$verifCustomer->solde_parrainage,
                                'montant_sans_frais' => $verifCustomer->solde_parrainage,
                                'frais' =>0,
                                'solde' => $verifCustomer->solde,
                                'origine_operation' => 'systeme',
                                'id_customer' => $verifCustomer->id,
                                'param2' => $verifCustomer->contry_code,
                                'param4' => $verifCustomer->pays,
                                'param9' => 'customer',
                            ]);
                            $paiement->save();

                            $messageNotif = "Versement de $verifCustomer->solde_parrainage FCFA total de vos commisions sur votre compte GamPay effectué ";
                            $this->sendNotificationInprogram($verifCustomer->phoneclient,$verifCustomer->phoneclient,'info', '',$messageNotif,null, null);

                        }else{
                            return response()->json([
                                'statut'=> false,
                                'message'=> "Vous n'avez pas atteint le quota de transaction mensuelle",
                            ]);
                        }
                    }else{
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'La récupération des gains se fait en fin de mois',
                        ]);
                    }
                }else{
                    return response()->json([
                        'statut'=> false,
                        'message'=> "Paiement des commissions bientôt disponible",
                    ]);
                }

            }else{

                $gain = Partage::where('id_status', $request->id)->where('id_referent', $verifCustomer->id)->first();
                $newSolde = doubleval($verifCustomer->solde) + doubleval($gain->gain);
                $remboursement = new Historiquetrans([
                    'operation' => 'recharge_compte_gam',
                    'reference' => $request->id,
                    'etat' => 'CONFIRMEE',
                    'numclient' => $request->numClient,
                    'phonevendeur' => $request->numClient,
                    'content'=> "Status shared",
                    'montant' =>strval($gain->gain),
                    'montant_sans_frais' => strval($gain->gain),
                    'frais' =>0,
                    'solde' => $verifCustomer->solde,
                    'origine_operation' => $request->origine,
                    'id_customer' => $verifCustomer->id,
                    'param2' =>'GA',
                    'param4' => $verifCustomer->pays,
                    'param9' => 'customer',
                ]);
                if($remboursement->save()){
                    $verifCustomer->update([
                        'solde'=>$newSolde
                    ]);
                    $gain->update([
                        'param2'=>'recuperer']);
                }
                return response()->json([
                    'statut'=> true,
                    'message'=> 'transfert effectué',
                ]);
            }
        }else{
            return response()->json([
                'statut'=> false,
                'message'=> 'Utilisateur introuvable',
            ]);
        }
    }

    public function allTransBfm(Request $request){
        $notifBfm = DB::SELECT(" select * from notif n WHERE n.receiver = $request->receiver and n.type ='bfm' ORDER BY id DESC ");

        return response()->json([
            'statut'=>true,
            'notif'=> $notifBfm,
        ]);
    }


    public function valideVisaOperation(Request $request){
        if(empty($request->id)){
            $trans = Historiquetrans::whereIn('operation', ['transfert_visa'])->where('reference', 'AUTO')->where('id', '>', 36146666)->where('etat', 'attend')->orderBy('id', 'desc')->take(2)->get();
        }else{
            $trans = Historiquetrans::where('id', $request->id)->get();
        }
        $token = 0;
        $status = 0;
        if(count($trans)>0){
            foreach ($trans as $tran){
                $recupToken = ProgramSubscription::where('id', 34357)->first();
                if(date('Y-m-d H:i', strtotime($recupToken->updated_at.'+ 5 min')) <= date('Y-m-d H:i') || $recupToken->param1 == null){
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

                $status= $this->orabankTransStatus($tran->param8, $token);
                $agents = Customer::where('username', 'agent_gam_international')->get();// recupère la liste des agents
                $order =  Orders::where('reference', $tran->param8)->first();
                if($status == 'PURCHASED' || $status == 'AUTHORISED'){
                    Historiquetrans::where('id', $tran->id)->update([
                        'etat' => 'atraiter',
                    ]);

                    if($order){
                        Orders::where('reference', $tran->reference)->update([
                            'status' => 'true'
                        ]);
                    }
                    if($agents){
                        foreach ($agents as $agent){
                            $message = "$tran->operation de $tran->montant_sans_frais vers le $tran->numclient éffectué via VISA ";
                            $this->sendNotificationInprogram($agent['phoneclient'], $agent['phoneclient'], 'info', '',$message, '', '');
                        }
                    }
                }elseif( in_array($status,['THREE_DS_NOT_AUTHENTICATED', 'PURCHASE_DECLINED']) || (date('Y-m-d H:i', strtotime($tran->created_at.'+ 20 min')) <= date('Y-m-d H:i') && $tran->etat == 'attend')) {

                    Historiquetrans::where('id', $tran->id)->update([
                        'reference' => 'FALSE',
                    ]);
                    if ($order) {
                        Orders::where('reference', $tran->reference)->update([
                            'status' => 'false'
                        ]);
                    }
                }
            }
        }
        return ['token'=>$token,'status'=>$status, 'trans'=> $trans];
    }

    public function rechargeCard(Request $request){
        if(!empty($request->card)){
            $myCard = Card::where('qr', $request->card)->first();
            if($myCard){
                $partner = Customer::where('id', $request->partner)->first();
                if(doubleval($partner->solde) >= doubleval($request->montant)){

                    $partner->update([ // debit du solde du partenaire
                        'solde'=> strval(doubleval($partner->solde) - doubleval($request->montant)),
                    ]);

                    if(!empty($myCard->customer)){
                        $client = Customer::where('id',$myCard->customer)->first();
                        $client->update([
                            'solde'=> empty($client->solde)? strval($request->montant) : strval(doubleval($client->solde) + doubleval($request->montant)),
                        ]);
                        $this->createTrans('recharge_compte_gam', 'rm_3.0', 'CONFIRMEE',$client->phoneclient, $partner->phoneclient, 'recharge_compte_gam', $request->montant, $request->montant, '0', $partner->solde, $request->origine, $partner->id, '241');
                        $this->sendNotificationInprogram($partner->phoneclient,$client->phoneclient,'rm_3.0', strval($request->montant),null,null, null  );

                    }else{
                        $myCard->update([
                            'amount'=> empty($myCard->amount)? strval($request->montant) : strval(doubleval($myCard->amount) + doubleval($request->montant)),
                            'partner'=> $partner->phoneclient,
                            'partner_name'=> $partner->nom
                        ]);
                        $this->createTrans('recharge_card_gam', 'rm_x', 'CONFIRMEE',$myCard->code, $partner->phoneclient, 'rm_x', $request->montant, $request->montant, '0', $partner->solde, $request->origine, $partner->id, '241');
                        $messaNotif = 'Vous avez rechargez la carte '.$myCard->code.' de '.$request->montant.' FCFA.';
                        $this->sendNotificationInprogram($partner->phoneclient,$partner->phoneclient,'info', '',$messaNotif,null, null  );
                    }
                    return response()->json([
                        'statut'=> true,
                        'message'=> 'operation en cours'
                    ]);

                }else{
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Votre solde GamPay est insuffisant'
                    ]);
                }
            }else{
                return response()->json([
                    'statut'=> false,
                    'message' => 'Carte inconnue',
                ]);
            }

        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'Informations incorrecte',
            ]);
        }
    }

    public function getMyBfm(Request $request){
        $numSansZero = substr($request->receiver, 1);
        $bfm = DB::SELECT("select * from historiquetrans h where (phonevendeur = $request->receiver or numclient in ( $request->receiver, $numSansZero) ) and param3 like 'BFM%' ORDER BY id DESC ");

        return response()->json([
            'statut'=>true,
            'body'=>count($bfm)>0?$bfm:0,
        ]);
    }

    public function updateRia(Request $request){
        $trans = Historiquetrans::where('operation','transfert_ria')->where('etat', '!=',['CONFIRMEE','confirmee'])->get();

        if ($trans) {
            Historiquetrans::where('id', $request->id)->update([
                'etat' => 'CONFIRMEE'
            ]);
            return 1;

        } else {
            return 0;
        }
    }


    public function storePriority($customer, $operation){
        $myCustomer = Customer::where('id', $customer)->orWhere('phoneclient', $customer)->first();
        if($myCustomer){
            $myCustomer->update(['priority'=> $operation]);
        }
    }


    /******* Api send whatsApp message *******/
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

    /******* END Api send whatsApp message *******/
    
    public function createTransCadeau(Request $request){
        $num = $request->phone;
        $cadeaux = Cadeau::where('id', $request->cadeau)->first();
        $challenger = Customer::where('id', $request->challenger)->first();
        $select = $request->select;

        if($cadeaux){
            switch ($cadeaux->operation){
                case "sim":
                    $trans = new Historiquetrans([
                        'operation' => $select == 'virtuelle'? 'esim_international': 'sim_international',
                        'reference' => 'cadeau',
                        'etat' => 'atraiter',
                        'numclient' => $challenger->phoneclient,
                        'phonevendeur' => $challenger->phoneclient,
                        'content'=> $cadeaux->content,
                        'montant' =>$cadeaux->montant,
                        'montant_sans_frais' => $cadeaux->montant,
                        'frais' =>'0',
                        'solde' => $challenger->solde,
                        'origine_operation' => 'App_Cadeau',
                        'id_customer' => $cadeaux->id,
                        'param2' => $cadeaux->param1,
                        'id_grand_parent' => $this->recupInfoCustomers($challenger->phoneclient, $challenger->phoneclient)

                    ]);
                    break;

                case "achat_credit" : case "achat_forfait":
                    $trans = new Historiquetrans([
                        'operation' => 'achat_credit',
                        'reference' => 'cadeau',
                        'etat' => 'attend',
                        'numclient' => $num,
                        'phonevendeur' => $challenger->phoneclient,
                        'content'=> $cadeaux->content,
                        'montant' =>$cadeaux->montant,
                        'montant_sans_frais' => $cadeaux->montant,
                        'frais' =>'0',
                        'solde' => $challenger->solde,
                        'origine_operation' => 'App_Cadeau',
                        'id_customer' => $cadeaux->id,
                        'id_grand_parent' => $this->recupInfoCustomers($challenger->phoneclient, $challenger->phoneclient)

                    ]);
                    break;
            }
            $trans->save();
            Cadeau::where('id', $cadeaux->id)->update([
                'statut' => 'livre'
            ]);
            
            return 1;
        }else{
            return 0;
        }
    }
    
    
    public function getTransRmWhatapp(Request $request){
        $phoneSansIndicatif = substr($request->whatsapp, 3);
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
        
        $numSansZero = substr($phone, 1);
        
        $trans = Historiquetrans::where('operation', 'recharge_compte_gam')->where('numclient', $phone)->where('content', 'whatsapp')->where('tentative_effectue', '0')->where('id','>',36724370)->first();
        $partenaire = Customer::where('phoneclient', $trans->phonevendeur)->first();
        $myCustomer = Customer::whereIn('whatsapp', [$request->whatsapp,'241'.$numSansZero,$phone])->orWhere('phoneclient', $phone)->first();

        if($myCustomer){
            $status = 'old';
            Customer::where('id',$myCustomer->id)->update([
                'solde' => strval(doubleval($myCustomer->solde) + doubleval($trans->montant_sans_frais)),
                'whatsapp' => $request->whatsapp
            ]);
            $this->sendNotificationInprogram($myCustomer->phoneclient, $myCustomer->phoneclient,'rm_3.0', $trans->montant_sans_frais,null,null, null);
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
                'whatsapp'=> $request->whatsapp,
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
            'tentative_effectue'=> '1'
        ]);

        return response()->json([
            'status' => $status,
            'phoneclient' => $compte->phoneclient,
            'numclient' => $phone,
            'partner'=> 'By GAM',
            'name' => $compte->nom ." ". $compte->prenom,
            'id' => strval($compte->id),
            'transId' => $trans->id,
            'solde' => $compte->solde,
            'amount'=> $trans->montant_sans_frais,
            'imageRecap' => "https://gampay.org/app.PNG",
            'imageCredit' => "https://gampay.org/app.PNG",
            'imageTransfert' => "https://gampay.org/app.PNG",
            'imageApp' => "https://gampay.org/app.PNG",
            'imageAssistance' => "https://gampay.org/app.PNG",
        ]);
    }
    
    public function getTransRmWhatAppOk(Request $request){
        $phoneSansIndicatif = substr($request->whatsapp, 3);
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
        $numSansZero = substr($phone, 1);

        $trans = Historiquetrans::where('operation', 'recharge_compte_gam')->where('numclient', $phone)->where('content', 'whatsapp')->where('tentative_effectue', '0')->where('id','>',36724370)->first();
        if($trans){
            $partenaire = Customer::where('phoneclient', $trans->phonevendeur)->first();
            //$myCustomer = Customer::whereIn('whatsapp', [$request->whatsapp,'241'.$numSansZero,$phone])->orWhere('phoneclient', $phone)->first();
            $myCustomer = Customer::where('phoneclient', $phone)->first();
            if($myCustomer){
                if(empty($trans->param7)){
                    Customer::where('id',$myCustomer->id)->update([
                        'solde' => strval(doubleval($myCustomer->solde) + doubleval($trans->montant_sans_frais)),
                        'whatsapp' => $request->whatsapp
                    ]);
                }
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
                    'whatsapp'=> $request->whatsapp,
                    'satus'=>1
                ]);
                $customer->save();
                Http::get('https://gampay.app/gamclients/public/api/recupIdCustomer');
                $compte = $customer;
                $status = 'new';
            }

            Historiquetrans::where('id', $trans->id)->update([
                'numclient' => $compte->phoneclient,
                'reference' => 'open',
                'param7' => empty($trans->param7)? $request->direction : $trans->param7.'-'.$request->direction
            ]);

            if(empty($request->direction)){
                return response()->json([
                    'status' => true,
                    'message' =>"Début de l'opération",
                    'phoneclient' => $compte->phoneclient,
                    'numclient' => $trans->numclient,
                    'partner'=> $partenaire->nom,
                    'name' => $compte->nom ." ". $compte->prenom,
                    'id' => strval($compte->id),
                    'transId' => $trans->id,
                    'solde' => $compte->solde,
                    'amount'=> $trans->montant_sans_frais,
                    'imageRecap' => "https://gampay.app/video_flow/rm.mp4",
                    'imageCredit' => "https://gampay.org/app.PNG",
                    'imageTransfert' => "https://gampay.org/app.PNG",
                    'imageApp' => "https://gampay.org/app.PNG",
                    'imageAssistance' => "https://gampay.org/app.PNG",
                ]);
            }else{
                return redirect('https://gampay.org/gamclients/public/api/orientationCustomerRm?montant='.$trans->montant_sans_frais.'&phone='.$compte->phoneclient.'&whatsapp='.$request->whatsapp.'&operation='.$request->direction.'&numclient='.$trans->numclient);
            }
        }else{
            if(empty($request->direction)){
                return response()->json([
                    'status' => false,
                    'message' => "Votre rendu monnaie a déjà été effectué"
                ]);
            }else{
                $myCustomer = Customer::whereIn('whatsapp', [$request->whatsapp,'241'.$numSansZero,$phone])->orWhere('phoneclient', $phone)->first();
                if($myCustomer){
                    $data = 'history*'.$myCustomer->phoneclient.'*200*gam';
                    return redirect("https://app.gampay.org/?log=$myCustomer->id&v=$data");
                }else{
                    return redirect("https://app.gampay.org/?p=2280618&v=vue");
                }
            }
        }
    }
    
    public function getStatusTrans(Request $request){
        $trans = Historiquetrans::where('phonevendeur', $request->phone)->where('origine_operation', "scyd")->orderBy('id', 'desc')->first();

        switch($trans->operation){
            case 'achat_credit' :
                $debutMessage = "Votre achat crédit de $trans->montant_sans_frais FCFA vers le $trans->numclient ";
                break;
            case 'recharge_visa_uba' :
                $debutMessage = "Votre recharge visa de $trans->montant_sans_frais FCFA vers le compte $trans->content ";
                break;
            case 'achat_forfait' :
                $debutMessage = "Votre achat forfait de $trans->montant_sans_frais FCFA vers le $trans->numclient ";
                break;
            case 'transfert_mobile' :
                $debutMessage = "Votre transfert de $trans->montant_sans_frais FCFA vers le $trans->numclient ";
                break;
            default :$debutMessage = "Votre opération GamPay de $trans->montant_sans_frais FCFA vers le $trans->numclient ";
        }

        if( in_array($trans->etat, ['CONFIRMEE', 'confirme'])){
            $status = "confirme";
            $finMessage = "a été effectué avec succès.";
        }else{
            $status = "traite";
            $finMessage = "est en cours de traitement. Nous nous activons pour vous satisfaire au plus vite";
        }
        
        $confirm = $this->confirmeRmwhatsapp($trans->id, $trans->montant, $trans->phonevendeur,$request->trans);

        return response()->json([
            'status' => $status,
            'message' => $debutMessage.''.$finMessage,
            'id' => $trans->id
        ]);
    }
    
    public function getOperateur($operation, $numero, $pays){
        $debut = $numero[0].''.$numero[1].''.$numero[2];
        $start = $numero[0].''.$numero[1];
        if((in_array($debut, ['074', '076', '077']) || in_array($start, ['74', '76', '77'])) and in_array($pays,['+241', '241'])){
            if($operation == 'achat_credit'){
                $operateur = 'AIRTEL_GA';
            }else{
                $operateur = 'AIRTEL MONEY';
            }
        }elseif ((in_array($debut, ['066', '062', '060', '065']) || in_array($start, ['66', '62', '60', '65'])) and  in_array($pays,['+241', '241'])){
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
    
    public function sendTemplateMessageRm($whatsapp,$messageId,$customer_name,$amount,$partner,$file,$trans, $level)
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
                        "templateName": "rmsimplie",
                        "templateData": {
                          "body": {
                            "placeholders": ["'.$customer_name.'", "'.$amount.'", "'.$partner.'"]
                          },
                         "header": {
                            "type": "VIDEO",
                            "mediaUrl": "https://gampay.app/video_flow/whatsapprm.mp4"
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
        
        $result = curl_exec($curl);
        //return $result;
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
    
    public function sendTemplateMessageRmSave($whatsapp,$messageId,$customer_name,$amount,$partner,$file,$trans, $level)
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
                        "templateName": "rmdirection",
                        "templateData": {
                          "body": {
                            "placeholders": ["'.$customer_name.'", "'.$amount.'", "'.$partner.'"]
                          },
                         "header": {
                            "type": "VIDEO",
                            "mediaUrl": "https://gampay.app/video_flow/whatsapprm.mp4"
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "?whatsapp='.$whatsapp.'&direction=achat_credit"},
                            {"type": "URL", "parameter": "?whatsapp='.$whatsapp.'&direction=transfert_mobile"},
                            {"type": "QUICK_REPLY", "parameter": "transfertvisa"},
                            {"type": "QUICK_REPLY", "parameter": "achatforfait"},
                            {"type": "QUICK_REPLY", "parameter": "achatvisauba"},
                            {"type": "QUICK_REPLY", "parameter": "preinscription"},
                            {"type": "QUICK_REPLY", "parameter": "esiminternational"},
                            {"type": "QUICK_REPLY", "parameter": "achatedan"},
                            {"type": "QUICK_REPLY", "parameter": "assister"},
                            {"type": "QUICK_REPLY", "parameter": "appli"}
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
        
            /*curl_setopt_array($curl, array(
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
                                "placeholders": ["'.$customer_name.'", "'.$amount.'", "'.$partner.'"]
                            },
                           "header": {
                                "type": "VIDEO",
                                "mediaUrl": "https://gampay.app/video_flow/whatsapprm.mp4"
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
        }*/

        $result = curl_exec($curl);
        //return $result;
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
    
    
    public function checkNumberUseWhatsapp($numero){
        $numSansZero = substr($numero, 1);
        $numwhat = WhatsAppVerify::where('number', "241$numSansZero")->first();
        if($numwhat){
            if($numwhat->status == "true"){
                return 1;
            }else{
                return 0;
            }
        }else{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://zylalabs.com/api/926/whatsapp+number+checker+api/743/number+checker?number=241$numSansZero");
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
                            'number'=>  "241$numSansZero",
                            'status' => "true"
                        ]);
                        $andWhat->save();
                        return 1;
                    }else{
                        $andWhat = new WhatsAppVerify([
                            'number'=>  "241$numSansZero",
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
    
    public function verifWhatsAppMessagebackup($mess,$trans,$latence){
        if(!empty($latence)){
            sleep($latence);
        }
        $backup = WhatsAppBackUp::where('messageId', $mess)->first();
        if($backup){
            if(!empty($backup->seen_at)){
                $status = 'open';
            }else{
                $status = 'delivered';
            }
            Historiquetrans::where('id', $trans)->update([
                'reference' => $status
            ]);
        }else{
            $trans = Historiquetrans::where('id', $trans)->first();
            Historiquetrans::where('id', $trans)->update([
                'operation' => $trans->param9,
                'content' => $trans->param2,
                'tentative_effectue'=> '1',
                'param2' => $request->aNull,
                'param1' => $request->aNull,
                'param9' => 'banking',
                'etat' => 'atraiter'
            ]);
        }
        return 1;
    }
    
   // traitement rm whatsapp pas reçu
    public function verifWhatsAppTransTraite(Request $request){
        $trans = DB::select("select * from historiquetrans h WHERE h.content ='whatsapp' and h.reference in ('rm_3.0', 'rm_4.0') and h.id>36721930 and deleted_at is null order by id desc limit 10");        
        if(count($trans)>0){
            foreach ($trans as $tran){
                $activity =  date('Y-m-d H:i:s', strtotime("$tran->created_at +10 seconds"));
                $now = date('Y-m-d H:i:s');
             
                if($activity<$now){
                    if(in_array($tran->reference, ['rm_3.0', 'rm_4.0'])){
                        Historiquetrans::where('id', $tran->id)->update([
                            'operation' => $tran->param9,
                            'content' => $tran->param2,
                            'etat' => 'atraiter',
                            'tentative_effectue'=> '1',
                            'param2' => $request->aNull,
                            'param1' => $request->aNull,
                            'param9' => 'banking'
                        ]); 
                        
                        if($tran->reference == 'rm_4.0'){
                            $customer = Customer::where('phoneclient', $tran->numclient)->first();
                            if($customer){
                                if(doubleval($customer->solde) >= doubleval($tran->montant)){
                                    Customer::where('id', $customer->id)->update([
                                       'solde'=> strval(doubleval($customer->solde) - doubleval($tran->montant))
                                    ]); 
                                }
                            }
                        }
                    }
                }
            }
        }
        return $trans;
    }
    
    
    public function storeActivityPartner($partner, $day, $stock){
        $now = date('Y-m-d');
        if(str_contains(strval($day), strval($now))){
            if(empty($stock)){
                $trans = 1;
            }else{
                $trans = $stock +1;
            }
        }else{
            $trans = 1;
        }
        Customer::where('id', $partner)->update([
            'last_transaction_at' => date('Y-m-d H:i:s'),
            'stock_gab' => $trans
        ]);
        return 1;
    }
    
    
    public function confirmeRmwhatsapp($id,$montant,$phonevendeur,$trans){
        if(empty($trans)){
            $recharge =  Historiquetrans::where('operation', 'recharge_compte_gam')->where('montant', $montant)->where('numclient', $phonevendeur)->where('content', 'whatsapp')->whereIn('etat', ['open', 'delivered'])->first();
            if(!empty($recharge)){
                Historiquetrans::where('id', $recharge->id)->update([
                    'param5' => 'success'
               ]);
            }
        }else{
            Historiquetrans::where('id', intval($trans))->update([
                'param5' => 'success'
            ]);
        }
       
        Historiquetrans::where('id', $id)->update([
            'param8' => 'success'
        ]);
    }
    
    public function getCustomerUsingScyd($phone){
        $trans = Historiquetrans::where(function($q) use($phone) {
            $q->whereIn('numclient', [$phone, substr($phone, 1)])->orWhere('phonevendeur',$phone);
        })->where('origine_operation', 'scyd')->get();
        return count($trans);
    }
    
    
    public function testVerifWhatsapp(Request $request){
        $verifWhatsApp = $this->checkNumberUseWhatsapp($request->phone);
        return $verifWhatsApp;
    }
    
    public function recupDataDevice($deviceData, $customer){
        $myCustomer = Customer::where('id', intval($customer))->first();
        $tabData = $deviceData;
        if(!empty($tabData['myDevice']) && $tabData['myDevice'] != 'ko' && empty($myCustomer->my_device)){
            $myDeviceId =  $tabData['myDevice'];
        }else{
            $myDeviceId = $myCustomer->my_device;
        }
        Customer::where('id',$myCustomer->id)->update([
            'param8' =>!empty($tabData['device'])? $tabData['device'] : $myCustomer->param8,
            'my_device' => $myDeviceId
        ]);
    }
    
    public function testsendTemplateRm(){
        $tempalte = $this->sendTemplateMessageRm('241060064024','rmtesttemplate1','Justin laurent','400','GAm','file','', 0);
        return $tempalte;
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
            'operation'=> $operation,
            'content' => $content == 'MOBICASH'? 'LIBERTIS':'AIRTEL_GA',
            'timestamps' => date('Y-m-d H:i:s')
        ]);
    }
    
    
    public function getContryIp($myIp){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "ipinfo.io/$myIp?token=5aa25ae6c48863");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Authorization: Bearer 94171fac-a916-4f7c-b634-0760d146e4f7"));
        $result = curl_exec($ch);// Check HTTP status code
        $response = json_decode($result);
        return $response ->country ;
    }
    public function getContryIpTest(Request $request){
       $myIp = $request->ip();
       return $this->getContryIp($myIp);
    }
    
    public function getNomMobile($phone){// recupérer les nom des mobile money
        $info = CustomerComplement::where('phoneclient', $phone)->first();
        if($info){
            if(!empty($info->nom) && !str_contains($info->nom, 'trer PIN') && $info->nom!=''){
                 $nom = str_replace(",","",$info->nom);
            }else{
                $nom = '0';
            }
        }else{
            $newInfo = new CustomerComplement([
                'id_customer'=>12354,
                'phoneclient'=> $phone
            ]);
            $newInfo->save();
            $nom = '0';
        }
        return $nom;
        
        // testing restauration
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
                            "mediaUrl": "https://gampay.app/video_flow/whatsapprm.mp4"
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "'.$whatsapp.'"},
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
    
    public function storeEtapeInComment($customer, $etape){
        $comment = new Comment([
            'article' => 'step GamPay payment',
            'numero_client' => $customer,
            'commentaire' => $etape,
        ]);
        $comment->save();
    }
    
    public function sendTemplateMessageRmGoStore($whatsapp,$messageId, $customer_name, $amount, $partner, $numClient)
    {
        $message = "$customer_name, vous avez reçu $amount FCFA de monnaie (*$partner*). Découvrez encore plus de produits & services sur notre chaîne WhatsApp";

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
                        "templateName": "new_communication_rm_image",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "IMAGE",
                            "mediaUrl": "https://gampay.org/image_flow/newComrm.jpg"
                          },
                           "buttons": [
                            {"type": "URL", "parameter": "' . $whatsapp . '&rm=rm&messageId=' . $messageId . '"},
                            {"type": "QUICK_REPLY", "parameter": "application_rm"},
                            {"type": "QUICK_REPLY", "parameter": "later"}
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
        // Close handle
        curl_close($curl);
    }
    
    /****************** Communication template *****************************/
    public function sendTemplateMessageRmGoUssd($whatsapp,$messageId, $customer_name, $amount, $partner)
    {
        $message = "$customer_name, vous avez reçu $amount FCFA de monnaie (*$partner*). Découvrez notre service d'achat de crédit Libertis en payant par Airtel Money (*150*3*10*GAM*MONTANT*Numéro Libertis*mot de passe#). Plus d'information https://wa.me/24174471892/.";

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
                        "templateName": "new_com1_rm_video",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "VIDEO",
                            "mediaUrl": "https://gampay.org/video_flow/new_video_comm_ussd_retouch.mp4"
                          },
                           "buttons": [
                            {"type": "URL", "parameter": "' . $whatsapp . '&new_rm=rm"},
                            {"type": "QUICK_REPLY", "parameter": "makeknown_rm"},
                            {"type": "QUICK_REPLY", "parameter": "assistance_rm"},
                            {"type": "QUICK_REPLY", "parameter": "not_interested"}
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
                    return $result;
                }
            } else {
                return $result;
            }
        } else {
            return $result;
        }
        // Close handle
        curl_close($curl);
    }
    
    public function templateCustomerRmMessageVisa($whatsapp,$messageId,$customer_name, $amount, $partner)
    {
        $message = "$customer_name, vous avez reçu $amount FCFA de monnaie (*$partner*). Rechargez votre carte Visa UBA prépayée en 5 minutes sur *GamPay*";
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
                        "templateName": "client_rm_message_visa",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "IMAGE",
                            "mediaUrl": "https://gampay.org/image_flow/communication_recharge_visa.jpg"
                          },
                          "buttons": [
                            {"type": "QUICK_REPLY", "parameter": "interested_rm_message_visa"},
                            {"type": "QUICK_REPLY", "parameter": "not_interested_rm_message_visa"},
                            {"type": "QUICK_REPLY", "parameter": "download_rm_message_visa"},
                            {"type": "QUICK_REPLY", "parameter": "assistance_rm_message_visa"},
                            {"type": "URL", "parameter": "' . $whatsapp . '&abonne_rm_message_visa=go"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "https://gampay.app/gamclients/public/api/recupCallBack"
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
        // Close handle
        curl_close($curl);
    }
    
    public function templateCustomerForRelanceCommandeCardVisaRm($whatsapp, $messageId, $amount, $partner)
    {
        $message = "Vous avez reçu $amount FCFA de monnaie (*$partner*). Bonne nouvelle : vous pouvez commander votre carte Visa UBA prépayée sur *GamPay* et faites vous livrée, en toute simplicité.";
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
                        "templateName": "relance_commande_visa",
                        "templateData": {
                          "body": {
                            "placeholders": ["' . $message . '"]
                          },
                         "header": {
                            "type": "VIDEO",
                            "mediaUrl": "https://gampay.org/video_flow/commande.mp4"
                          },
                          "buttons": [
                            {"type": "QUICK_REPLY", "parameter": "commande_rm_visa"},
                            {"type": "QUICK_REPLY", "parameter": "assistance_rm_visa"},
                            {"type": "URL", "parameter": "' . $whatsapp . '&abonne_rm_visa=ok"}
                          ]
                        },
                        "language": "fr"
                      },
                      "notifyUrl": "https://gampay.app/gamclients/public/api/recupCallBack"
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
        // Close handle
        curl_close($curl);
    }
    
    /****************** Fin communication template *****************************/
    
    public function testNewTemplateRm(Request $request){
       return $this->sendTemplateMessageRmGoUssd($request->phone, 'new_com1_rm_video', 'LAULAU', '200', '074582442');
    }
    
    public function checkNumWhatsapp($phone)
    {
        $client = new Client();
        $response = $client->request('POST', 'https://waapi.app/api/v1/instances/52447/client/action/get-number-id', [
            'body' => '{"number":"' . $phone . '"}',
            'headers' => [
                'accept' => 'application/json',
                'authorization' => 'Bearer GZksqGyZxKRnOfihBKpIbSUM9p3Bhps6bI6zmuUKaea8e277',
                'content-type' => 'application/json',
            ],
        ]);
        $decode = json_decode($response->getBody(),true);
        return $decode['data']['status'];
    }
    
    public function sendTemplateAfterRm(){
        $trans = DB::select("select * from historiquetrans h where h.operation NOT in ('recharge_compte_gam', 'recieve_gam_transfert', 'emit_gam_transfert', 'gam_transfert') and h.param8 = 'partenaire' and (h.param5 is NULL or h.param5 !='point') and h.etat in ('CONFIRMEE', 'confirme') and h.id>38307469 ORDER BY updated_at desc limit 5");
        if(count($trans)>0){
            foreach ($trans as $tran){
                $whatsapp = '241'. substr($tran->numclient, 1);
                //$whatsapp = '24104071340';
                //$messagewhatId = 'new_com1_rm_video'.time();
                //$messagewhatId = 'client_rm_message_visa' .  time();
                $messagewhatId = 'rm_commande_visa' .  time();
                $partner = '';
                if(str_contains($tran->id_grand_parent, ',')){
                    $dataPartner = json_decode($tran->id_grand_parent, true);
                    $partner = $dataPartner['emetteur']['nom'];
                }
               //$this->sendTemplateMessageRmGoUssd($whatsapp,$messagewhatId, "Bonjour cher client", strval($tran->montant), $partner);
               
                //$backup = WhatsAppBackUp::where('messageId', 'like', "new_com1_rm_video%")->where('recipeint', 'like', "%{$whatsapp}%")->where('status', 'DELIVERED')->get();
                //$backup = WhatsAppBackUp::where('messageId', 'like', "client_rm_message_visa%")->where('recipeint', 'like', "%{$whatsapp}%")->where('status', 'DELIVERED')->get();
                $backup = WhatsAppBackUp::where('messageId', 'like', "rm_commande_visa%")->where('recipeint', 'like', "%{$whatsapp}%")->where('status', 'DELIVERED')->get();

                if(count($backup) == 0){
                    //$check1 = $this->checkNumWhatsapp($whatsapp);
                    //if ($check1 == 'success') {
                        //$this->sendTemplateMessageRmGoUssd($whatsapp,$messagewhatId, "Bonjour cher client", strval($tran->montant), $partner);
                        //$this->templateCustomerRmMessageVisa($whatsapp, $messagewhatId,"Bonjour cher client",strval($tran->montant), $partner);
                        $this->templateCustomerForRelanceCommandeCardVisaRm($whatsapp,$messagewhatId,strval($tran->montant),$partner);
                    //}
                }
               
                $getDataTrans = '50';
                if(!in_array($tran->reference, ['AUTO', '1', '2', '3', '4', '5', '6', '7', '8', '9'])){
                    try {
                        $getDataTrans = $this->getDataTransConfirm($tran->reference);
                    } catch (Throwable $e) {
                        $getDataTrans = '50';
                    }
                    
                }

                Historiquetrans::where('id', $tran->id)->update([
                    'param5'=>'point',
                    'tentative_autorise' => $getDataTrans
                ]);
            
                $customer = Customer::where('phoneclient',$tran->numclient)->first(); 
                if($customer){
                    $option = 'rm';
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
                        'code_confirm' => empty($customer->code_confirm)? $option : $customer->code_confirm,
                    ]);
                }
            }
        }
        return $trans;
    }
    
    public function getDataTransConfirm($ref){
       $data = GamRechargeHist::where('reference', $ref)->where('etat', 'sent	tonumbergam')->first();
       //$data = GamRechargeHist::where('id', "like","109102050%")->first();
        if($data){
            if(str_contains(strval($data->content), 'solde')){
                if($data->sender == 'AirtelMoney'){
                    $debut = explode(',', $data->content);
                    $partName = explode('.', $debut[1]);
                }
                
                elseif($data->sender == 'Moov Money'){
                    
                    $debut = explode('-', $data->content);
                    $partName = explode('le 20', $debut[1]);
                }
            }
            return $partName[0];
        }
        return '50';
    }
    
    // public function getDataTransConfirm($ref){
    //     $data = GamRechargeHist::where('reference', $ref)->where('etat', 'sent	tonumbergam')->first();
    //     //return $data;
    //     if($data){
    //         if(str_contains(strval($data->content), 'Solde')){
    //             if($data->sender == 'Moov Money'){
    //                 $debut = explode('-', $data->content);
    //                 $partName = explode('le 20', $debut[1]);
    //             }else if($data->sender == 'AirtelMoney'){
    //                 $debut = explode(',', $data->content);
    //                 $partName = explode('.', $debut[1]);
    //             }
    //             return $partName[0];
    //         }
    //     }
    //     return '50';
    // }

}
    
    

