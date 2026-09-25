<?php

namespace App\Http\Controllers;

use App\Models\Historiquetrans;
use App\Models\Customer;
use App\Models\ProgramSubscription;
use Illuminate\Http\Request;

class ObjectifController extends Controller
{
     public function getObjectifTeam($team, $object, $effectif){
         $color= '';
         $tabclientTeam = Array();
         $transTeams = Array();
         $achatCeditTeams=Array();
         $achatForfaitTeams=Array();
         $achatEdanTeams=Array();
         $achatVisaTeams=Array();
         $tranfertTeams=Array();
         $renduMonnaieTeams=Array();

         $clientsTeams = ProgramSubscription::where('param2', $team)->where('created_at','>=', '2021-09-14 08:16:40')->get();

         foreach ($clientsTeams as $clientsTeam){
             array_push($tabclientTeam,$clientsTeam['phoneclient']);
         }
         $transactionTeams = Historiquetrans::where('operation', '!=', 'recharge_compte_gam')->where('created_at','>=', '2022-01-01 16:20:44')->get();

        foreach ($transactionTeams as $transactionTeam){
            if (!in_array($transactionTeam['phonevendeur'],$tabclientTeam )){
                array_push($transTeams,$transactionTeam);
                switch ($transactionTeam['operation']){
                    case "achat_credit":
                        array_push($achatCeditTeams,$transactionTeam);
                        break;
                    case "achat_forfait"||"achat_forfait_profit" ||"achat_forfait_profit_parrainage"||"achat_forfait_bonus" :
                        array_push($achatForfaitTeams,$transactionTeam);
                        break;
                    case "achat_unites_electrique":
                        array_push($achatEdanTeams,$transactionTeam);
                        break;
                    case "recharge_visa_uba":
                        array_push($achatVisaTeams,$transactionTeam);
                        break;

                    case "emit_gam_transfert"||"recieve_gam_transfert":
                        array_push($tranfertTeams,$transactionTeam);
                        break;

                    case "rendu_monnaie_simple":
                        array_push($renduMonnaieTeams,$transactionTeam);
                        break;
                }
            }
        }
        $calPourcentageTeam = count($transTeams)*100/$object;
        $pourcentageTeam = number_format($calPourcentageTeam, 1, '.', '');

        if ($pourcentageTeam<45){
            $color= '#c80000';
        }elseif($pourcentageTeam>=45 && $pourcentageTeam>60){
            $color= 'FA4E47FF';
        }elseif($pourcentageTeam>=60 && $pourcentageTeam>75){
            $color= '#FF6400';
        }elseif($pourcentageTeam>=75 && $pourcentageTeam>85){
            $color= '#c1c800';
        }elseif($pourcentageTeam>=85 ){
            $color= '#43c800';
        }
         $gain = 200000*$pourcentageTeam/100;
        $statTeam = [
            'nom'=>$team,
            'effectif'=>$effectif,
            'colorPourcentage'=>$color,
            'objectif'=>$object,
            'transTeam'=>$transTeams,
            'pourcentageTeam'=>$pourcentageTeam,
            'creditTeam'=>$achatCeditTeams,
            'forfaitTeams'=>$achatForfaitTeams,
            'edanTeams'=>$achatEdanTeams,
            'tranfertTeams'=>$tranfertTeams,
            'visaTeam'=>$achatVisaTeams,
            'renduMonnaieTeam'=>$renduMonnaieTeams,
            'gainTeam'=>$gain,
        ];

        return $statTeam;
    }

