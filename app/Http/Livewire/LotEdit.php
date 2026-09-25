<?php

namespace App\Http\Livewire;

use App\Models\Lot;
use Livewire\Component;
use phpDocumentor\Reflection\Types\This;

class LotEdit extends Component
{

    public Lot $lot;
    public $image;

    protected $rules = [
       'lot.nom' => 'required',
       'lot.quantite' => 'required',
       'lot.rang_gagnant' => 'required',
       'lot.photo' => 'required',
       'lot.description' => 'required',
    ];

    public function saveLot()
    {
      $this->lot->save();
      $this->emit('lotUpdated');
    }

    public function render()
    {
        return view('livewire.lot-edit');
    }
}
