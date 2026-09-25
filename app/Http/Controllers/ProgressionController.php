<?php

namespace App\Http\Controllers;

use App\Models\ProgressionCustomer;
use App\Models\StorePhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class ProgressionController extends Controller
{
    public function countProgression(Request $request)
    {
        $id = $request->id;
        $phone = $request->phone;
        $product = $request->product;
        $value = $request->value;
        $plusPetiteDifference = null;

        $states = [];

        $custom = ProgressionCustomer::where('phoneCustomer', $phone)->first();

        if ($custom) {
            if (!empty($custom->states)) {
                $states = json_decode($custom->states, true);

                if (array_key_exists($product, $states)) {
                    $states[$product]++;
                } else {
                    $states[$product] = 1;
                }

                $custom->update([
                    'states' => json_encode($states)
                ]);
            }
        } else {
            (!empty($value) && $product == 'ussd') ? $states[$product] = $value + 1 : $states[$product] = 1;

            $custom = new ProgressionCustomer([
                'idCustomer' => $id ?? null,
                'phoneCustomer' => $phone ?? null,
                'states' => json_encode($states)
            ]);
            $custom->save();
        }

        if (empty($custom->id_phone)) {
            $idphone = [];
            $statesData = json_decode($custom->states, true);
            $totalPointsClient = array_sum($statesData);
            $stores = StorePhone::where('statut', true)->get();
            $meilleurScoreTelephone = null;

            foreach ($stores as $store) {
                $pointsUssdTelephone = $store->pts_ussd;

                if ($pointsUssdTelephone > $totalPointsClient) {

                    if ($meilleurScoreTelephone === null || $pointsUssdTelephone < $meilleurScoreTelephone) {
                        $meilleurScoreTelephone = $pointsUssdTelephone;
                        $idphone = [
                            'id' => $store->id,
                            'name' => $store->name,
                            'file' => $store->file,
                            'price' => $store->price,
                            'ussd' => $store->pts_ussd,
                            'pack' => $store->pts_pack,
                            'app' => $store->pts_app
                        ];
                    }
                }
            }

            $custom->update([
                'id_phone' => json_encode($idphone)
            ]);
        }
        
        return response()->json([
            'statut' => true,
            'customer' => $custom
        ]);
    }
    
    public function getProgression(Request $request)
    {
        if (!empty($request->phone)) {
            $user = ProgressionCustomer::where('phoneCustomer', $request->phone)->first();
            $statesData = json_decode($user->states, true);
            $totalPointsClient = array_sum($statesData);
            
            if(!empty($request->idphone)){
                $stores = StorePhone::where('id', $request->idphone)->get();
            }elseif(!empty($user->id_phone)){
                $dataPhones = json_decode($user->id_phone, true);
                $stores = StorePhone::where('id', $dataPhones['id'])->get();
            }
            $meilleurScoreTelephone = null;
            $price_phone_reduct = null;
            $idphone = [];

            foreach ($stores as $store) {
                $pointsUssdTelephone = $store->pts_ussd; 
        
                if ($pointsUssdTelephone > $totalPointsClient) {
                    
                    if ($meilleurScoreTelephone === null || $pointsUssdTelephone < $meilleurScoreTelephone) {
                        
                        $meilleurScoreTelephone = $pointsUssdTelephone;
                        $idphone = [
                            'id' => $store->id,
                            'name' => $store->name,
                            'file' => $store->file,
                            'price' => $store->price,
                            'ussd' => $store->pts_ussd,
                            'pack' => $store->pts_pack,
                            'app' => $store->pts_app
                        ];
                        $ussdClient = $statesData['ussd'] ?? 0;
                        $packClient = $statesData['pack'] ?? 0;
                        $appClient  = $statesData['app']  ?? 0;

                        $ussd = $ussdClient * 100 / 0.05;
                        $pack = ($packClient * 100 /0.3) + $ussd;
                        $app  = ($appClient * 100 / 0.7) + $ussd;

                        $price_phone_reduct = round($store->price - ($ussd + $pack + $app));
                    }
                }
            }

            $user->update([
                'id_phone' => json_encode($idphone),
                'price_phone_reduct' => $price_phone_reduct
            ]);
        }
        $customs = DB::select("SELECT * from client2 WHERE classement is not null and deleted_at is null order by CAST(challenge as integer) desc");

        $stres = StorePhone::where('statut', true)->get();

        return response()->json([
            'statut'  => true,
            'user' => $user ?? null,
            'body' => $customs,
            'phones' => $stres,
        ]);
    }

    public function getProgressionOld(Request $request)
    {
        $phone = $request->phone;
        $id = $request->id;
        $statut = false;

        $custom = ProgressionCustomer::where(function ($q) use ($phone, $id) {
            if ($phone) $q->where('phoneCustomer', $phone);
            if ($id) $q->orWhere('idCustomer', $id);
        })->first();

        if ($custom) {
            $statut = true;
            $statesData = json_decode($custom->states, true);

            if (is_array($statesData) && !empty($statesData)) {
                $details = [];
                foreach ($statesData as $service => $quantite) {
                    $details[] = "{$service} : {$quantite}";
                }
                $listeServices = implode(', ', $details);
                $smsTexte = "Bonjour, voici votre progression - " . $listeServices;
            } else {
                $smsTexte = "Bonjour, aucune progression détaillée disponible.";
            }
        } else {
            $smsTexte = "Aucune progression enregistrée.";
        }

        return response()->json([
            'statut'  => $statut,
            'message' => $smsTexte,
        ]);
    }

    public function calculPointsStore(Request $request)
    {
        $stores = StorePhone::where('statut', false)->get();
        $pts_ussd = 0;
        $pts_pack = 0;
        $pts_app = 0;
        if (count($stores) > 0) {
            foreach ($stores as $store) {
                $ussd = $store->price * 0.05;
                $pts_ussd = $ussd / 100;
                $pts_pack = (($store->price - $ussd) * 0.3) / 100;
                $pts_app = (($store->price - $ussd) * 0.7) / 100;

                StorePhone::where('id', $store->id)->update([
                    'pts_ussd' => $pts_ussd,
                    'pts_pack' => $pts_pack,
                    'pts_app' => $pts_app,
                    'statut' => true
                ]);
            }
            return response()->json([
                'statut'  => true,
                'body' => $stores,
            ]);
        }else{
            return response()->json([
                'statut'  => false,
                'body' => $stores,
            ]);
        }
    }
}
