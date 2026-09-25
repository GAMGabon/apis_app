<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Soldes extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'solde';
    protected $primaryKey = 'id';
    protected $fillable = [
        'client',
        'produit',
        'solde',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
        'param9',
        'param10',
    ];


}
