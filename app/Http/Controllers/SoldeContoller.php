<?php

namespace App\Http\Controllers;
use App\Models\Soldes;
use App\Models\Historiquetrans;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\returnArgument;

class SoldeContoller extends Controller
{
    
    public function checkSoldes(){
        $mesSoldes = Soldes::whereIn('produit', ['AM', 'MM', 'CA', 'CL', 'FA', 'GAB', 'FI', 'VISA', 'EDAN'])->get();
        foreach ($mesSoldes as $solde) {
                switch ($solde->produit) {
                    case 'AM':
                        if ($solde->solde <= $solde->param2) {

                            return 'Airtel money';
                        }
                        break;
                    case 'MM':
                        if ($solde->solde <= $solde->param2) {
                            return 'Moov money';
                        }
                        break;
                    case 'CA':
                        if ($solde->solde <= $solde->param2) {
                            return 'Crédit Airtel';
                        }
                        break;
                    case 'CL':
                        if ($solde->solde <= $solde->param2) {
                            return 'Crédit Libertis';
                        }
                        break;
                    case 'GAB':
                        if ($solde->solde <= $solde->param2) {
                            return 'GAB';
                        }
                        break;
                    case 'EDAN':
                        if ($solde->solde <= $solde->param2) {
                            return 'EDAN';
                        }
                        break;
                    case 'FI':
                        if ($solde->solde <= $solde->param2) {
                            return 'Forfait international';
                        }
                        break;
                    default:
                        if ($solde->solde <= $solde->param2) {
                            return 'Forfait Airtel';
                        }
                        break;
                }
        }
        return 0;
    }

    public function rechargeSoldes(Request $request){
        
        $sold= Soldes::where('id', $request->produit)->first();
        Soldes::where('id', $request->produit)->update([
            'solde'=>intval($sold->solde) + intval($request->montant),
        ]);
        return 1;
    }

    public function getInfoProduit(Request $request){
        $now = empty($request->dateNow)? strval(date('Y-m-d')): $request->dateNow ;
        $frais = 0;
        $vente = 0;
        $countTrans = 0;
        $produitName = '';
        $trans = [];
        $solde= Soldes::where('id', intval($request->id))->first();
        switch ($request->produit){
            case 'achat_credit':
                if($request->contentOperation == 'LIBERTIS'){
                    $produitName = 'Credit Libertis';
                }else if($request->contentOperation == 'AIRTEL_GA'){
                    $produitName = 'Credit Airtel';
                }else{
                    $produitName = 'Credit International';
                }
                $trans = Historiquetrans::where('operation', 'achat_credit')->where('content', $request->contentOperation)->whereNotIn('etat', ['attend', 'annule', 'annulé', 'rembourse', 'en_agence_attend'])->where('created_at', 'like', "$now%")->get();
            break;

            case 'recharge_visa_uba':
                 $produitName = 'Recharges VISA';
                $trans = Historiquetrans::where('operation', 'recharge_visa_uba')->whereNotIn('etat', ['attend', 'annule', 'annulé', 'rembourse', 'en_agence_attend'])->where('created_at', 'like', "$now%")->get();
            break;
            case 'achat_forfait':
                $produitName = 'Forfait Airtel';
                $trans = Historiquetrans::where('operation', 'achat_forfait')->whereNotIn('etat', ['attend', 'annule', 'annulé', 'rembourse', 'en_agence_attend'])->where('created_at', 'like', "$now%")->get();
            break;

            case 'transfert_mobile':
                if($request->contentOperation == 'MOBICASH'){
                    $produitName = 'Transferts vers Mobicash';
                }else if($request->contentOperation == 'AIRTEL MONEY'){
                    $produitName =  'Transferts vers Airtel Money';
                }else{
                    $produitName = 'Transferts GAM';
                }
                $trans = Historiquetrans::whereIn('operation', ['transfert_mobile', 'transfert_visa', 'rendu_monnaie_simple'])->where('content', $request->contentOperation)->whereNotIn('etat', ['attend', 'annule', 'annulé', 'rembourse', 'en_agence_attend'])->where('created_at', 'like', "$now%")->get();
            break;
            
            case 'international':
                $produitName = 'Forfaits internationaux';
                $trans = Historiquetrans::whereIn('operation', ['forfait_international', 'sim_international', 'esinm_international'])->whereNotIn('etat', ['attend', 'annule', 'annulé', 'rembourse', 'en_agence_attend'])->where('created_at', 'like', "$now%")->get();
            break;

            case 'GAB':
                $produitName = 'Operations GAB ';
                $trans = Historiquetrans::where('origine_operation', 'GAB')->where('created_at', 'like', "$now%")->get();
                break;

        }

        $credit = 0;
        $forfait = 0; $visa = 0;
        $edan = 0; $transfert = 0;


        if(count($trans)>0){
            foreach ($trans as $tran) {
                $frais = $frais + intval($tran->frais);
                $vente = $vente + intval($tran->montant_sans_frais);
                switch ($tran->operation){
                    case 'achat_credit':
                        $credit = $credit + intval($tran->montant_sans_frais);;
                        break;
                    case 'achat_forfait':
                        $forfait = $forfait + intval($tran->montant_sans_frais);;
                        break;
                    case 'recharge_visa_uba':
                        $visa = $visa + intval($tran->montant_sans_frais);;
                        break;
                    case 'transfert_mobile': case 'transfert_visa':
                    $transfert = $transfert + intval($tran->montant_sans_frais);;
                        break;
                }
            }
           
            $countTrans = count($trans);
        }else{
            $trans = 0;
        }

        return response()->json([
            'statut'=> true,
            'operation'=> $produitName,
            'frais'=>$frais,
            'vente'=> $vente,
            'trans'=>$trans,
            'countTrans' =>$countTrans,
            'solde'=> $solde? $solde->solde : 0,
            'soldeDepart' => $solde? $solde->param1 : 0,
            'dataSolde' =>$solde,
            'detailsTrans' => [
                'credit'=>$credit,
                'visa'=>$visa,
                'forfait'=>$forfait,
                'transfert'=>$transfert,
                'edan'=>$edan
            ]
        ]);
    }
    
