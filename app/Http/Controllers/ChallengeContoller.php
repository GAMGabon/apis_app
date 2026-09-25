<?php

namespace App\Http\Controllers;

use App\Models\Cadeau;
use App\Models\Client2;
use App\Models\Customer;
use App\Models\Followers;
use App\Models\Historiquetrans;
use App\Models\Vue;
use App\Models\Lot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class ChallengeContoller extends Controller
{
    public function index(Request $request){

        return view('challenge.index');
    }

    public function lots(Request $request){

        return view('challenge.lots');
    }
    
    public function filleul(Request $request){
        $challenger = Customer::where('id',intval($request->challenger))->first();
        $filleuls = Customer::whereNull('username')->where('type', 'CLIENT_LAMBDA')->sum('Fonction8');
        $customers = Customer::whereNull('username')->where('type', 'CLIENT_LAMBDA')->orderBy('Fonction8', 'desc')->take(100)->get();
        $firstFiveTab = Customer::whereNull('username')->where('type', 'CLIENT_LAMBDA')->orderBy('Fonction8', 'desc')->take(5)->get();
        $firstFive = 0;
        foreach ($firstFiveTab as $five){
            $firstFive = $firstFive + $five->Fonction8;
        }
        $gain = 'ko';
        $last = 0;
         $lastWinners = 0;
        $objectif = 100;
        $montant = 100000;
        if($challenger->type == "prix_import"){
            return redirect('https://gampay.org/gamclients/public/caissiere?caissiere='.$challenger->id);
        }else if($challenger->type == "hotesse"){
            return redirect('https://gampay.org/gamclients/public/hotesse?hotesse='.$challenger->id);
        }else{
           if($challenger->type == 'project' || str_contains($challenger->username, 'gam')){
               /* $data = json_encode([
                    'user'=> strval($challenger->id),
                    'page'=> 'project',
                ]);*/
                return redirect("https://app.gampay.org/?go=action&phoneaction=".$challenger->phoneclient);
            }
            
            return redirect('https://app.gampay.org/?v=chef&p='.$challenger->id);  
            //return redirect('https://gampay.app/gamclients/public/classementQuiz?fzqut='.$challenger->id);
        }
        foreach ($firstFiveTab as $customer){
            if( $customer ->id == $challenger->id){
                $gain = 'ok';
            }
            $last = $last +1;
            if($last == 5){
                $lastWinners = $customer->Fonction8;
            }
        }
        return view('challenge.filleul', compact('customers', 'filleuls', 'challenger', 'objectif','montant', 'firstFive', 'gain', 'lastWinners'));
    }
    
    public function boris(Request $request){

        $challenger = Customer::where('id',intval($request->boris))->first();
        $num = $challenger->phoneclient;
        $invite = $request->invite;

        $trans = Historiquetrans::where('id','>', 34477999)->whereIn('phonevendeur', static function($q) use($num){
            $q->select('phoneclient')->from('customer')->where( 'phoneparent', $num)->orWhere( 'phonegrandparent', $num)->get();
        })->whereNotIn('operation', ['gam_transfert', 'recharge_compte_gam'])->whereNotIn('origine_operation', ['AGENCE_SIGMA'])->whereIn('etat',['CONFIRMEE', 'confirme', 'atraiter', 'en_attente', 'TRAITE'])->orderBy('created_at', 'desc')->get();

        return view('challenge.boris', compact('challenger', 'trans','invite'));
    }
    
    public function filleulBack(Request $request){

        return view('challenge.filleulBack');
    }
    
    public function verifieFilleu(){
        $customers = Customer::orderBy('filleuls', 'desc')->whereNull('username')->take(30)->get();
       
        foreach ($customers as $customer){
            $filleul= Customer::where('phoneparent', $customer->phoneclient)->get();
            $filleulOld= Customer::where('phonegrandparent', $customer->phoneclient)->get();

            Customer::where('id',$customer->id)->update([
                'filleuls'=> count($filleul),
                //'petit_fils'=> count($filleulOld),
            ]);
        }

        return 10;
    }
    
    public function getCommisions(){
        $trans = Historiquetrans::where('id','>', 34477999)->whereIn('etat',['CONFIRMEE', 'confirme'])->
        whereNull('code_validation')->whereNotIn('operation',['gam_transfert', 'recharge_compte_gam'])->whereNotIn('origine_operation', ['AGENCE_SIGMA'])->take(40)->get();

        if($trans){
            foreach ($trans as $tran){
                $client = Customer::where('phoneclient',$tran->phonevendeur)->first();
                switch ($tran->operation) {
                    case 'recharge_visa_uba' :
                        if(!empty($client->phoneparent) && !empty($client->phonegrandparent)){
                            $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                            $parrainOld = Customer::where('phoneclient', $client->phonegrandparent)->first();

                            Customer::where('id', $parrain->id)->update([
                                'solde_parrainage' => $parrain->solde_parrainage == null? strval(doubleval($tran->montant_sans_frais) * 0.0025)  : strval(intval($parrain->solde_parrainage) + intval(strval(doubleval($tran->montant_sans_frais) * 0.0025)))
                            ]);

                            Customer::where('id', $parrainOld->id)->update([
                                'solde_parrainage' => $parrainOld->solde_parrainage == null? strval(doubleval($tran->montant_sans_frais) * 0.0025)  : strval(intval($parrainOld->solde_parrainage) + intval(strval(doubleval($tran->montant_sans_frais) * 0.0025)))
                            ]);

                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' =>  strval(doubleval($tran->montant_sans_frais) * 0.0025)
                            ]);

                        }elseif(!empty($client->phoneparent)){
                            $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                            Customer::where('id', $parrain->id)->update([
                                'solde_parrainage' => $parrain->solde_parrainage == null? strval(doubleval($tran->montant_sans_frais) * 0.005)  : strval(intval($parrain->solde_parrainage) + intval(strval(doubleval($tran->montant_sans_frais) * 0.005)))
                            ]);

                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' => strval(doubleval($tran->montant_sans_frais) * 0.005)
                            ]);
                        }else{
                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' =>'parrainage_off'
                            ]);
                        }
                        break;

                    case 'sim_international' : case 'esim_international' :
                    if(!empty($client->phoneparent) && !empty($client->phonegrandparent)){
                        $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                        $parrainOld = Customer::where('phoneclient', $client->phonegrandparent)->first();

                        Customer::where('id', $parrain->id)->update([
                            'solde_parrainage' => $parrain->solde_parrainage == null? '500': strval(intval($parrain->solde_parrainage) + 500)
                        ]);

                        Customer::where('id', $parrainOld->id)->update([
                            'solde_parrainage' => $parrainOld->solde_parrainage == null? '500': strval(intval($parrainOld->solde_parrainage) + 500)
                        ]);

                        Historiquetrans::where('id',$tran->id)->update([
                            'code_validation' => '500'
                        ]);


                    }elseif(!empty($client->phoneparent)){
                        $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                        Customer::where('id', $parrain->id)->update([
                            'solde_parrainage' => $parrain->solde_parrainage == null? '1000': strval(intval($parrain->solde_parrainage) + 1000)
                        ]);

                        Historiquetrans::where('id',$tran->id)->update([
                            'code_validation' =>'1000'
                        ]);
                    }else{
                        Historiquetrans::where('id',$tran->id)->update([
                            'code_validation' =>'parrainage_off'
                        ]);
                    }
                    break;

                    case 'achat_visa_uba' :
                        if(!empty($client->phoneparent) && !empty($client->phonegrandparent)){
                            $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                            $parrainOld = Customer::where('phoneclient', $client->phonegrandparent)->first();

                            Customer::where('id', $parrain->id)->update([
                                'solde_parrainage' => $parrain->solde_parrainage == null? '1500': strval(intval($parrain->solde_parrainage) + 1500)
                            ]);

                            Customer::where('id', $parrainOld->id)->update([
                                'solde_parrainage' => $parrainOld->solde_parrainage == null? '1500': strval(intval($parrainOld->solde_parrainage) + 1500)
                            ]);

                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' => '1500'
                            ]);
                        }elseif(!empty($client->phoneparent)){
                            $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                            Customer::where('id', $parrain->id)->update([
                                'solde_parrainage' => $parrain->solde_parrainage == null? '3000': strval(intval($parrain->solde_parrainage) + 3000)
                            ]);

                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' => '3000'
                            ]);
                        }else{
                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' =>'parrainage_off'
                            ]);
                        }
                        break;

                    case 'forfait_international' :
                        if(!empty($client->phoneparent) && !empty($client->phonegrandparent)){
                            $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                            $parrainOld = Customer::where('phoneclient', $client->phonegrandparent)->first();

                            Customer::where('id', $parrain->id)->update([
                                'solde_parrainage' => $parrain->solde_parrainage == null? strval(doubleval($tran->montant_sans_frais) * 0.05)  : strval(intval($parrain->solde_parrainage) + intval(strval(doubleval($tran->montant_sans_frais) * 0.05)))
                            ]);

                            Customer::where('id', $parrainOld->id)->update([
                                'solde_parrainage' => $parrainOld->solde_parrainage == null? strval(doubleval($tran->montant_sans_frais) * 0.05)  : strval(intval($parrainOld->solde_parrainage) + intval(strval(doubleval($tran->montant_sans_frais) * 0.05)))
                            ]);

                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' =>strval(doubleval($tran->montant_sans_frais) * 0.05)
                            ]);

                        }elseif(!empty($client->phoneparent)){
                            $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                            Customer::where('id', $parrain->id)->update([
                                'solde_parrainage' => $parrain->solde_parrainage == null? strval(doubleval($tran->montant_sans_frais) * 0.15)  : strval(intval($parrain->solde_parrainage) + intval(strval(doubleval($tran->montant_sans_frais) * 0.15)))
                            ]);

                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' =>  strval(doubleval($tran->montant_sans_frais) * 0.15)
                            ]);
                        }else{
                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' =>'parrainage_off'
                            ]);
                        }
                        break;

                    default :
                        if(!empty($client->phoneparent) && !empty($client->phonegrandparent)){

                            $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                            $parrainOld = Customer::where('phoneclient', $client->phonegrandparent)->first();

                            if(strlen($tran->param8)>35){
                                Customer::where('id', $parrain->id)->update([
                                    'solde_parrainage' => $parrain->solde_parrainage == null? strval(doubleval($tran->montant_sans_frais) * 0.0025)  : strval(intval($parrain->solde_parrainage) + intval(strval(doubleval($tran->montant_sans_frais) * 0.0025)))
                                ]);

                                Customer::where('id', $parrainOld->id)->update([
                                    'solde_parrainage' => $parrainOld->solde_parrainage == null? strval(doubleval($tran->montant_sans_frais) * 0.0025)  : strval(intval($parrainOld->solde_parrainage) + intval(strval(doubleval($tran->montant_sans_frais) * 0.0025)))
                                ]);

                                Historiquetrans::where('id',$tran->id)->update([
                                    'code_validation' =>  strval(doubleval($tran->montant_sans_frais) * 0.0025)
                                ]);
                            }else{
                                Customer::where('id', $parrain->id)->update([
                                    'solde_parrainage' => $parrain->solde_parrainage == null? '25': strval(intval($parrain->solde_parrainage) + 25)
                                ]);

                                Customer::where('id', $parrainOld->id)->update([
                                    'solde_parrainage' => $parrainOld->solde_parrainage == null? '25': strval(intval($parrainOld->solde_parrainage) + 25)
                                ]);

                                Historiquetrans::where('id',$tran->id)->update([
                                    'code_validation' => '25'
                                ]);
                            }

                        }elseif(!empty($client->phoneparent)){
                            $parrain = Customer::where('phoneclient', $client->phoneparent)->first();
                            if(strlen($tran->param8)>35){
                                Customer::where('id', $parrain->id)->update([
                                    'solde_parrainage' => $parrain->solde_parrainage == null? strval(doubleval($tran->montant_sans_frais) * 0.005)  : strval(intval($parrain->solde_parrainage) + intval(strval(doubleval($tran->montant_sans_frais) * 0.005)))
                                ]);

                                Historiquetrans::where('id',$tran->id)->update([
                                    'code_validation' => strval(doubleval($tran->montant_sans_frais) * 0.005)
                                ]);
                            }else{
                                
                                Customer::where('id', $parrain->id)->update([
                                    'solde_parrainage' => $parrain->solde_parrainage == null? '50': strval(intval($parrain->solde_parrainage) + 50)
                                ]);

                                Historiquetrans::where('id',$tran->id)->update([
                                    'code_validation' =>  '50'
                                ]);
                            }

                        }else{
                            Historiquetrans::where('id',$tran->id)->update([
                                'code_validation' =>'parrainage_off'
                            ]);
                        }
                        break;
                }
            }
            return [
                'response'=> true,
                'body'=>$trans
            ];
        }else{
            return 100;
        }
    }
    
    public function students(Request $request){
        $student = Customer::where('id',intval($request->student))->first();
        $vueFilleul = DB::select('select * from vues v WHERE v.customer in (select id from customer c where c.phoneparent = '.$student->phoneclient.')');
        $vuePetitsFIls = DB::select('select * from vues v WHERE v.customer in (select id from customer c where c.phoneparent = '.$student->phoneclient.')');
        $students = Customer::where('customer_type', 'student')->whereNull('username')->orderBy('Fonction6', 'desc')->get();
        $vuesTotal = Customer::where('customer_type', 'student')->whereNull('username')->sum('Fonction6');
        $objectif = 40000;
        $montant = 1000000;
        $a=0;
        $vues = 0;
        foreach ($students as $b){
            $a = $a+1;
            if($a<21) {
                $vues = $vues + $b->Fonction6;
            }
        }
        return view('challenge.student', compact('student','students', 'vueFilleul', 'vuePetitsFIls','objectif','montant', 'vues', 'vuesTotal'));
    }
    
    public function becomStudent(Request $request){
        $student = Customer::where('id',intval($request->id))->first();
        Customer::where('id', intval($request->id))->update([
            'customer_type' => 'student',
            //'Fonction6' => $student->Fonction6 == null? 0 : $student->Fonction6
        ]);
        return 1;
    }

    public function RemoveStudent(Request $request){
        Customer::where('id', intval($request->id))->update([
            'customer_type' => 'lambda',
        ]);
        return 1;
    }
    
    public function compteVueAuto(){
        $views = Vue::whereNull('param1')->get();
        if($views){
            foreach ($views as $view){
                $customer = Customer::where('id',$view->customer)->first();
                if($customer){
                    // compte des vues du client et de ses pères
                   /* Customer::where('id', intval($customer->id))->update(//pour le parrain
                        ["Fonction6" => $customer->Fonction6 + 1]
                    );*/

                    if($customer->phoneparent){//pour le parrain du parrain
                        $parrainOld = Customer::where('phoneclient',$customer->phoneparent)->first();
                        if($parrainOld){
                            Customer::where('id', intval($parrainOld->id))->update(
                                ["Fonction6" => $parrainOld->Fonction6 + 1]
                            );
                        }
                    }

                    if($customer->phonegrandparent){//pour le grand père du parrain
                        $parrainOldOld = Customer::where('phoneclient',$customer->phonegrandparent)->first();
                        if($parrainOldOld){
                           /* Customer::where('id', intval($parrainOldOld->id))->update(
                                ["Fonction6" => $parrainOldOld->Fonction6 + 1]
                            );*/
                        }
                    }

                    Vue::where('id', $view->id)->update(
                        ["param1" => 'compte']
                    );


                }

            }
        }
           return $views;
       
    }
    
    public function caissier(Request $request){
        $objectif = 3000;
        $montant = 1500000;

        $okala = Customer::where("code_confirm", "okala")->where('id', '>', 7601490)->get();
        $guegue = Customer::where("code_confirm", "guegue")->where('id', '>', 7601490)->get();
        $bord_mer = Customer::where("code_confirm", "bord_mer")->where('id', '>', 7601490)->get();
        $awendje = Customer::where("code_confirm", "awendje")->where('id', '>', 7601490)->get();
        $nzeng = Customer::where("code_confirm", "nzeng")->where('id', '>', 7601490)->get();
        $golf = Customer::where("code_confirm", "golf")->where('id', '>', 7601490)->get();;

        $totalInstallation = count($okala) + count($guegue) + count($bord_mer) + count($awendje) + count($golf) + count($nzeng);

        $tabs = [
            ['name' => "AWENDJE", 'value' => count($awendje)],
            ['name' => "BAS DE GUEGUE", 'value' => count($guegue)],
            ['name' => "BORD DE MER", 'value' => count($bord_mer)],
            ['name' => "OKALA", 'value' => count($okala)],
            ['name' => "GOLF", 'value' => count($golf)],
            ['name' => "NZENG AYONG", 'value' => count($nzeng)],

        ];


        return view('challenge.priximport', compact('totalInstallation', 'objectif', 'montant', 'tabs'));
    }
    
    public function caissiere(Request $request)
    {

        $caissiere = Customer::where('id', intval($request->caissiere))->first();
        Http::get('https://gampay.org/gamclients/public/api/valideCustomerPrixImportSecondChallenge?code='.$caissiere->option1);
        
        //details transaction pour une caissiere
        $transclientcaisse = Historiquetrans::where("phonevendeur", $caissiere->phoneclient)->where( 'operation','recharge_compte_gam')->where('id','>',35147058)->get();
        $totaltransclientcaisse = count($transclientcaisse);

        // transactions de chaque prix import
        $eachtranscaiss =  DB::select("SELECT * FROM historiquetrans h
        where h.phonevendeur in (SELECT  phoneclient from customer c where c.option1 ='$caissiere->option1' and type='prix_import'
       ) and h.operation='recharge_compte_gam' and h.id>35147058 ");
        $totaltranscaiss = count($eachtranscaiss);

        $objectif = 100;
        $montant = 30000;
        $quota = 100;
        
        $caissePrincipe= Customer::where('type', 'prix_import')->where('option3',1)->where('option1', $caissiere->option1)->first();
        $allcaissieres=Customer::where('type', 'prix_import')->where('option3',1)->orderBy('Fonction6', 'desc')->get();

        return view('challenge.caissiere', compact('caissiere', 'totaltransclientcaisse','totaltranscaiss','allcaissieres','montant','objectif', 'quota','caissePrincipe'));
    }

     public function hotesse(Request $request)
    {

        $hotesse = Customer::where('id', intval($request->hotesse))->first();
        $hotessesCount = Customer::where('code_confirm', $hotesse->code_confirm)->where('type', 'hotesse')->get();
        $affectation = Customer::where('option1', $hotesse->code_confirm)->first();
        if(count($hotessesCount)>1){
            $objectif = 500 * count($hotessesCount);
            $montant = 100000 * count($hotessesCount);
        }else{
            $objectif = 500 ;
            $montant = 100000 ;
        }

        //details installation pour une hotesse
        $eachpriximports =  DB::select("SELECT * FROM customer c where c.phoneclient in (SELECT phonevendeur from historiquetrans h where h.id>35147058 ) and c.code_confirm ='$hotesse->code_confirm' ");
        $totalcaiss = count($eachpriximports);

        // installation sans transaction pour une hotesse
        $installationsanstrans =DB::select("SELECT * FROM customer c where c.code_confirm ='$hotesse->code_confirm' ");
        $totalinstallsanstrans = count($installationsanstrans);

        $connexions = Customer::where('code_confirm', $hotesse->code_confirm)->whereNotNull('unlock_token')->get();
        $quota = 100;
        $allcaissieres=Customer::where('type', 'prix_import')->where('option3',1)->orderBy('Fonction6', 'desc')->get();

        return view('challenge.hotesse', compact('objectif', 'hotesse', 'montant','totalcaiss','totalinstallsanstrans', 'connexions', 'quota', 'allcaissieres', 'affectation' ));
    }
    
    public function getTransCaisse(){
        /*$caisses = Customer::where('type', 'prix_import')->get();
        foreach ($caisses as $caisse){
            $transclientcaisse = Historiquetrans::where("phonevendeur", $caisse->phoneclient)->where( 'operation','recharge_compte_gam')->where('id','>',35147058)->get();
          
        }
        
        $caisses = Customer::where('type', 'prix_import')->where('option3', 1)->get();
        foreach ($caisses as $caisse){
            $clientCaisseValidate = Customer::where('flp_account',$caisse->option1)->where('flp_point_caisse', 'valide')->get();
            
        }
        
        return $caisses;*/
        
        
        $caisses = Customer::where('type', 'prix_import')->where('option3', 1)->get();
        foreach ($caisses as $caisse){
            $clientCaisseValidate = Customer::where('flp_account',$caisse->option1)->where('flp_point_caisse', 'valide')->get();
            $clientVaisseValideVrai1 = DB::select("select DISTINCT unlock_token from customer where  flp_account ='$caisse->option1' and flp_point_caisse ='valide' and  ( unlock_token is not null and unlock_token <> 'pwa')");
            $clientVaisseValideVrai2 = DB::select("select unlock_token from customer where  flp_account ='$caisse->option1' and flp_point_caisse ='valide' and  ( unlock_token is null or unlock_token = 'pwa')");

            Customer::where('id', $caisse->id)->update([
                //'Fonction6' => count($clientVaisseValideVrai1) + count($clientVaisseValideVrai2),
                'avatar' =>strval( count($clientCaisseValidate) - count($clientVaisseValideVrai1) - count($clientVaisseValideVrai2))
            ]);
        }
        return $caisses;
    }
    
    public function removeCodeConfirmPrixImport(Request $request){
        $customers = DB::select("select * from customer c where c.code_confirm in (select option1 from customer where type = 'prix_import') and c.id <7730130 limit 100");
        if($customers){
            foreach ($customers as $customer){
                Customer::where('id', $customer->id)->update([
                    'code_confirm'=> $request->valueNull
                ]);
            }
        }
        return $customers;
    }

    public function recupClientsHotesses(Request $request){
        $trans = DB::select("SELECT * FROM historiquetrans h where h.phonevendeur in (SELECT phoneclient from customer c where c.type='prix_import') and h.operation='recharge_compte_gam' and h.param9 is null and h.id>35147058 limit 50");
        if($trans){
           foreach ($trans as $tran){
               $prixImport = Customer::where('phoneclient',$tran->phonevendeur)->first();
               Customer::where('phoneclient', $tran->numclient)->update([
                   'code_confirm'=> $prixImport->option1
               ]);
               
               Historiquetrans::where('id', $tran->id)->update([
                   'param9'=>1
               ]);
           }
        }
        return $trans;
    }
    
    
   public function getCustomerPrixImportSecondChallenge(){
        $tab = [];
        $code = DB:: select("select DISTINCT option1 from customer c where c.type = 'prix_import'");
        foreach ($code as $a){
            array_push($tab, $a->option1);
        }

        $trans = DB:: select("select * from historiquetrans h where h.phonevendeur in (select phoneclient from customer c where c.type = 'prix_import') and h.param1 is null and h.id>35324764 and h.operation = 'recharge_compte_gam' and h.reference = 'rm_3.0' order by id asc limit 10");
        if(count($trans)>0){
            foreach ($trans  as $tran){
                $partenaire = Customer::where('phoneclient', $tran->phonevendeur)->first();
                $customer = Customer::where('phoneclient', $tran->numclient)->first();
                if(!in_array($customer->flp_account , $tab)){
                    Customer::where('id', $customer->id)->update([
                        'flp_account'=>$partenaire->option1,
                        'flp_grade'=>strval($tran->id),
                    ]);
                }

                Historiquetrans::where('id', $tran->id)->update([
                    'param1'=>'prix_import_challenge2'
                ]);
            }

            return $trans;
        }
        return 0;

    }


    public function valideCustomerPrixImportSecondChallenge(Request $request){

        if(!empty($request->code)){
            $customers = DB::select("select * from customer c where c.flp_account ='$request->code' and (c.flp_point_caisse is null or c.flp_point_caisse = '0000' ) order by c.updated_at desc limit 80");
        }else{
            $customers = DB::select("select * from customer c where c.flp_account in (select DISTINCT option1 from customer c where c.type = 'prix_import') and (c.flp_point_caisse is null or c.flp_point_caisse = '0000' ) order by c.updated_at desc  limit 80");
        }
        if(count($customers)>0){
            foreach ($customers as $customer){
                $numSansZero = substr($customer->phoneclient, 1);
                $transRm = Historiquetrans::where('id', intval($customer->flp_grade))->first();
                $trans = Historiquetrans::where('phonevendeur',$customer->phoneclient)->whereIn('numclient',[$customer->phoneclient, $numSansZero])->whereIn('etat',['confirme', 'CONFIRMEE'])->where('id', '>', intval($customer->flp_grade))->get();
                if(count($trans)>0){
                    Customer::where('id', $customer->id)->update([
                        'flp_point_caisse'=>'valide',
                        'flp_grade'=>$transRm->phonevendeur,
                    ]);
                }
            }
            return $customers;
        }
        return 0;
    }
    
    public function classementFête(Request $request){
        $now = date("Y-m-d H:i:s");
        $fin = date("Y-m-d H:i:s", $request->fzqut);

        if(!empty($request->fzqut) && $fin>$now) {
            return view('challenge.classement');
        }else{
            return view('challenge.chaine');
        }
    }
   
    public function generateLink(){
        $end = strtotime("+1 day");
        return  "https://gampay.org/gamclients/public/classementPromo?validate=$end";
    }
    
    public function followers(){
        $end = strtotime("+1 day");
        return   view('challenge.chaine');
    }

    public function saveFollwers(Request $request){
        if(!empty($request->phone)){
            $follower = Followers::where('phone', $request->phone)->first();
            if(empty($follower)){
                $newFollowers = new Followers([
                    'phone'=>$request->phone
                ]);
                $newFollowers->save();
            }
            return 1;
        }else{
            return 0;
        }
    }
    
    public function help(){
        $end = strtotime("+1 day");
        return   view('challenge.help');
    }
    
    public function classementFin(Request $request){
        $pointCumuleUssd=0;
        $pointCumuleapplication=0;
        $challenger=Customer::where('id',$request->fzqut)->first();

        if($challenger){
            $pointCumuleapplication=$challenger->score;
            $customerussd=Client2::where('numclient',$challenger->phoneclient)->first();
            if($customerussd){
                $pointCumuleUssd=$customerussd->score;
            }
        }

        $first = Customer::where('score', '>', 1)->whereNull('username')->orderBy('score', 'desc')->take(2500)->get();
        $firstUssd = Client2::where('score', '>', 1)->orderBy('score', 'desc')->take(2500)->get();

        return view('challenge.classementFin', compact('first', 'firstUssd', 'pointCumuleUssd','challenger', 'pointCumuleapplication' ));
    }
    
    public function cadeaux(Request $request){
        $challenger=Customer::where('id',$request->challenger)->first();
        $agent = $request->agent;
        if($challenger){
            return redirect('https://gampay.app/gamclients/public/classementFollow?fzqut='.$challenger->id);
            //return redirect('https://gampay.app/gamclients/public/classementPromo?fzqut='.$challenger->id);
            //$cadeaux = Cadeau::where('phoneclient',$challenger->phoneclient)->whereNull('statut')->get();
            //return view('challenge.cadeaux', compact('challenger', 'cadeaux', 'agent'));
        }
        return   view('challenge.chaine');
    }
    
    
    public function distributionCadeauAgent(Request $request){
        $cadeau = Cadeau::where('id', $request->cadeau)->first();
        Cadeau::where('id', $cadeau->id)->update([
            'statut' => 'livre'
        ]);
        return 1;
    }
    
    public function challengeVille(Request $request){
        $now = date("Y-m-d H:i:s");

        $challenger=Customer::where('id',$request->fzqut)->first();

        $customers = Customer::whereNull('username')->orderBy('challenge', 'desc')->take(20)->get();
        $customer = $challenger->id;
        return view('challenge.classement', compact('customers', 'challenger', 'customer'));
    }
    
    
    public function classementPromo(Request $request){
        $now = date("Y-m-d H:i:s");
        $fin = date("Y-m-d H:i:s", $request->validate);

        if(!empty($request->validate)) {
        
            if($fin>$now){
                $classement = true;
            }else{
               return view('challenge.chaine');
            }
           
        }else{
             $classement = false;
        }
        
        $challenger=Customer::where('id',$request->fzqut)->first();
        $montant = 500000;
        //$theFirsts =  Customer::where('score', '>', 1)->whereNull('username')->orderBy('challenge', 'desc')->take(20)->get();
        $theFirsts =  Customer::whereNull('username')->where('statut', 'challenger')->orderBy('challenge', 'desc')->take(20)->get();
        $totalFirst = 0;
        foreach ($theFirsts as $customer){
            $totalFirst = $totalFirst + $customer->challenge;
        }
        $customers = Customer::whereNull('username')->where('statut', 'challenger')->orderBy('challenge', 'desc')->take(100)->get();
        $filleuls = DB::select("select * from customer where gam_card_ceated_at like '2024%' and phoneparent =$challenger->phoneclient order by created_at desc");
        $mini = DB::select("select * from customer where username is null and statut = 'challenger' and petit_fils >0 order by petit_fils desc");
        return view('challenge.classementPromo', compact( 'montant','challenger', 'customers', 'totalFirst', 'classement', 'filleuls', 'mini'));
    }
    
    public function classementQuiz(Request $request){
        $challenger=Customer::where('id',$request->fzqut)->first();
        $quota = 5;
        $customers = Customer::whereNotNull('Fonction6')->where('statut', 'quizz')->orderBy('Fonction6', 'desc')->take(10)->get();
        return view('challenge.classementQuiz', compact( 'challenger', 'customers', 'quota'));
    }
    
    // Challenge promo
    public function getPromo(Request $request){
        $VerifChallendEnd = date("Y-m-d H:i:s");
        $d = mktime(23, 59, 59, 7, 30, 2024);
        $EndChallenge = date("Y-m-d H:i:s", $d);
        $phone = $this->getNumByWhatsApp($request->whatsapp);
        $myWhatsapp = $request->whatsapp;
        $myCustomer = Customer::where(function($q) use($phone,$myWhatsapp) {
            $q->where('phoneclient', $phone)->orWhereIn('whatsapp',[$phone, $myWhatsapp]);
        })->first();
        if($myCustomer){
            if(!empty($myCustomer->username)){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Vous ne pouvez pas bénéficier de cette promotion',
                    'code' => 'stop'
                ]);
            }
            
            if(!empty($request->zone) && $request->zone!='international'){
                Customer::where('id', $myCustomer->id)->update([
                    'zone' => $request->zone,
                ]);
            }


            if(empty($request->code)){
                if(empty($myCustomer->option2)){
                    Http::get('https://gampay.app/gamclients/public/api/recupIdCustomer');
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Votre code est cours de génération',
                        'code' => 'restart'
                    ]);
                }else{
                    Customer::where('id', $myCustomer->id)->update([
                        'statut' =>'challenger',
                        'whatsapp' => $request->whatsapp
                    ]);
                    return response()->json([
                        'statut'=> true,
                        'message'=> "",
                        'code' => 'GG'.$myCustomer->option2
                    ]);
                }
            }else{

                if(!empty($myCustomer->phoneparent)){
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Vous avez déjà été parrainné',
                        'code' => 'stop'
                    ]);
                }else{
                    $myCode = substr($request->code, 2);
                    $myParrain =  Customer::where('option2', $myCode)->first();
                    if($myParrain){
                        $attente = date("Y-m-d H:i:s", strtotime('+1 day'));
                        Customer::where('id', $myCustomer->id)->update([
                            'has_flp_account'=> 'true',
                            'gam_card_ceated_at'=>$attente,
                            'phoneparent'=> $myParrain->phoneclient,
                            'phonegrandparent'=> $myParrain->phoneparent,
                            'zone' =>!empty($request->zone) && $request->zone!='international'? $request->zone:$myParrain->zone
                        ]);
                        if($VerifChallendEnd > $EndChallenge){
                            $pointChallenge = $myParrain-> challenge;
                        }else{
                            $pointChallenge = $this->calculPointChallenge(empty($myParrain->Fonction4)? 1 : $myParrain->Fonction4 + 1, $myParrain->Fonction2, $myParrain->Fonction6, $myParrain->Fonction8);
                        }
                        Customer::where('id', $myParrain->id)->update([
                            'Fonction4'=> empty($myParrain->Fonction4)? 1 : $myParrain->Fonction4 + 1,
                            'filleuls' => empty($myParrain->filleuls)? 1 : $myParrain->filleuls + 1,
                            'challenge'=> $pointChallenge,
                            'statut' =>'challenger'
                        ]);
                        $message = "Grâce à *$myParrain->nom $myParrain->prenom*, profitez de *0 frais* sur toutes nos opérations *jusqu'au $attente*";
                        return response()->json([
                            'statut'=> true,
                            'message'=> '',
                            'code' => 'go',
                            'promotion' => $message
                        ]);
                    }else{
                        return response()->json([
                            'statut'=> false,
                            'message'=> 'Code incorrect',
                            'code' => 'stop'
                        ]);
                    }
                }
            }
        }else{
            if(empty($request->code)){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Entrez le code PROMO de votre parrain',
                    'code' => 'no'
                ]);
            }else{
                $myCode = substr($request->code, 2);
                $myParrain =  Customer::where('option2', $myCode)->first();
                if($myParrain){
                    $attente = date("Y-m-d H:i:s", strtotime('+1 day'));
                    $customer = new Customer([
                        'nom'=> 'client',
                        'prenom'=> 'GAM',
                        'pays'=> '+241',
                        'phoneclient'=> $phone,
                        'solde'=>0,
                        'mdpclient'=> '1234',
                        'option1'=> $myParrain->option1,
                        'code_confirm'=> $myParrain->code_confirm,
                        'phoneparent'=> $myParrain->phoneclient,
                        'phonegrandparent'=> $myParrain->phoneparent,
                        'whatsapp'=> $request->whatsapp,
                        'gam_card_ceated_at'=>$attente,
                        'has_flp_account'=> 'true',
                        'zone' =>!empty($request->zone) && $request->zone!='international'? $request->zone: $request->zoneNull
                    ]);
                    $customer->save();
                    if($VerifChallendEnd > $EndChallenge){
                        $pointChallenge = $myParrain-> challenge;
                    }else{
                        $pointChallenge = $this->calculPointChallenge(empty($myParrain->Fonction4)? 1 : $myParrain->Fonction4 + 1, $myParrain->Fonction2, $myParrain->Fonction6, $myParrain->Fonction8);
                    }
                    Customer::where('id', $myParrain->id)->update([
                        'Fonction4'=> empty($myParrain->Fonction4)? 1 : $myParrain->Fonction4 + 1,
                        'filleuls' => empty($myParrain->filleuls)? 1 : $myParrain->filleuls + 1,
                        'challenge'=> $pointChallenge,
                        'statut' =>'challenger'
                    ]);
                    Http::get('https://gampay.app/gamclients/public/api/recupIdCustomer');
                    $message = "Grâce à *$myParrain->nom $myParrain->prenom*, profitez de *0 frais* sur toutes nos opérations *jusqu'au $attente*";
                    return response()->json([
                        'statut'=> true,
                        'message'=> '',
                        'code' => 'go',
                        'promotion' => $message
                    ]);
                }else{
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Code incorrect',
                        'code' => 'stop'
                    ]);
                }
            }
        }
    }
    
    
    public function getNewPromo(Request $request){
        $VerifChallendEnd = date("Y-m-d H:i:s");
        $d = mktime(23, 59, 59, 7, 30, 2024);
        $EndChallenge = date("Y-m-d H:i:s", $d);
        $phone = $this->getNumByWhatsApp($request->whatsapp);
        $myWhatsapp = $request->whatsapp;
        $myCustomer = Customer::where(function($q) use($phone,$myWhatsapp) {
            $q->where('phoneclient', $phone)->orWhereIn('whatsapp',[$phone, $myWhatsapp]);
        })->first();
        if($myCustomer){
            /*if(!empty($myCustomer->username)){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Vous ne pouvez pas bénéficier de cette promotion',
                    'code' => 'stop'
                ]);
            }else{
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Vous avez déjà un compte GamPay',
                    'code' => 'stop'
                ]); 
            }*/
            
            return response()->json([
                'statut'=> false,
                'message'=> 'Vous avez déjà un compte GamPay',
                'code' => 'stop'
            ]); 
        }else{
            if(empty($request->code)){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Entrez le code PROMO de votre parrain',
                    'code' => 'no'
                ]);
            }else{
                $myCode = substr($request->code, 3);
                $myParrain =  Customer::where('option2', $myCode)->first();
                if($myParrain){
                    $attente = date("Y-m-d H:i:s", strtotime('+1 day'));
                    $customer = new Customer([
                        'nom'=> 'client',
                        'prenom'=> 'GAM',
                        'pays'=> '+241',
                        'phoneclient'=> $phone,
                        'solde'=>0,
                        'mdpclient'=> '1234',
                        'option1'=> $myParrain->option1,
                        'code_confirm'=> $myParrain->code_confirm,
                        'phoneparent'=> $myParrain->phoneclient,
                        'phonegrandparent'=> $myParrain->phoneparent,
                        'whatsapp'=> $request->whatsapp,
                        'gam_card_ceated_at'=>$attente,
                        'has_flp_account'=> 'true',
                        'zone' =>!empty($request->zone) && $request->zone!='international'? $request->zone: $request->zoneNull
                    ]);
                    $customer->save();
                    if($VerifChallendEnd > $EndChallenge){
                        $pointChallenge = $myParrain-> challenge;
                    }else{
                        $pointChallenge = $this->calculPointChallenge(empty($myParrain->Fonction4)? 1 : $myParrain->Fonction4 + 1, $myParrain->Fonction2, $myParrain->Fonction6, $myParrain->Fonction8);
                    }
                    Customer::where('id', $myParrain->id)->update([
                        'Fonction4'=> empty($myParrain->Fonction4)? 1 : $myParrain->Fonction4 + 1,
                        'filleuls' => empty($myParrain->filleuls)? 1 : $myParrain->filleuls + 1,
                        'challenge'=> $pointChallenge,
                        'statut' =>'challenger'
                    ]);
                    Http::get('https://gampay.app/gamclients/public/api/recupIdCustomer');
                    $message = "Grâce à *$myParrain->nom $myParrain->prenom*, profitez de *0 frais* sur toutes nos opérations *jusqu'au $attente*";
                    return response()->json([
                        'statut'=> true,
                        'message'=> '',
                        'code' => 'go',
                        'promotion' => $message
                    ]);
                }else{
                    return response()->json([
                        'statut'=> false,
                        'message'=> 'Code incorrect',
                        'code' => 'stop'
                    ]);
                }
            }
        }
    }
    
    public function orientationPromo(Request $request){
        $phone = $this->getNumByWhatsApp($request->whatsapp);
        $myCustomer = Customer::where('phoneclient', $phone)->first();
        if($myCustomer){
            return redirect("https://app.gampay.org/?log=$myCustomer->id&v=$request->operation");
        }else{
            return redirect("https://app.gampay.org/?p=2280618&v=vue");
        }
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
    
    public function calculPointChallenge($filleus, $installation, $trafic, $folowers ){
        $calcul = $filleus + ($installation * 3) + ($trafic * 5) + $folowers;
        return $calcul;
    }
    
    public function getCustomerPromo(Request $request){
        $phone = $this->getNumByWhatsApp($request->whatsapp);
        $myWhatsapp = $request->whatsapp;
        $myCustomer = Customer::where(function($q) use($phone,$myWhatsapp) {
            $q->where('phoneclient', $phone)->orWhereIn('whatsapp',[$phone, $myWhatsapp]);
        })->first();

        if($myCustomer){
            /*if(str_contains(strval($myCustomer->username), 'gam')){
                return response()->json([
                    'statut'=> false,
                    'message'=> 'Vous ne pouvez pas bénéficier de cette promotion',
                    'code' => 'stop'
                ]);
            }*/
            $compte = $myCustomer;
            Customer::where('id', $myCustomer->id)->update([
                'whatsapp' => $request->whatsapp
            ]);
            $type = 'old';
        }else{
            $customer = new Customer([
                'nom'=> 'client',
                'prenom'=> 'GAM',
                'pays'=> '+241',
                'phoneclient'=> $phone,
                'solde'=>0,
                'mdpclient'=> '1234',
                'option1'=> 'gam',
                'code_confirm'=> 'gam',
                'whatsapp'=> $request->whatsapp,

            ]);
            $customer->save();
            $compte = $customer;
            $type = 'new';
        }
        
        $message = '';
        if($request->option == 'login'){
            $message = "Installez l'application GamPay et utilisez votre identifiant *$compte->phoneclient* et votre mot de passe *$compte->mdpclient* pour accéder à votre compte";
        }

        return response()->json([
            'statut'=> true,
            'message'=> $message,
            'code' => $compte->option2,
            'type' => $type,
            'nom' => $compte->nom.' '.$compte->prenom
        ]);

    }


    public function getOrientationPromo(Request $request){
        $myWhatsapp = $request->whatsapp;
        $phone = $this->getNumByWhatsApp($request->whatsapp);
        $myCustomer = Customer::where(function($q) use($phone,$myWhatsapp) {
            $q->where('phoneclient', $phone)->orWhereIn('whatsapp',[$phone, $myWhatsapp]);
        })->first();

        if($myCustomer){
            $challenger = $myCustomer->statut;
            switch($request->option){
                case 'transfert_visa':case 'recharge_visa':case 'esim':case 'wifi':
                $groupe = 'Dépt. International &Communication (1)';
                break;
                case 'challenger':
                    $challenger = $request->option;
                    $groupe = 'GAM Service client (3)';
                    break;
                case 'videopromo':
                    if(!str_contains(strval($myCustomer->Fonction7), 'videopromo')){
                        $message = "Le *$myWhatsapp* a commencé la procédure de mise en statut";
                        //$messageCustomer = "Cher client, vous souhaitez partager notre communication sur votre statut?! N'hésitez pas à nous contacter si vous avez des questions.";
                        //$this->sendWhatsAppMessage($myWhatsapp,$messageCustomer, 'other');
                        $this->sendWhatsAppGroupMessage($message, 'Sygma (1)', 'other');
                        Customer::where('id', $myCustomer->id)->update([
                            'Fonction7' => empty($myCustomer->Fonction7)? $request->option : $myCustomer->Fonction7 .'-'.$request->option
                        ]); 
                    }
                    return redirect("https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c/224");
                case 'abonne':
                    if(!str_contains(strval($myCustomer->Fonction7), 'abonne')){
                        $message = "Le *$myWhatsapp* a commencé la procédure d'abonnement";
                        //$messageCustomer = "Cher client, vous avez entamé la procédure d'abonnement à notre chaîne WhatsApp. N'hésitez pas à nous contacter si vous avez des questions.";
                        //$this->sendWhatsAppMessage($myWhatsapp,$messageCustomer, 'other');
                        $this->sendWhatsAppGroupMessage($message, 'Sygma (1)', 'other');
                        Customer::where('id', $myCustomer->id)->update([
                            'Fonction7' => empty($myCustomer->Fonction7)? $request->option : $myCustomer->Fonction7 .'-'.$request->option
                        ]);
                    }
                    return redirect("https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c/224");
                default:
                    $groupe = 'GAM Service client (3)';

            }
            Customer::where('id', $myCustomer->id)->update([
                'grade' =>  $request->option,
                'statut' => $challenger,
                'whatsapp' => $request->whatsapp
            ]);

            if($request->option != 'challenger'){
                return 1;
            }else{
                if($myCustomer->statut != 'challenger'){
                    $now = date('Y-m-d h:i:s');
                    Customer::where('id', $myCustomer->id)->update([
                        'confirmation_sent_at' => $now
                    ]);
                    $myOption = strval($request->option);
                    $message = "Le *$myCustomer->phoneclient* *($myCustomer->nom $myCustomer->prenom)* a choisi l'option *$myOption* sur SCYD";

                    $this->sendWhatsAppGroupMessage($message, $groupe, 'other');
                    $this->startChallengePromoTemplate($request->whatsapp,$myCustomer->id);
                }
                $message = 'https://wa.me/24102535748?text=GG/'.$myCustomer->option2;
                return redirect("https://wa.me/?text=$message");
            }
        }
    }
    
    public function startChallengePromoTemplate($whatsapp, $id){
        $numWhatsApp = $whatsapp;
        $messageId = 'promo'.time();
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
                        "templateName": "infostart",
                        "templateData": {
                          "body": {
                            "placeholders": []
                          },
                         "header": {
                            "type": "VIDEO",
                            "mediaUrl": "https://gampay.app/video_flow/status.mp4"
                          },
                          "buttons": [
                            {"type": "URL", "parameter": "'.$numWhatsApp.'&option=videopromo"},
                            {"type": "URL", "parameter": "'.$id.'"},
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

        curl_exec($curl);

        return 1;
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
       
        // Close handle
        curl_close($ch);
    }
    
    public function getStatsCustomerPromo(Request $request){
        $phone = $this->getNumByWhatsApp($request->whatsapp);
        $myWhatsapp = $request->whatsapp;
        $myCustomer = Customer::where(function($q) use($phone,$myWhatsapp) {
            $q->where('phoneclient', $phone)->orWhereIn('whatsapp',[$phone, $myWhatsapp]);
        })->first();

        if($myCustomer){
            if(empty($myCustomer->ability) || $myCustomer->ability<3){
                $montant = 500000;
                //$theFirsts =  Customer::where('score', '>', 1)->whereNull('username')->orderBy('challenge', 'desc')->take(20)->get();
                $theFirsts =  Customer::whereNull('username')->where('statut', 'challenger')->orderBy('challenge', 'desc')->take(20)->get();
                $totalFirst = 0;
                foreach ($theFirsts as $customer){
                    $totalFirst = $totalFirst + $customer->challenge;
                }
                if($totalFirst>0){
                    $gain = round(($myCustomer->challenge *$montant )/ $totalFirst);
                }else{
                    $gain = 0;
                }
                $message = "Vos statistiques: 
Gain 💰 *$gain* => Filleuls 👥 *($myCustomer->Fonction4)* ; installations 📱 *($myCustomer->Fonction2)* ; Trafic 💵 *($myCustomer->Fonction6)*; Followers 👍🏻 *($myCustomer->Fonction8)*
Plus détails dans l’onglet «*Challenge*» du menu «*MyPay*» de votre application *GamPay* (Choisir l'option *Installer GamPay*)";

                Customer::where('id', $myCustomer->id)->update([
                    'ability' =>  empty($myCustomer->ability)? 1 : $myCustomer->ability+ 1
                ]);
                
                return response()->json([
                    'statut'=> true,
                    'message'=> $message
                ]);

            }else{
                return response()->json([
                    'statut'=> true,
                    'message'=> "Retrouvez les statistiques de votre challenge dans l'onglet *Challenge* du menu *MyPay* de votre application *GamPay*"
                ]);
            }

        }else{
            return response()->json([
                'statut'=> false,
                'message'=> "Nous n'avons pas réussi à récupérer vos données" ,
            ]);
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
       
        // Close handle
        curl_close($ch);
    }
    
    public function updatedZoneCustomer(Request $request){
        if($request->zone != 'international' &&  $request->zone != 'autre'){
            $tab =  explode('-', $request->zone);
            Customer::where('id', $request->challenger)->update([
                'contry_code' => $tab[0],
                'zone' => $tab[1].'/'.$tab[2],
                'city' => $tab[2]
            ]);
        }else{
            Customer::where('id', $request->challenger)->update([
                'city' => $request->zone
            ]);  
        }
        //return redirect('https://gampay.app/gamclients/public/classementPromo?fzqut='.$request->challenger);
        return redirect('https://gampay.app/gamclients/public/classementQuiz?fzqut='.$request->challenger);
    }
    
    public function classementFollow(Request $request){
        $challenger=Customer::where('id',$request->fzqut)->first();
        $montant = 100000;
        $objectif = 1000;
        $obtenu = 0;
        $price =  $montant/$objectif;
        $quota = 10;

        $customers = Customer::whereNull('username')->whereNotNull('challenge')->where('challenge', '>', 0)->where('statut', 'challenger')->orderBy('challenge', 'desc')->get();
        //$customers = Customer::whereIn('phoneclient', ['074582442', '074595435', '074071340'])->where('challenge', '>', 0)->orderBy('challenge', 'desc')->take(5)->get();

        foreach ($customers as $customer){
            $obtenu = $obtenu + $customer->challenge;
        }
        if($obtenu>0){
            $reste = $montant - (($obtenu*$montant)/$objectif);
        }else{
            $reste = $montant;
        }
        $avancement = ($obtenu * 100)/ $objectif;
        $filleuls = DB::select("select * from customer where gam_card_ceated_at like '2024-08%' and phoneparent =$challenger->phoneclient order by created_at desc");
        return view('challenge.classementFollow', compact( 'montant','challenger', 'customers', 'obtenu', 'objectif', 'price', 'reste', 'quota', 'filleuls', 'avancement'));
    }
    
    /************************************************ Ambassadeurs GamOs ******************************************************/

    /*** parrainnage ambassadeur ***/

    public function sharedLinkAmbassadeur(Request $request)
    {
        return redirect("https://wa.me/24105949831?text=secureRSbyGAM$request->myCode");
    }
    public function parrainageGamOs(Request $request)
    {
        $attente = date("Y-m-d H:i");
        $myCustomer = Customer::where('phoneclient', $request->phone)->first();
        $myCode = substr($request->code, 13);
        $messageSend = "Un bonus de 500f de crédit en plus vous attend... Étape 1.Cliquez sur *Mettre en statut*. NB : Étape 2.*Partagez nous une capture d'écran de votre statut une fois que vous avez obtenu 20 vues*. Pour rendre votre expérience encore plus agréable, sécurisez dès maintenant vos réseaux sociaux. Suivez nous sur WhatsApp 👉🏽 https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c/408";
        if ($myCustomer) {
            if (!empty($myCustomer->phoneparent) || str_contains($myCustomer->option1,'filleul_ambassadeur')) {
                $messageSend = "Désolé, vous avez déjà été parrainné";
                $send = $this->sendMessageForPartageInStatus($request->whatsapp,$messageSend , '');
                return $send;
            }

            $myParrain =  Customer::where('id', $myCode)->first();
            if ($myParrain) {
                Customer::where('id', $myCustomer->id)->update([
                    'has_flp_account' => 'true',
                    'expirationparrainage' => $attente,
                    'whatsapp' => $request->whatsapp,
                    'phoneparent' => $myParrain->phoneclient,
                    'option1' => empty($myCustomer->option1) ? 'filleul_ambassadeur' : $myCustomer->option1 . '_filleul_ambassadeur',
                ]);

                Customer::where('id', $myParrain->id)->update([
                    'filleuls' => empty($myParrain->filleuls) ? 1 : $myParrain->filleuls + 1,
                ]);
            }
        } else {
            $myParrain =  Customer::where('id', $myCode)->first();
            if ($myParrain) {
                $customer = new Customer([
                    'nom' => 'client',
                    'prenom' => 'GAM',
                    'pays' => '+241',
                    'phoneclient' => $request->phone,
                    'solde' => 0,
                    'mdpclient' => '1234',
                    'option1' => 'filleul_ambassadeur',
                    'code_confirm' => $myParrain->code_confirm,
                    'phoneparent' => $myParrain->phoneclient,
                    'phonegrandparent' => $myParrain->phoneparent,
                    'whatsapp' => $request->whatsapp,
                    'expirationparrainage' => $attente,
                    'has_flp_account' => 'true',
                ]);
                $customer->save();

                Customer::where('id', $myParrain->id)->update([
                    'filleuls' => empty($myParrain->filleuls) ? 1 : $myParrain->filleuls + 1,
                ]);
            }
        }

        $send = $this->sendMessageForPartageInStatus($request->whatsapp,$messageSend , $myParrain->phoneclient);
        
        return $send;
        return redirect("https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c/224");
        //return redirect('https://wa.me/24102819111?text=#secureRSbyGAM@' . $myCode);
    }

    public function sendMessageForPartageInStatus($whatsapp, $message, $parrain)
    {
        $phone = $this->getNumByWhatsApp($whatsapp);
        $messageId = 'msgPartageStatus' . time();
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
                    $id = $response['messages'][0]['id'];
                    Http::get("https://gampay.org/gamclients/public/api/createFlowBackupRequest?messageId=$id&accoundId=$messageId&phone=$phone&parrain=$parrain");
                    $this->sendImage($whatsapp, $phone);
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
    
    public function sendImage($whatsapp,$phone)
  {
     
    $messageId = 'imagePartageStatus' . time();
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
            "type": "image",
            "image": {
                "link": "https://gampay.org/image_flow/affiche_de_communication1.png"
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

    public function getInfoChallengeForCustomer(Request $request)
    {
        $customer = Customer::where('id', intval($request->challenger))->first();
        if ($customer) {
            if(!empty($request->search)){
                $search = $request->search;
                $users = Customer::where(function($q) use( $search) {
                    $q->where('phoneclient', 'like', '%'.$search.'%')->orWhere('nom', 'like', '%'.$search.'%')->orWhere('prenom', 'like', '%'.$search.'%')->orWhere('pseudo', 'like', '%'.$search.'%')->orWhere('whatsapp', 'like', '%'.$search.'%')->orWhere('created_at', 'like', '%'.$search.'%');
                })->where('phoneparent', $customer->phoneclient)->orderBy('created_at', 'desc')->take(20)->get();
            }else{
              $users = Customer::where('phoneparent', $customer->phoneclient)->where('option1','like', "%filleul_ambassadeur%")->get();  
            }
            $d = mktime(10, 00, 00, 07, 13, 2026);
            $startChallenge = date("Y-m-d H:i:s", $d);
            return response()->json([
                'statut' => true,
                'objectif' => "1000",
                'montantAGagner' => "1000",
                'dateStartChallenge' => $startChallenge,
                //'count_filleuls' => $customer->filleuls,
                'body'=> $users
                //'message' => "Vos statistiques: Filleuls 👥 *($customer->filleuls)*"
            ]);
        } else {
            return response()->json([
                'statut' => false,
                'message' => 'Client non trouvé'
            ]);
        }
    }

    public function getPriceProduct($produit)
    {
        $amount = 0;
        switch ($produit) {
            case 'recharge_visa_uba':
                $amount = 1000; // Example price, replace with actual price
                break;
                // Add more cases for other products
        }
        return $amount;
    }

    public function calculGainParrainageGamOs(Request $request)
    {
        $customer = Customer::where('phoneclient', $request->phone)->whereNotIn('expirationparrainage', ['off'])->first();
        if ($customer) {
            if (str_contains(strval($customer->option1), 'filleul_ambassadeur')) {
                if ($customer->expirationparrainage < date("Y-m-d H:i:s")) {
                    $customer->update([
                        'expirationparrainage' => 'off'
                    ]);
                    return response()->json([
                        'statut' => false,
                        'message' => 'Votre parrainage a expiré'
                    ]);
                }

                $myParrain =  Customer::where('phoneclient', $customer->phoneparent)->first();

                if ($myParrain) {
                    $debut = $customer->phoneparent[0] . $customer->phoneparent[1];
                    //return $debut;
                    if ($debut == '07') {
                        $operateur = "AIRTEL MONEY";
                    } else {
                        $operateur = "MOBICASH";
                    }

                    $historiqueTrans = new Historiquetrans([
                        'operation' => "transfert_mobile",
                        'reference' => 'AUTO',
                        'etat' => "attend",
                        'numclient' => $customer->phoneparent,
                        'phonevendeur' => $customer->phoneparent,
                        'content' => $operateur,
                        'montant' => strval($request->frais),
                        'montant_sans_frais' => $request->frais,
                        'origine_operation' => "recompense_parrainage_ambassadeur",
                        'id_customer' => $myParrain->id,
                    ]);

                    $historiqueTrans->save();
                    $myParrain->update([
                        'challenge' => $myParrain->challenge == 0 ? $request->frais : $myParrain->challenge + strval($request->frais)
                    ]);
                }
            }
        }
    }
    
    // Challenge USSD
    public function getPointTombola($price,$top)
    {
        $point = 0;
        if ($price >= 100 && $price <= 199) {
            return $point;
        }

        switch ($price) {
            case 1200:
                $point = $top == false ? 2 : 4;
                break;
            case 2200:
                $point = $top == false ? 3 : 6;
                break;
            case 5200:
                $point = $top == false ? 5 : 10;
                break;
            case 10200:
                $point = $top == false ? 10 : 20;
                break;
            default:
                $point = $top == false ? 0 : 0;
        }
        return $point;
    }

    public function participationTombola(Request $request)
    {
        $now = date('Y-m-d H:i:s'); // 'H' majuscule pour le format 24h
        if(in_array($request->montant,[1200,2200,5200,10200])){
            $point = Client2::where('numclient', '074071340')->first();
            if ($point) {
                if ($point->challenge < 1800) {
                    //Http::get("https://gampay.app/gamclients/public/api/participationTombola?phonevendeurChallenge=074071340&numclientChallenge=06264339&montant=100");
                    $cumul = 0;
                    $status = false;
                    $top = false;
                    $customer = Client2::whereIn('classement', [$request->numclientChallenge, '0' . $request->numclientChallenge])->first();
                    if (empty($customer)) {
                        $status = true;
                        $customer = Client2::where('numclient', $request->phonevendeurChallenge)->first();
                    }

                    if ($customer) {
                        if ($customer->numclient != $request->phonevendeurChallenge) {
                            $top = true;
                        }

                        $cumul = $this->getPointTombola($request->montant, $top);

                        $name = $customer->name;
                        if (empty($name)) {
                            $paymentController = new PaymentController();
                            $requesName =  $paymentController->getInfoCustomerPvitIn('airtel', $customer->numclient);
                            if ($requesName != null && $requesName != 'error') {
                                $goodName = explode(" ", $requesName);
                                $name = $goodName[0];
                            }
                        }

                        $customer->update([
                            'classement' => $status == true ? $request->numclientChallenge : $customer->classement,
                            'challenge' => $customer->challenge + $cumul,
                            'name' => $name
                        ]);

                        if ($customer->challenge >= 0) {
                            $point->update([
                                'challenge' => $point->challenge + 2
                            ]);
                        }
                    }

                    return [$status, $customer];
                }
            }
        }
    }
    
    public function participationTombolaTest(Request $request)
    {
        if(in_array($request->montant,[1200,2200,5200,10200])){
            $point = Client2::where('numclient', '074071340')->first();
            if ($point) {
                if ($point->challenge < 1800) {
                    //Http::get("https://gampay.app/gamclients/public/api/participationTombola?phonevendeurChallenge=074071340&numclientChallenge=06264339&montant=100");
                    $cumul = 0;
                    $status = false;
                    $top = false;
                    $customer = Client2::whereIn('classement', [$request->numclientChallenge, '0' . $request->numclientChallenge])->first();
                    if (empty($customer)) {
                        $status = true;
                        $customer = Client2::where('numclient', $request->phonevendeurChallenge)->first();
                    }
    
                    if ($customer) {
                        if ($customer->numclient != $request->phonevendeurChallenge) {
                            $top = true;
                        }
    
                        $cumul = $this->getPointTombola($request->montant, $top);
    
                        $name = $customer->name;
                        if (empty($name)) {
                            $paymentController = new PaymentController();
                            $requesName =  $paymentController->getInfoCustomerPvitIn('airtel', $customer->numclient);
                            if ($requesName != null && $requesName != 'error') {
                                $goodName = explode(" ", $requesName);
                                $name = $goodName[0];
                            }
                        }
                        
                        $customer->update([
                            'classement' => $status == true ? $request->numclientChallenge : $customer->classement,
                            'challenge' => $customer->challenge + $cumul,
                            'name' => $name
                        ]);
                    }
    
                    return [$status, $customer];
                }
            }
        }else{
            return "Pas de pack";
        }
    }

    public function getParticipationTombola(Request $request){
        $customers = Client2::whereNotNull('classement')->orderByDesc('challenge')->take(20)->get();
        $point = Client2::where('numclient','074071340')->first();
        $account = null;
        if(!empty($request->user)){
           $account = Client2::where('classement', $request->user)->first();  
        }
        return response()->json([
            'statut' => true,
            'attendu' => 1800,
            'user' => $account,
            'en_cours'=> $point->challenge,
            'count' => count($customers),
            'body' => $customers,
            'user' => $account,
        ]);
    }
    
    public function getLotChallenge(Request $request)
    {
        $rang = 0;
        $lot = Lot::whereNotNull('status')->get();
        if(!empty($request->phone)){
            $rang = DB::select("SELECT rang FROM (SELECT numclient, ROW_NUMBER() OVER (ORDER BY challenge DESC) AS rang FROM client2) AS client2 WHERE numclient = $request->phone");
        }
        return response()->json([
            'statut' => true,
            'rang'=> $rang,
            'body' => $lot
        ]);
    }
    
}
