<?php

namespace App\Http\Livewire;

use App\Models\Lot;
use http\Env\Request;
use Livewire\Component;
use Livewire\WithFileUploads;

class Lots extends Component
{
    use WithFileUploads;
    public $nom;
    public $idLot;
    public $photo ;
    public $photoEdit ;// image quand on modifie la photo d'un lot
    public $desc ;
    public $quantite ;
    public $rang ;
    public $updateImage ;
    public int $lotId = 0;
    public int $lotImage = 0;

    protected $listeners = [
      'lotUpdated' => 'onLOtUpdated'
    ];

    public function render()
    {
        return view('livewire.lots',[
        'lots' => Lot::orderBy('id', 'desc')->get()
        ] );
    }

    public function startEdit(int $id, $op){
        switch ($op){
            case 'edit':
                if($this->lotId == $id){
                    $this->lotId = 0;
                }else{
                    $this->lotId = $id;
                }
                break;

            case 'add':
                if($this->lotImage == $id){
                    $this->lotImage = 0;
                }else{
                    $this->lotImage = $id;
                }
                break;
        }

    }

    public function onLOtUpdated(){
        $this->reset('lotId');
        $this->reset();
    }

    public function edit( $id, $nom, $qte, $rang, $photo, $desc){
        $this->idLot = $id;
        $this->nom = $nom;
        $this->quantite = $qte;
        $this->rang = $rang;
        $this->updateImage = $photo;
        $this->desc = $desc;
    }



    public function updateLot(){

        if ($this->idLot <> 0){

            if (!empty($this->photo)){
                //$extension = $this->photo->getClientOriginalExtension();
                //$filename = time() . "." . $extension;
                //$this->photo->move('img/lots/', $filename);
            }else{
                $filename = $this->updateImage;
            }

            Lot::find($this->idLot)->update([
                'nom'=> $this->nom,
                'photo'=> $filename,
                'description'=> $this->desc,
                'quantite'=> $this->quantite,
                'rang_gagnant'=> $this->rang,
                'challenge_for'=> 'gam',
            ]);
            session()->flash('message', 'Lot Modifié.');
            $this->reset();
        }else{
            if (!empty($this->photo)){
                $filename = time() . "." . $this->photo->extension();
                $this->photo->storeAs('lots', $filename);
            }else{
                $filename = "trophy.png";
            }
            $mission = new Lot([
                'nom'=> $this->nom,
                'photo'=> $filename,
                'description'=> $this->desc,
                'quantite'=> $this->quantite,
                'rang_gagnant'=> $this->rang,
                'challenge_for'=> 'gam',
            ]);
            $mission->save();
            session()->flash('message', 'Lot enregistré.');
            $this->reset();
        }




    }


    public function deletedLot(Request $request){

        dd($request->idLot);

        Lot::destroy();
    }
}
