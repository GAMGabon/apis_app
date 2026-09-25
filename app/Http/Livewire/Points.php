<?php

namespace App\Http\Livewire;

use App\Models\Customer;
use App\Models\Incident;
use App\Models\Lot;
use Livewire\Component;

class Points extends Component

{
    public $search ="";
    public $lieu =" ";
    public $customers;
    public $lots;


    public function render()
    {
        if ($this->search <> 0){
            $this->customers =Customer::where('phoneclient', 'like', "%{$this->search}%")->take(10)->get();
        }else{
            $this->customers =Customer::where('score', '<>', '0')->take(20)->orderBy('score', 'desc')->get();
        }
        $this->lots = Lot::all();

        return view('livewire.points', ['incidents'=>$this->customers,'lots'=>$this->lots]);
    }
}
