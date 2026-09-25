<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CarteAnnuaire extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'carte_annuaire';

    protected $primaryKey = 'id';

    protected $fillable = [
        'num_carte',
        'quatre_chiffres',
        'account_number',
        'date',
        'state',
        'id_carte_information',
        'id_order_card',
        'id_customer',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',

    ];
}
