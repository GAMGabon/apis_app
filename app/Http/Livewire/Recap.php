<?php

namespace App\Http\Livewire;
use App\Models\Customer;
use App\Models\Historiquetrans;
use Illuminate\Support\Facades\Route;
use Livewire\Component;

class Recap extends Component
{

        public $phone;
        public $transOut;
        public $montanTransOut;
        public $transIn;
        public $montanTransIn;
        public $partenaire;
        public $all;
        public $tab;
        public $periode;
        public $search = '';
        public $debut = '';
        public $fin = '';
        public $myDate;


    public function render(){


        if($_GET != null){
            $token = $_GET['rapport_ref'];
            $id=substr($token,5);
            $this->partenaire = Customer::where('id', $id)->first();
        }

        $this->phone = $this->partenaire->phoneclient;
        $numSansZero = substr($this->phone, 1);
        $numeroZero =  $this->phone;

        $this->myDate = date('Y-m-d');

        if($this->debut != '' && $this->fin != ''){
            $dateDebut = $this->debut.' 00:00:00';
            $dateFin = $this->fin.' 23:59:59';
            setlocale(LC_TIME, "fr_FR");
            $this->periode = strval(strftime("%d %h %Y", strtotime($this->debut))).' - '.strval(strftime("%d %h %Y", strtotime($this->fin))) ;
        }else{
            $dateDebut = $this->myDate.' 00:00:00';
            $dateFin = $this->myDate.' 23:59:59';
          
            setlocale(LC_TIME, "fr_FR");
            $this->periode = strval(strftime("%d %h %Y", strtotime(date('Y-m-d'))));
        }
        
        $this->debut = $dateDebut;
        $this->fin = $dateFin;

        if($this->search != ''){
            $a = $this->search;
            $this->transOut = Historiquetrans::where('phonevendeur', $this->phone)->whereNotIn('etat', ['attend', 'en_agence_attend'])->where(function($q) use($a) {
                $q->where('numclient', 'like', "%{$a}%")->orWhere('montant_sans_frais', 'like', "%{$a}%")->orWhere('content', 'like', "%{$a}%");
            })->where('created_at', 'like', ''.$this->myDate.'%')->whereBetween('created_at', [$dateDebut, $dateFin])->get();

            $this->montanTransOut = Historiquetrans::where('phonevendeur',$this->phone)->whereNotIn('etat', ['attend', 'en_agence_attend'])->where(function($q) use($a) {
                $q->where('numclient', 'like', "%{$a}%")->orWhere('montant_sans_frais', 'like', "%{$a}%")->orWhere('content', 'like', "%{$a}%");
            })->whereBetween('created_at', [$dateDebut, $dateFin])->sum('montant_sans_frais');

            $this->all = Historiquetrans::where('phonevendeur',$numeroZero)->where(function($q) use($a) {
                $q->where('numclient', 'like', "%{$a}%")->orWhere('montant_sans_frais', 'like', "%{$a}%")->orWhere('content', 'like', "%{$a}%");;
            })->whereNotIn('etat', ['attend', 'en_agence_attend'])->whereBetween('created_at', [$dateDebut, $dateFin])->orderBy('id', 'desc')->get();

        }else{
            $this->transOut = Historiquetrans::where('phonevendeur', $this->phone)->whereNotIn('etat', ['attend', 'en_agence_attend'])->whereBetween('created_at', [$dateDebut, $dateFin])->get();
            $this->montanTransOut = Historiquetrans::where('phonevendeur',$this->phone)->whereNotIn('etat', ['attend', 'en_agence_attend'])->whereBetween('created_at', [$dateDebut, $dateFin])->sum('montant_sans_frais');

            $this->all = Historiquetrans::where(function($q) use($numeroZero,$numSansZero) {
                $q->whereIn('numclient', [$numSansZero, $numeroZero])->orWhere('phonevendeur',$numeroZero);
            })->whereNotIn('etat', ['attend', 'en_agence_attend'])->whereBetween('created_at', [$dateDebut, $dateFin])->orderBy('id', 'desc')->get();
        }

        $this->transIn = Historiquetrans::whereIn('numclient', [$this->phone, $numSansZero])->whereIn('operation', ['gam_transfert', 'recharge_compte_gam'])->whereNotIn('etat', ['attend', 'en_agence_attend'])->whereBetween('created_at', [$dateDebut, $dateFin])->get();
        $this->montanTransIn = Historiquetrans::whereIn('numclient',[$this->phone, $numSansZero])->whereIn('operation', ['gam_transfert', 'recharge_compte_gam'])->whereNotIn('etat', ['attend', 'en_agence_attend'])->whereBetween('created_at', [$dateDebut, $dateFin])->sum('montant');

        $this->tab = [
            'transOut'=>$this->transOut,
            'montanTransOut'=>$this->montanTransOut,
            'transIn'=>$this->transIn,
            'montanTransInt'=>$this->montanTransIn,
        ];

        return view('livewire.recap');
    }
}
