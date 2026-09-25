<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Historiquetrans extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'historiquetrans';

    protected $primaryKey = 'id';

    protected $fillable = [
        'operation',
        'reference',
        'etat',
        'numclient',
        'phonevendeur',
        'content',
        'montant',
        'montant_sans_frais',
        'frais',
        'solde',
        'id_customer',
        'id_parent',
        'id_grand_parent',
        'origine_operation',
        'code_validation',
        'timestamps',
        'tentative_effectue',
        'tentative_autorise',
        'parrainage',
        'old_parrainage',
        'trans',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
        'param9',
    ];
     public function customer(){
        return $this->belongsTo(Customer::class, 'phonevendeur','phoneclient');
    }
    
    public function achat(){
        return $this->belongsTo(GamRechargeHist::class, 'reference','reference');
    }

    public function comments(){
        return $this->hasMany(Comment::class, 'param2','id');
    }
    public function orders(){
        return $this->belongsTo(Orders::class, 'param8','reference');
    }



}
