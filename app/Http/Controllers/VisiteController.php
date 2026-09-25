<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PartageCarte;
use Illuminate\Http\Request;
use App\Models\CarteVisite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;


class VisiteController extends Controller
{
     public function addcarte(Request $request) {
        $mycarte = new CarteVisite([
            'nom'=>$request->nom,
            'logo'=>$request->logo,
            'site'=>$request->site,
            'phone'=>$request->phone,
            'color'=>$request->color,
            'customer_id'=>$request->customer,
            'whatsapp'=>$request->whatsapp,
            'domicile'=>$request->domicile,
            'email'=>$request->email,
            'entreprise'=>$request->entreprise,
            'poste'=>$request->poste,
            'instagram'=>$request->instagram,
            'fbk'=>$request->fbk,
            'partage'=> empty($request->partage)? 25 : $request->partage,
            'tiktok'=>$request->tiktok,
            'linkdin'=>$request->linkdin,
        ]);
        $mycarte->save();

        return response()->json( [
            'statut'=> true,
            'body'=> $mycarte,
        ]);
    }


    public function visiteCarte(Request $request){
        $carte = CarteVisite::where('id', intval($request->carte))->first();
        if($carte){
            if($carte->partage > 0){
                $carte->update([
                    'partage'=> $carte->partage - 1
                ]);
                return redirect('https://app.gampay.org/?visite=visite&v='.$carte->id.'&p='.$carte->customer_id);
            }else{
                $message = json_encode(['type' => 'visite', 'text'=> "$carte->nom a épuisé le nombre de partage de sa carte de visite", 'file'=>$carte->logo]);
                return redirect('http://localhost:61527/?message='.$message);
            }
        }else{
            return redirect('https://app.gampay.org/?v=vue&p='.$request->user);
        }
    }


    public function getVisiteCarte(Request $request){
        if(!empty($request->carte)){
            $carte = CarteVisite::where('id', intval($request->carte))->first();
        }else{
            $carte = CarteVisite::where('customer_id', intval($request->customer))->get();
        }
        if((!empty($request->carte) && !empty($carte)) || (empty($request->carte) && count($carte) > 0)){
            return response()->json( [
                'statut'=> true,
                'body'=> $carte,
            ]);
        }else{
            return response()->json( [
                'statut'=> false,
            ]);
        }
    }
    
    
    public function saveCarte(Request $request){
        $customer = Customer::where('phoneclient', $request->phoneclient)->first();
        if($customer){
            $id = $customer->id;
            $mypriority = empty($customer->priority)? [] : json_decode($customer->priority);
            array_push($mypriority, 'visite');
            $customer->update(['priority', json_encode($mypriority)]);
            $type = 'old';
        }else{
            $parrain = Customer::where('phoneclient',$request->parrain)->first();
            $newCustomer = new Customer([
                'nom'=> 'client',
                'prenom'=> 'GAM',
                'pays'=>$request->pays,
                'phoneclient'=> $request->phoneclient,
                'solde'=> '0',
                'mdpclient'=> '1234',
                'phoneparent'=> $parrain->phoneclient,
                'option1'=> $parrain->option1,
                'code_confirm'=> $parrain->code_confirm,
                'priority' => json_encode(['visite']),
                'satus'=>1
            ]);
            $newCustomer->save();
            Http::get('https://gampay.app/gamclients/public/api/recupIdCustomer');
            $id = $newCustomer->id;
            $type = 'new';
        }

        $verifPartage = PartageCarte::where('carte_id', $request->carte)->where('customer_id', $id)->get();
        if(count($verifPartage)>0){
            return response()->json([
                'statut'=> false,
                'message'=>'Vous avez déjà enregistré cette carte de visite',
                'type' => $type
            ]);
        }else{
            $newSaveCard = new PartageCarte([
                'carte_id'=> $request->carte,
                'customer_id'=> $id
            ]);
            $newSaveCard->save();
            return response()->json([
                'statut'=> true,
                'message'=> 'Carte de visite enregistrée',
                'type' => $type
            ]);
        }
    }
    
    public function getPartageCustomer(Request $request) {
        $getpartageclient = DB::select("select * from carte_visite cv where cv.id in ( select carte_id from partage_carte pc where pc.customer_id =$request->customer_id)");
        if (count($getpartageclient)>0) {
            return response()->json([
                'statut'=>true,
                'body'=>$getpartageclient
            ]);
        }else{
            return response()->json( [
                'statut'=> false,
                'message'=>'Aucune carte ',
            ]);
        }
    }
}
