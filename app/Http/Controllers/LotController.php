<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LotController extends Controller
{

    public function lotCustomer(Request $request){
        $uri = $request->path();
        return view('lots', compact('uri'));
    }
}
