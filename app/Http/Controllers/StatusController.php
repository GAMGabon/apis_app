<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Customer;
use App\Models\Status;
use App\Models\Partage;
use App\Models\Historiquetrans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;


class StatusController extends Controller
{

    public function getStatus(Request $request){
  
        if(!empty($request->customer)){
            $monStatus = Status::where('client_id', intval($request->customer))->get();
            if(count($monStatus)>0){
                $statusPaiement = Status::where('client_id', intval($request->customer))->where('status', 0)->get();
                if($statusPaiement){
                    foreach ($statusPaiement as $status){
                        $stran = Historiquetrans::whereNotIn('etat', ['CONFIRMEE', 'attend'])->where('content', $status->id)->first();
                        if($stran){
                            Status::where('id',  $status->id)->update([
                                'status'=>2
                            ]);
                        }
                    }
                }
                $status= Status::where('client_id', intval($request->customer))->orderBy('id', 'desc')->get();;
            }else{
                $status = 0;
            }
        }else if(empty($request->vue) || $request->vue == 'vue'){
            $status = Status::where('status', 1)->orderBy('level', 'desc')->orderBy('id', 'desc')->get();
        }else{
            $monStatus = Status::where('status', 1)->where('id', intval($request->vue))->get();
            if(count($monStatus)>0){
                $status = $monStatus;
            }else{
                $status = Status::where('status', 1)->orderBy('level', 'desc')->get();
            }
            
        }
        return response()->json([
            'statut'=> true,
            'body'=> $status,
            'chaine' => 'https://whatsapp.com/channel/0029Va8AVWF05MUWbo2zbk3c/174'
        ]);
    }
    
     public function storeStatut(Request $request)
    {
        $customer = Customer::where('id', intval($request->idCustomer))->first();
        $Store = new Status([
            'client_id' => intval($request->id),
            'nom' => $request->nom,
            'prix' => $request->price,
            'titre' => $request->titre,
            'description' => $request->desc,
            'facebook' => $request->fbk,
            'www' => $request->gam,
            'vue_demandees' => $request->vues,
            'phone' => $request->phone,
            'zones' => json_encode($request->zones),
            'whatsapp' => $request->what,
            'file' => $request->file,
            'files' => json_encode($request->listImages),
            'file_type' => $request->type,
            'secteurs' => json_encode($request->secteurs),
        ]);
        $Store->save();
        return response()->json([
            'statut'=> true,
            'body'=> $Store,
        ]);
    }

    public function getComment(Request $request){
        $comment = Comment::where('status_id', intval($request->vue))->orderBy('id', 'desc')->get();
        return response()->json([
            'statut'=> true,
            'body'=> $comment,
        ]);
    }

    public function addComment(Request $request){
        $customer = Customer::where('id', intval($request->idCustomer))->first();
        $status = Status::where('id', intval($request->id))->first();
        $comment = new Comment([
            'status_id'=>intval($request->id),
            'article'=>$request->titre,
            'numero_client'=>$request->phone,
            'nom_client'=>$customer->pseudo != null? $customer->pseudo : 'xxx',
            'commentaire'=>$request->comment,
            'status'=>1,
            'param7'=>1,
        ]);
        $comment->save();



        Status::where('id', intval($request->id))->update([
            'last_comment'=> json_encode([
                'nom'=> $customer->pseudo != null? $customer->pseudo : 'xxx',
                'comment'=> $request->comment
            ]),
            'nbr_comment'=> $status->nbr_comment + 1,
        ]);
        return response()->json([
            'statut'=> true,
            'body'=> $comment,
        ]);
    }
    
    public function getLocationPhone(Request $request){
       
        return response()->json([
            'statut'=> true,
            'body'=> 0,
        ]);
    }
    
    
    public function updateStatut(Request $request)
    {
        $customer = Status::where('id', intval($request->id))->update([
            'nom' => $request->nom,
            'titre' => $request->titre,
            'description' => $request->desc,
            'facebook' => $request->fbk,
            'www' => $request->gam,
            'phone' => $request->phone,
            'zones' => json_encode($request->zones),
            'whatsapp' => $request->what,
        ]);
        $status = Status::where('id', intval($request->id))->first();
        return response()->json([
            'statut'=> true,
            'body'=> $status,
        ]);
    }

    public function getStatusUser(Request $request){
        $status = Status::where('client_id', $request->id)->orderBy('id', 'desc')->get();
        return response()->json([
            'statut'=> true,
            'body'=> $status,
        ]);
    }
    
    public function status(Request $request){
        $status = Status::where('id', $request->id)->first();
        return response()->json([
            'statut'=> true,
            'body'=> $status,
        ]);
    }
    
    public function paiement(){
        $strans = Historiquetrans::whereNotIn('etat', ['CONFIRMEE', 'attend'])->where('operation', 'achat_status')->get();
        if($strans){
            foreach ($strans as $stran){
                Status::where('id', intval($stran->content))->update([
                    'status'=>1
                ]);
                Historiquetrans::where('id',$stran->id)->update([
                    'etat'=>'CONFIRMEE'
                ]);
                // Notifier les reférents
                $referents = Customer::where('customer_type', 'referent')->get();
                if($referents){
                    foreach ($referents as $referent){
                        $numNotif = $referent->phoneclient;
                        $messageNotif = 'Une nouvelle publication disponible dans votre zone';
                        // On envoie le code par notification et par sms au propiétaire
                        Http::get("https://gampay.org/gamclients/public/api/sendNotificationget?phonevendeur=$numNotif&operation=info&message=$messageNotif&numclient=$numNotif");
                    }
                }
            }
        }
        return $strans;
    }
    
    public function getShare(Request $request){
        //$getShare = Partage::where('id_status', $request->objet)->orderBy('nbr_vues', 'desc')->get();
        $tatus = intval($request->objet);
        $getShare = DB::select("select * from partage p where id_status=$tatus  order by CAST(nbr_vues AS INTEGER) desc");
        return response()->json([
            'statut'=> true,
            'body'=> $getShare,
        ]);

    }

    public function getReferent(Request $request){
        $getReferent = Partage::where('id_referent', $request->id)->get();
        return response()->json([
            'statut'=> true,
            'body'=> $getReferent,
        ]);

    }
    
    public function recupGain(Request $request){
        
        if($request->operation == 'parrainage'){
            return response()->json([
                'statut'=> false,
                'message'=> 'La rcupération des gains se fait chaque 30 du mois',
            ]);
        }
        $verifCustomer = Customer::where('phoneclient', $request->numClient)->first();
        
        
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

}
