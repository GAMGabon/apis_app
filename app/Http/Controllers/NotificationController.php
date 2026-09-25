<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{

    public function notification(Request $request){
        $notif = count(Notification::where('receiver', $request->phoneclient)->where('notified',0)->get());
        if($notif>=1){
            return response()->json([
                'statut'=> true,
                'body'=>$notif

            ]);
        }else{
            return response()->json([
                'statut'=> false,
                'body'=>[]

            ]);
        }
    }

}