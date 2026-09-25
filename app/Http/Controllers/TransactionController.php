<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    //Inser une transaction
    public function newTrans(Request $request){
        $trans = new Transaction([
            'montant'=>$request->montantTrans,
            'quartier'=>$request->quartierTrans,
            'agent'=>$request->agentTrans,
            /*'param1'=>$request->param1Trans,
            'param2'=>$request->param2Trans,
            'param3'=>$request->param3Trans,
            'param4'=>$request->param4Trans,
            'param5'=>$request->param5Trans*/
        ]);
        $trans -> save();
        if (!empty($trans->id)) {
            $response = 1;
        }else{
            $response = 0;
        }
        return $response ;
    }

    public function getTrans(){
        $trans = Transaction::all();
        return json_encode($trans);
    }
}
