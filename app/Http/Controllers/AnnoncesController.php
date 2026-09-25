<?php


namespace App\Http\Controllers;
use App\Models\Annonces;
use App\Models\Customer;
use DB;
use App\Models\Categorie;
use Illuminate\Database\Console\Migrations\ResetCommand;
use App\Models\Historiquetrans;
use Illuminate\Http\Request;

class AnnoncesController extends Controller
{
    
    public function annonces(Request $request){
        $Annonces = new Annonces(
            [
                'cat'=>$request->cat,
                'titre_annonce'=>$request->titre_annonce,
                'prix'=>$request->prix,
                'nom'=>$request->nom,
                'id_customer'=>$request->id_customer,
                'description'=>$request->description,
                'phoneclient'=>$request->phoneclient,
                'whatsapp'=>$request->whatsapp,
                'latitude'=>$request->latitude,              
                'longitude'=>$request->longitude,
                'region'=>$request->region,              
                'image'=>$request->image,
                'ref'=>$request->ref,
                'boosting'=>$request->boosting,
                'listImages'=>json_encode($request->listImages),                                             
            ]
        );

        $Annonces->save();
        return response()->json([
            'statut'=> true,
            'message'=>'Annonces créer',
        ]);

    }


    
public function annoncesDelete (Request $request){

    $annonces= Annonces::find(intval($request->id))->delete();

    if($annonces == true){
        return response()->json([
            'statut'=> true,
            'message'=>'Annonce à été supprimée avec success',
        ]);
    }else {
        return response()->json([
            'statut'=>false,
            'message'=>'Aucune annonce à supprimée',
        ]);
    }

}

public function recupAnnonce(Request $request ){
    $lat= Annonces::where('statut', '1')->orderBy('id','desc')->get();
    if($lat==true){
        return response()->json([
            'statut'=> true,
            //'annonces'=> $lat,
            'annonces'=> $lat,
        ]);
    }else{
        return response()->json([
            'statut'=> false,
            'annonces'=> 'Aucun annonce',
        ]);
    }

}


public function modifAnnonce(Request $request ){
    Annonces::find(intval($request->id))->update([
        'nom'=>$request->nom,              
        'titre_annonce'=>$request->titre_annonce,
        'prix'=>$request->prix,
        'latitude'=>$request->latitude,
        'longitude'=>$request->longitude,
        'description'=>$request->description,                
        'cat'=>$request->cat,
        'region'=>$request->region,
        'phoneclient'=>$request->phoneclient,
        'image'=>$request->image,
        'boosting'=>$request->boosting, 
        'listImages'=>json_encode($request->listImages),  
    ]);

    return response()->json([
        'statut'=> true,
        'message'=>'modification avec success',
    ]);

}

public function addCategorie(Request $request){
    $categorie = new Categorie(
        [               
           'titre_cat'=>$request->titre_cat,                                       
        ]
    );
    $categorie->save();
    return response()->json([
        'statut'=> true,
        'message'=>'categorie créer',
    ]);

}


public function recupCategorie(Request $request ){
    $cat= Categorie::all();

    if($cat==true){
        return response()->json([
            'statut'=> true,
            //'annonces'=> $cat,
            'categorie'=> $cat,
        ]);
    }else{
        return response()->json([
            'statut'=> false,
            'categorie'=> 'Aucune categorie',
        ]);
    }


}


public function deleteCategorie (Request $request){

    $annonces= Categorie::find(intval($request->id))->delete();

    if($annonces == true){
        return response()->json([
            'statut'=> true,
            'message'=>'catégorie à été supprimée avec success',
        ]);
    }else {
        return response()->json([
            'statut'=>false,
            'message'=>'Aucune catégorie à supprimée',
        ]);
    }

}



public function userAnnonce (Request $request){

    $annonces= Annonces::where('id_customer',intval($request->id))->get();
    
    //$annonces = Annonces::where('num_client', $request->num)->where('id_transaction', $request->transaction)->get();S

    if($annonces == true){
        return response()->json([
            'statut'=> true,
             'Annonces'=> $annonces,
        ]);
    }else {
        return response()->json([
            'statut'=>false,
            'Annonces'=>'Aucune annonce trouvée',
        ]);
    }

}


public function modfiBoosting(Request $request ){
    Annonces::find(intval($request->id))->update([
        'boosting'=>$request->boosting,              
    ]);

    return response()->json([
        'statut'=> true,
        'message'=>'modification avec success',
    ]);

}


public function recupBoosting (Request $request){

    $annonces= Annonces::where('boosting', 'true')->orderBy('id','desc')->get();
    
    if(count($annonces)>0){
        return response()->json([
            'statut'=> true,
             'Annonces'=> $annonces,
        ]);
    }else {
        return response()->json([
            'statut'=>false,
            'Annonces'=>'Aucune annonce trouvée',
        ]);
    }

}


public function userAchat(Request $request){
    $achat= historiquetrans::where('operation' , 'achat_annonce')->where('id_customer', strval($request->id))->get();
    $annonces = Array();
    if ( count($achat)>0) {
        foreach ($achat as $achats ) {
            $annonce= Annonces::where('id', $achats['content'] )->get();
             array_push( $annonces,$annonce);
        }
        return response()->json([
            'statut'=>true,
            'Annonces'=>$annonces,
        ]);
    }else {
        return response()->json([
            'statut'=>false,
            'Annonces'=>'Aucune annonce trouvée',
        ]);
    }
}









}
