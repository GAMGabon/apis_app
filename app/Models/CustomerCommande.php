<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCommande extends Model
{
    use HasFactory,SoftDeletes;
    
    protected $primaryKey = 'id';
    protected $table = 'customers_commandes';

    protected $fillable = [
        'customer',
        'vendeur',
        'nom_commande',
        'zone',
        'details',
        'plafond',
        'post',
        'status_commande',
        'montant',
        'latitude',
        'longitude',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5'
    ];

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer','id');
    }

    public function vendeur(){
        return $this->belongsTo(Customer::class, 'vendeur','id');
    }
}