    public function statgam(){
        $achatCeditTeams=Array();
        $achatForfaitTeams=Array();
        $achatEdanTeams=Array();
        $achatVisaTeams=Array();
        $tranfertTeams=Array();
        $renduMonnaieTeams=Array();

        $transactionTeams = Historiquetrans::where('operation', '!=', 'recharge_compte_gam')->where('created_at','>=', '2022-01-01 16:20:44')->get();

        foreach ($transactionTeams as $transactionTeam){
                switch ($transactionTeam['operation']){
                    case "achat_credit":
                        array_push($achatCeditTeams,$transactionTeam);
                        break;
                    case "achat_forfait"||"achat_forfait_profit" ||"achat_forfait_profit_parrainage"||"achat_forfait_bonus" :
                        array_push($achatForfaitTeams,$transactionTeam);
                        break;
                    case "achat_unites_electrique":
                        array_push($achatEdanTeams,$transactionTeam);
                        break;
                    case "recharge_visa_uba":
                        array_push($achatVisaTeams,$transactionTeam);
                        break;

                    case "emit_gam_transfert"||"recieve_gam_transfert":
                        array_push($tranfertTeams,$transactionTeam);
                        break;

                    case "rendu_monnaie_simple":
                        array_push($renduMonnaieTeams,$transactionTeam);
                        break;
                }
        }
        $calPourcentageTeam = count($transactionTeams)*100/50000;
        $pourcentageTeam = number_format($calPourcentageTeam, 1, '.', '');

         if ($pourcentageTeam<45){
            $color= '#c80000';
        }elseif($pourcentageTeam>=45 && $pourcentageTeam>60){
            $color= 'FA4E47FF';
        }elseif($pourcentageTeam>=60 && $pourcentageTeam>75){
            $color= '#FF6400';
        }elseif($pourcentageTeam>=75 && $pourcentageTeam>85){
            $color= '#c1c800';
        }elseif($pourcentageTeam>=85 ){
            $color= '#43c800';
        }


        $statTeam = [
            'nom'=>'GAM SERVICE',
           'colorPourcentage'=>'blue',
            'objectif'=>50000,
            'transTeam'=>$transactionTeams,
            'pourcentageTeam'=>$pourcentageTeam,
            'creditTeam'=>$achatCeditTeams,
            'forfaitTeams'=>$achatForfaitTeams,
            'edanTeams'=>$achatEdanTeams,
            'tranfertTeams'=>$tranfertTeams,
            'visaTeam'=>$achatVisaTeams,
            'renduMonnaieTeam'=>$renduMonnaieTeams,

        ];

        return $statTeam;
    }

    public function getObjectifs(Request $request){
        $uri = $request->path();
        $statGams= $this->statgam();
        $statWinners = $this->getObjectifTeam('winner',25702, 6);
        $statGladiators = $this->getObjectifTeam('gladiators', 4286, 1);
        $statsSigmas = $this->getObjectifTeam('sigma',20000, 4);
        $statTeams= Array();
        $dernier= Array();


        array_push($statTeams,$statWinners);

        array_push($statTeams,$statsSigmas);
        array_push($statTeams,$statGladiators);


        return view('objectif', compact('statTeams','statGams', 'uri'));
    }
    
    
    public function clientCom(Request $request){
        $coms = Customer::where('id', intval($request->login))->get();
        $now = new \DateTime();
        $tabClient = Array();
        $nbrTrans = 0;
        $mois = $now->format('m');
        if(strval($mois) == '02'){
            $dateDebut = '2022-'.$mois.'-01 00:00:00';
            $dateFin = '2022-'.$mois.'-28 00:00:00';
        }else{
            $dateDebut = '2022-'.$mois.'-01 00:00:00';
            $dateFin = '2022-'.$mois.'-28 00:00:00';
        }
        $idCom = '';
        foreach ($coms as $com){
            $idCom = $com['id'];
        }
        $clients = Customer::where('option1',$idCom)->where('created_at','>=', $dateDebut)->orderBy('score', 'desc')->get();
        foreach ($clients as $client){
            array_push($tabClient, $client['phoneclient']);
        }

        $trans = Historiquetrans::where('created_at','>=', $dateDebut)->get();
        foreach ($trans as $tran){
            if(in_array($tran['phonevendeur'], $tabClient)){
                $nbrTrans = $nbrTrans+1;
            }
        }
        $uri = $request->path();

        return view('clientCom', compact(['nbrTrans', 'clients', 'trans', 'dateDebut', 'dateFin', 'coms', 'tabClient', 'uri', 'dateDebut' ]));

    }

  
}
