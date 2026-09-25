<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class WifiUdm extends Model
{
    use SoftDeletes;

    protected $table = 'wifi_udm';


    protected $primaryKey = 'id';
    protected $fillable = [
        'code',
        'montant',
        'statut',
        'dure',
        'date_debut',
        'date_fin',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
    ];

   
}
