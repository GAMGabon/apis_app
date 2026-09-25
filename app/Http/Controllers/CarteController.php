<?php

namespace App\Http\Controllers;

use App\Models\Carte;
use App\Models\CarteAnnuaire;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Historiquetrans;
use Illuminate\Http\Request;
use App\Models\SimInternal;
use DateTime;
use Illuminate\Support\Facades\DB;


class CarteController extends Controller
{
    // ajout d'une carte commande et encienne carte
    
    public function addCard(Request $request){
        $solde = $request->solde;
       
        if($request->bank == 'UBA'){
           //$price = 10000;
           $price = 10000;
           $mybank = 'UBA';
        }else{
            return response()->json([
               'statut'=> false,
               'body' => 'Les services ORABANK sont momentanément indisponibles',
            ]);
           $price = 11900;
           $mybank = 'ORABANK';
        }
       /*$veriCarte = Carte::where('id_customer', $request->idCust)->where('owner',$mybank)->get();

        if(count($veriCarte)>3){
            return response()->json([
               'statut'=> false,
               'body' => 'Vous ne pouvez avoir plus d\'une carte '.$mybank.' rattachée à votre compte',
            ]);
        }*/
       
        if($request->type == 'commande'){
            
            /*return response()->json([
                'statut'=> false,
                'body' => 'Le service est momentanément indisponible',
            ]);*/
            
            if($request->bank != 'UBA'){
                return response()->json([
                    'statut'=> false,
                    'body' => 'Le service ORABANK est momentanément indisponible',
                ]);
            }
            
            $newCarte = new Carte([
                'owner' => $request->bank,
                'names' => ($request->names == 'GAM') ? 'Client GAM' : $request->names,
                'state'=> 'false',
                'treatment'=> "paiement",
                'price'=> $price,
                'id_customer'=> $request->idCust,
                'order_where'=> "flutterApp",
                'id_order' => !empty($request->id_order) ? "GAM":null,
                'comment'=> empty($request->motif)?"Achat en cours":$request->motif,
                'param1' => $request->piece,
            ]);

            $newCarte->save();
            return response()->json([
                'statut'=> true,
                'body' => 'Votre commande a été enregistrée, Vous pouvez procéder au paiement',
                'idCard'=> $newCarte->id,
                'price'=> $newCarte->price,
                'nom'=> $request->names,
                'bank'=> $request->bank
            ]);
        }else{
            $cartes = Carte::whereIn('id_client',[ $request->account, '00'.$request->account])->get();
            if(count($cartes)>0){
                return response()->json([
                    'statut'=> false,
                    'body' => 'Un autre utilisateur utilise déjà cette carte',
                ]);
            }else{
                if($request->bank == 'UBA'){
                    $numCartes = '4572********'.substr($request->nums, 12, 16);
                }else{
                    $numCartes = $request->nums;  
                }
                $nowDate = date('Y-m-d H:m:s');
                //enregistrons la carte dans carte_annuaire
                $newAnnuaire = new CarteAnnuaire([
                    'num_carte' => $numCartes,
                    'quatre_chiffres'=>substr($request->nums, 12, 16) ,
                    'account_number'=>$request->account,
                    'date'=>$request->expired,
                    'state'=>"enable",
                    'id_customer'=>$request->idCust,
                    'param2'=>$request->phone,
                    'param4'=>$request->bank,
                    'param7'=>"old_card",
                ]);
                if($newAnnuaire->save()){
                    // enregistrement dans carte information
                    $newCarte = new Carte([
                        'owner' => $request->bank,
                        'names' => $request->names,
                        'num_card'=> $numCartes,
                        'pseudo_card'=> strtoupper($request->names),
                        'state'=> 'true',
                        'treatment'=> "confirme",
                        'price'=> $price,
                        'expire_by'=>$request->expired,
                        'id_customer'=> $request->idCust,
                        'order_where'=> "GamPay",
                        'paiement_way'=>'{"way":"agence","montant":"'.$price.'","id_buyer":"client","day_payment":"'.$nowDate.'","customer_old_solde: "'.$solde.'", "'.$solde.'}',
                        'comment'=>"le client avait déjà sa carte et ne l'a pas achété sur l'interface",
                        'id_client'=> $request->account,
                        'param1' => 'confirme',
                        'param2' =>'old_card',
                    ]);
                    if($newCarte->save()){
                        // Ajout des ID
                        CarteAnnuaire::find($newAnnuaire->id)->update([
                            'id_carte_information'=>$newCarte->id
                        ]);

                        Carte::find($newCarte->id)->update([
                            'param3'=>$newAnnuaire->id
                        ]);
                        return response()->json([
                            'statut'=> true,
                            'body' => 'Votre carte a été ajoutée',
                        ]);
                    }else{
                        return response()->json([
                            'statut'=> false,
                            'body' => 'Une erreur est survenue lors du chargement de vos données.',
                        ]);
                    }
                }else{
                    return response()->json([
                        'statut'=> false,
                        'body' => 'Une erreur est survenue lors du chargement de vos données.',
                    ]);
                }
           }
        }
    }