    public function getSoldes(Request $request){
        $jour = empty($request->dateNow)? strval(date('Y-m-d')): $request->dateNow ;
        $frais = 0;
        $vente = 0;
        $mesSoldes = Soldes::orderby('updated_at', 'desc')->get();
        $trans = Historiquetrans::whereNotIn('operation', ['recharge_compte_gam', 'recieve_gam_transfert', 'emit_gam_transfert', 'gam_transfert', 'demande_solde'])->whereNotIn('etat', ['attend','atend', 'annule', 'annulé', 'rembourse', 'en_agence_attend'])->where('created_at', 'like', "$jour%")->get();
        if(count($trans)>0){
            foreach ($trans as $tran){
                $frais = $frais + intval($tran->frais);
                $vente = $vente +  intval($tran->montant_sans_frais);
            }
        }
        
        return response()->json([
            'soldes'=> $mesSoldes,
            'nbrTrans' => count($trans),
            'frais' => $frais,
            'vente' => $vente,
            'trans' => $trans
        ]);
    }
    
    public function soldeMoov(){
        $solde= Soldes::where('produit', 'MM')->first();
        $trans = Historiquetrans::where('content', 'MOBICASH')->whereIn('etat', ['CONFIRMEE', 'demande'])->where('id','>=', '36528186')->whereNull('code_validation')->take(5)->get();
        foreach ($trans as $tran) {
            if (doubleval($solde->solde) > doubleval($tran->montant_sans_frais)) {
                Historiquetrans::where('id', $tran->id)->update([
                    'code_validation'=>1,
                    'etat'=>'CONFIRMEE'
                ]);
                $newSolde = $solde->solde - doubleval($tran->montant_sans_frais);
                $solde->update([
                    'solde'=> $newSolde,
                ]);
            }

        }
        return $trans;
    }
    
    public function alerteSoldesOperations(Request $request){
        $soldes = Soldes::where('param3', $request->operation)->get();
        if(count($soldes) > 0){
            foreach ($soldes as $solde){
                $servicesController = new ServicesController();
                if($solde->solde < intval($request->seuil)){
                    $customers = DB::select("select * from customer where phoneclient in ('074582442', '074119984', '076520541', '074071340')");
                    $message = "Le solde $solde->param5 est de $solde->solde FCFA";
                    if(count($customers)){
                        foreach ($customers as $customer){
                            $servicesController->sendWhatsAppMessage($customer->whatsapp,$message, 'other');
                        }
                    }
                }
            }
        }
        return $soldes;
    }

}
