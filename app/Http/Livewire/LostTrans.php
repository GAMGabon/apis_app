<?php

namespace App\Http\Livewire;

use App\Models\Customer;
use App\Models\Historiquetrans;
use Livewire\Component;

class LostTrans extends Component
{
    public $search ="";
    public $lieu =" ";
    public $trans;
    public $transTraites;
    public $lots;


    public function render()
    {
        if ($this->search <> ""){
            $this->trans =Historiquetrans::where('phonevendeur', 'like', "%{$this->search}%")->orWhere('reference', 'like', "%{$this->search}%")->orderBy('id', 'desc')->take(20)->get();
        }else{
            $this->trans = historiquetrans::whereNotIn('etat',['en_attente', 'CONFIRMEE','atraiter','confirme', 'echec','TRAITE', 'en_agence', 'a_valider', 'a_traiter', 'QR', 'attend'])->whereNotIn('phonevendeur', ['074773997', '077098737','077708669','062238888', '076334437','076334438','076334434', '076334431','076334457','076069934','066449422','066283522'])->where('id', '>', 33742413)->take(50)->orderBy('id', 'desc')->get();
        }
        $this->transTraites =  Historiquetrans::where('timestamps', strval(date('Y_m_d')))->get();
        return view('livewire.lost-trans', ['incidents'=>$this->trans, 'search'=>$this->search, 'traites'=>$this->transTraites ]);
    }
}