    public function validePaiement(Request $request){
        Carte::find($request->id)->update([
            'state'=>true,
            'treatment'=>'verification',
        ]);
        return 'OK';
    }

    public function recupCarte(Request $request){
       if(strval($request->friend) != 'friend'){
           $mesCartes = Carte::where('id_customer', $request->id)->where('owner','UBA')->where('treatment','confirme')->get();
           if(count($mesCartes)>0){
               return response()->json([
                   'statut'=> true,
                   'body' => $mesCartes,
                   'taille'=>count($mesCartes)
               ]);
           }else{
               return response()->json([
                  'statut'=> true,
                   'body' => [],
                   'taille'=> 0

               ]);
           }
       }else{
           $client = Customer::where('id',$request->id)->first();
           $mesfreinds = DB::select("select * from carte_information ci where ci.id_client in (select content from historiquetrans h where h.operation='recharge_visa_uba' and phonevendeur = $client->phoneclient) and ci.owner='UBA' and ci.id_customer <>$request->id and deleted_at is null");

           if(count($mesfreinds)>0){
               return response()->json([
                   'statut'=> true,
                   'body' => $mesfreinds,
                   'taille'=>count($mesfreinds)
               ]);
           }else{
               return response()->json([
                   'statut'=> false,
               ]);
           }
       }
    }

   public function editCarte(Request $request){
        Carte::find($request->id)->update([
            'names' => $request->names,
            'num_card'=> $request->nums,
            'expire_by'=>$request->expired,
            'id_client'=>$request->account
        ]);
        return response()->json([
            'statut'=> true,
            'body' => 'Modification réussie',
        ]);
    }
    
    public function addPieceCustomer(Request $request){
        Carte::find($request->id)->update([
            'param1' => $request->piece,
        ]);
        return response()->json([
            'statut'=> true,
        ]);
    }
    
    public function deleteCarte(Request $request){
        $carte = Carte::where('id',$request->id)->first();
        if($carte){
             Carte::where('id',$request->id)->update([
               'id_client' => $carte->id_client.'SUPP'.$carte->id,
            ]);
            Carte::where('id',$request->id)->delete();
            return response()->json([
                'statut'=> true,
                'body' => 'Votre carte a été retirée',
            ]);  
        }else{
            return response()->json([
                'statut'=> true,
                'body' => 'Carte déjà retirée',
            ]); 
        } 
    }
    
