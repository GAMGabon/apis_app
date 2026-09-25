<?php

namespace App\Http\Livewire;

use App\Models\Client2;
use App\Models\Customer;
use Livewire\Component;

class Classement extends Component
{

    public $first;
    public $last;
    public $firstUssd ;
    public $lastUssd ;// image quand on modifie la photo d'un lot
    public $app ;
    public $ussd ;
    public $search = '' ;

    public function render()
    {
        $this->first = Customer::where('score', '>', 1)->whereNull('username')->orderBy('score', 'desc')->take(2500)->get();
        $this->last = Customer::where('score', '>', 1)->whereNull('username')->orderBy('score', 'asc')->take(1)->first();

        $this->firstUssd = Client2::where('score', '>', 1)->orderBy('score', 'desc')->take(2500)->get();
        $this->lastUssd = Client2::where('score', '>', 1)->orderBy('score', 'asc')->take(1)->first();

        $this->app = 2500;
        $this->ussd =2500;
        return view('livewire.classement' , ['first'=> $this->first, 'last'=> $this->last,'firstUssd'=> $this->firstUssd,
            'lastUssd'=> $this->lastUssd,'app'=> $this->app,'ussd'=> $this->ussd,'search'=> $this->search ]);
    }
}
