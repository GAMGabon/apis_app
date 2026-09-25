<?php

namespace App\Http\Controllers;

use App\Models\Box_gamTv;
use App\Models\BoxGamTv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GamTvController extends Controller
{

   
    Public function newBox(Request $request){

        $box = new BoxGamTv(
            [
                'id_client' => $request->id,
                'nom_box' =>$request->nomBox,
                'num_box'=>$request->numero,
                'abonnement'=>$request->duree,
                'date_debut'=>$request->debut,
                'date_fin'=>$request->fin,
                'statut'=>'actif',
            ]
        );
        $box->save();
        return response()->json([
            'statut'=> true,
            'body'=>'Box creer',
        ]);
    }
    public function updateBox(Request $request){
        BoxGamTv::find(intval($request->id))->update([
            'nom_box'=>$request->nomBox,
        ]);
        return response()->json([
            'statut'=> true,
            'body'=>'Box modifiée',
        ]);
    }

    public  function getBox(Request $request){
        $box = BoxGamTv::where('id_client', $request->id)->get();
        if(count($box)>0){
            return response()->json([
                'statut'=> true,
                'body'=> $box,
            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'body'=> [],
            ]);
        }
    }

    //Abonnements


    public function newSubcription(Request $request){
        $abonnement = BoxGamTv::where('numBox', $request->numero)->update([
            'abonnement'=>$request->duree,
            'date_debut'=>$request->debut,
            'date_fin'=>$request->fin,
            'statut'=>'actif',
        ]);
            return response()->json([
                'statut'=> True,
                'body'=>'Abonnement reussi',
                ]);

    }
}