     // recuperer les infos des clients lors d'une opération
    public function recupInfoCustomers($emetteur, $receveur){
        $emetteur = Customer::where('phoneclient',$emetteur)->first();
        $receveur = Customer::whereIn('phoneclient',$receveur, '0'. $receveur)->first();
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
    
    public function commandeCard(){
        $trans = Historiquetrans::where('operation', 'achat_visa_uba')->whereNotIn('etat', ['attend', 'CONFIRMEE', 'remboursé'])->where('id', '>', 34952774)->orderBy('id', 'desc')->take(10)->get();
        if($trans){
            foreach ($trans as $tran){
                if($tran->montant_sans_frais < 11900){
                    $bank = 'UBA';
                }else{
                    $bank = 'UBA';
                   // $bank = 'ORABANK';
                }
                
                $carte = Carte::where('id_customer',strval($tran->id_customer))->where('owner', $bank)->first();
                if($carte){
                    Carte::where('id',$carte->id)->update([
                        'treatment' => 'attribution',
                    ]);
                    
                    if( ($bank == 'UBA' && $tran->montant_sans_frais == '10000') || ($bank == 'ORABANK' && $tran->montant_sans_frais == '11900')){
                        // attribution du coupon
                        $coupon = Coupon::whereNull('transaction')->where('purchase_amount',$tran->montant_sans_frais)->first();
                        if($coupon){
                            Historiquetrans::where('id',$tran->id)->update([
                                'param1' => $coupon->coupon,
                                'etat' =>'CONFIRMEE'
                            ]);
        
                            Coupon::where('id',$coupon->id)->update([
                                'transaction' => $tran->id,
                            ]);
                        }else{
                           Historiquetrans::where('id',$tran->id)->update([
                            'etat' =>'CONFIRMEE'
                           ]);  
                        } 
                    }else{
                       Historiquetrans::where('id',$tran->id)->update([
                            'etat' =>'CONFIRMEE'
                        ]);  
                    } 
                }
            }
            return response()->json([
                'statut'=> true,
                'body' => $trans,
                'message' => 'Commandes traitées',
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'body' => $trans,
                'message' => 'Pas de commande',
            ]);
        }
    }
    
     // ajouter une sim internationale physique
    public function addSim(Request $request){
        $sim = SimInternal::where('type', 'p')->where('iccd', $request->sim)->first();
        if($sim){
             if(empty($sim->customerId) && empty($sim->customerNum)){
                 $trans = Historiquetrans::where('id', intval($request->trans))->whereIn('etat',['atraiter', 'en_attente'])->first();
                 if($trans){
                     $customer = Customer::where('phoneclient', $trans->numclient)->first();
                     Historiquetrans::where('id',$trans->id)->update([
                         'param3'=>$sim->msisdn
                     ]);

                     SimInternal::where('id', $sim->id)->update([
                         'customerId'=>$customer->id,
                         'customerNum'=>$customer->phoneclient,
                     ]);

                     return response()->json([
                         'statut'=> true,
                         'message' => 'Sim Internationale ajoutée',
                     ]);
                 }else{
                     return response()->json([
                         'statut'=> false,
                         'message' => 'Transaction introuvable',
                     ]);
                 }

             }else{
                 return response()->json([
                     'statut'=> false,
                     'message' => 'Cette Sim est déjà rattachée à un compte',
                 ]);
             }
        }else{
            return response()->json([
                'statut'=> false,
                'message' => 'Sim inexistante',
            ]);
        }
    }
    
    public function enableMySim(Request $request){
       $sims = SimInternal::where('customerId', $request->customer)->get();
       if(count($sims)>0){
           if(count($sims) == 1 ){
               SimInternal::where('customerId', $request->customer)->update([
                   'param1'=>'enable'
               ]);
           }else{
               foreach ($sims as $sim){
                   if($sim->id == $request->sim){
                       SimInternal::where('id', $sim->id)->update([
                           'param1'=>'enable'
                       ]);
                   }else{
                       SimInternal::where('id', $sim->id)->update([
                           'param1'=> 'disable'
                       ]);
                   }
               }  
           }
          
       }

        return response()->json([
            'statut'=> false,
            'message' => 'Sim Activée',
        ]);
    }
    
    public function recupCarteScyd(Request $request){
        $mesCartes = Carte::where('id_customer', $request->id)->first();
        if($mesCartes){
            return response()->json([
                'statut'=> true,
                'body' => $mesCartes,
            ]);
        }else{
            return response()->json([
                'statut'=> false,

            ]);
        }
    }
}
