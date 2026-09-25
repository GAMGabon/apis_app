<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Carte extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'carte_information';

    protected $primaryKey = 'id';

    protected $fillable = [
        'owner',
        'names',
        'num_card',
        'pseudo_card',
        'state',
        'treatment',
        'price',
        'commission',
        'seuil_transaction',
        'expire_by',
        'id_customer',
        'id_agent',
        'order_where',
        'paiement_way',
        'comment',
        'id_order',
        'id_client',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',

    ];
}
