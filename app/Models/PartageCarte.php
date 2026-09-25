<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PartageCarte extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'partage_carte';

    protected $primaryKey = 'id';

    protected $fillable = [

        'carte_id',
        'customer_id',
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

    public function Partage(){
        return $this->hasMany(CarteVisite::class, 'id','carte_id')->orderBy('id', 'desc');
    }

    public function cartes(){
        return $this->hasMany(Customer::class, 'id','customerID')->orderBy('id', 'desc');
    }

}
