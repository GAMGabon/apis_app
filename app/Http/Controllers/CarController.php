<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicule;
use App\Models\CarsMark;
use App\Http\Controllers\File;
use Illuminate\Support\Facades\Redirect;

class CarController extends Controller
{
    public function storeCars(Request $request)
    {
        // $vehicule = new Vehicule;
        // if ($request->hasfile('image')) {
        //     $file = $request->file('image');
        //     $extenstion = $file->getClientOriginalExtension();
        //     $filename = time() . '.' . $extenstion;
        //     $file->move('uploads/vehicule/', $filename);
        //     $vehicule->image = $filename;
        // }

        $vehicule = new Vehicule([
            'nom' => $request->name,
            'type' => $request->type,
            'caracteristique' => json_encode($request->caracteristique),
            'prix' => $request->price,
            'image' => $request->image,
            'disponibility' => $request->disponibility,
        ]);

        $vehicule->save();
        return 1;
    }

    public function deleteCars($id)
    {
        $vehicule = Vehicule::find($id);

        $vehicule->delete();
        // Vehicule::find(intval($vehicule['id']))->update([
        //     'disponibility' => false
        // ]);
        return redirect('cars');
    }

    public function carsJson()
    {
        $vehicule = Vehicule::all();
        return response()->json([
            'body' => $vehicule
        ]);
    }

    public function cars()
    {
        $vehicule = Vehicule::all();
        $marque_cars = CarsMark::all();
        return view('cars', compact('vehicule', 'marque_cars'));
    }

    //Insert marque cars

    public function storeMarkCars(Request $request)
    {
        // $marque_cars = new CarsMark;
        // if ($request->hasfile('image_mark')) {
        //     $file = $request->file('image_mark');
        //     $extenstion = $file->getClientOriginalExtension();
        //     $filename = time() . '.' . $extenstion;
        //     $file->move('uploads/marque/', $filename);
        //     $marque_cars->logo = $filename;
        // }

        $marque_cars = new CarsMark([
            'marque' => $request->name_mark,
            'logo' => $request->image_mark
        ]);

        $marque_cars->save();
        return redirect()->back();
    }

    //update marque cars

    public function editMarkCars(Request $request, $id)
    {
        $marque_cars = CarsMark::find($id);

        $marque_cars->marque = $request->input('name_mark_update');
        $marque_cars->logo = $request->input('image_mark_update');

        $marque_cars->update();
        return redirect()->back();
    }

    //delete marque Cars

    public function deleteMarkCars($id)
    {
        $marque_cars = CarsMark::find($id);

        $marque_cars->delete();
        return redirect('cars');
    }
    
    public function getCars(Request $request){
        $allCar=Vehicule::all(); 
        $allmark=CarsMark::all();
        return response()->json([
            'status'=> true,
            'bodyCarsmarques'=> $allmark,
            'bodyCars'=> $allCar,
        ]);   
    }
    
    public function editCars(Request $request)
    {
        $vehicule = Vehicule::where('id', $request->id)->update([
            'nom' => $request->name_update,
            'type' => $request->type_update,
            'caracteristique' => $request->caracteristique_update,
            'prix' => $request->price_update,
            'image' => $request->image_update,
            'disponibility' => $request->disponibility_update
        ]);
        $vehicule->save();
        return redirect()->back();

    }
}
