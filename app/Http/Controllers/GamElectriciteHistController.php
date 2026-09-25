<?php

namespace App\Http\Controllers;


use App\Models\GamElectriciteHist;
use App\Models\Compteur;
use App\Models\Customer;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Http\Request;
use DB;

class GamElectriciteHistController extends Controller
{
    //
    public function updateHist(Request $request){
        $operations = GamElectriciteHist::where('num_client', $request->num)->where('id_transaction', $request->transaction)->get();
        if(count($operations)){
            foreach ($operations as $operation){
                GamElectriciteHist::find($operation['id'])->update([
                    'code_transaction' => $request->code
                ]);
            }
            session()->put('op', 'yes');
            return redirect()->route('edan');

        }else{
            session()->put('op', 'no');
            return redirect()->route('edan');
        }
    }

    public function getHist(Request $request){
        $uri = $request->path();
        $operations = GamElectriciteHist::where('etat', 'confirme')->get();
        return view('edan', compact('operations', 'uri'));
    }

    public function compteur(Request $request){
        return response()->json([
            'statut'=> False,
            'message'=>'Ce compteur existe déja',
        ]);
        
        $compteur = new Compteur(
            [
                'num_client'=>$request->phone,
                'nom_compteur'=>$request->nom,
                'num_compteur'=>$request->compteur,
                'dernier_montant'=>$request->montant, //dernier montant
            ]

        );
        $verifCompteur= Compteur::where('num_client', $request->phone)->where('num_compteur', $request->compteur)->get();
        if(count($verifCompteur)>0){
            return response()->json([
                'statut'=> False,
                'message'=>'Ce compteur existe déja',
            ]);

        }else {
            
            $compteur->save();
            return response()->json([
                'statut' => true,
                'message' => 'votre compteur a été bien créé',
            ]);
        }


    }
    public function ticketEdan(Request $request){
        $ticketEdan = new GamElectriciteHist(
            [
                'num_client'=>$request->phone,
                'nom_compteur'=>$request->nom,
                'num_compteur'=>$request->compteur,
                'montant_transaction'=>$request->montantTrans,
                'id_transaction'=>$request->idTrans,
                'code_transaction'=>$request->codeTrans,
                'prix_unitaire_kwh'=>$request->montant,
                'total_unites'=>$request->unites,
                'consommation'=>$request->consommation,
                'tva'=>$request->tva,
                'cse'=>$request->cse,
                'etat'=>$request->etat,
                'special_key'=>$request->specialkey,
            ]
        );

        $ticketEdan->save();
        return response()->json([
            'statut'=> true,
            'message'=>'Ticket créé',
        ]);
    }


    public function modifCompteur(Request $request ){

        Compteur::find(intval($request->id))->update([
            'num_client'=>$request->phone,
            'nom_compteur'=>$request->nom,
            'num_compteur'=>$request->compteur,
            'dernier_montant'=>$request->montant, //dernier montant
        ]);

        return response()->json([
            'statut'=> true,
            'message'=>'Votre compteur a bien été modifié',
        ]);
    }


    public function DeleteTicket(Request $request ){
      GamElectriciteHist::find(intval($request->id))->delete();
        return response()->json([
            'statut'=> true,
            'message'=>'Ticket supprimé.',
        ]);
    }

    public function DeleteCompteur(Request $request ){
        Compteur::find(intval($request->id))->delete();
        return response()->json([
            'statut'=> true,
            'message'=>'Compteur supprimé',
        ]);
    }



    public function recupCompteur(Request $request ){
        
        if(!in_array($request->phone, ['074119984', '074582442','062507006'])){
            return response()->json([
                'statut'=> true,
                'Compteur'=> [],
            ]);
        }
        
        
        $compt = Compteur::where('num_client', $request->phone)->get();
        if(count($compt)>0){
            return response()->json([
                'statut'=> true,
                'Compteur'=> $compt,
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'Compteur'=> 'Aucun compteur',
            ]);
        }
    }
    
    public function recupTicket(Request $request ){
        
        if(!empty($request->compteur)){
           $ticket = GamElectriciteHist::where('num_client', $request->phone)->where('num_compteur', $request->compteur)->whereNotIn('etat',['attend'])->orderBy('id', 'desc')->take(20)->get();
        }else{
           $ticket = GamElectriciteHist::where('num_client', $request->phone)->whereNotIn('etat',['attend'])->orderBy('id', 'desc')->take(20)->get();   
        }
        if(count($ticket)>0){
            return response()->json([
                'statut'=> true,
                'body'=> $ticket,
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'body'=> 'Aucun ticket',
            ]);
        }
    }
    
    public function transEdan(Request $request){
        $solde = 0;
        if($request->modePaiement == 'gam'){
            $client = Customer::where('id', intval($request->idCustomer))->first();            
            if(empty($client)){
               return response()->json([
                    'statut'=> false,
                    'message'=> 'Service indisponible pour le moment'
                ]);  
            }else{
                $solde=$client['solde'];
                if(intval($client['solde'])>= intval($request->montantFrais)){
                    $newSolde = intval($client['solde']) - intval($request->montantFrais);
                    Customer::find(intval($request->idCustomer))->update([
                        'solde' =>strval($newSolde)
                    ]);
                    $accord = true;
                    $etat = 'attente';
                }else{
                    $accord = false;
                }  
            }
          
        }else if($request->modePaiement == 'GAB'){
            $accord = true;
            $etat = 'attente'; 
        }else{
            $accord = true;
            $etat = 'attend';
        }
        
        $uniqid_trans = uniqid();

        if($accord == true){
            $transEdan = new GamElectriciteHist([
                'num_client' => $request->numclient,
                'nom_compteur' => $request->nomCompteur,
                'num_compteur' => $request->compteur,
                'montant_transaction' => $request->montantSansFrais,
                'etat' => $etat,
                'param1' => $request->origine,
                'special_key' => $uniqid_trans,
                'param2'=> $request->modePaiement
            ]);
            $transEdan ->save();
            
            return response()->json([
                'statut'=> true,
                'message'=> 'transaction en cours ',
                'montant'=> $transEdan->montant_transaction,
                'id'=> $transEdan->id
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'message'=> 'le solde de votre compte GamPay est insuffisant'
            ]);
        }
    }






}
