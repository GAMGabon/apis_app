<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CardOrder extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'order_card';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_customer',
        'nom',
        'birthday',
        'nationality',
        'type_identity',
        'num_identity',
        'photo_identity',
        'tel',
        'adress',
        'email',
        'has_credit_card',
        'verified',
        'delivery_info',
        'id_vendeur',
        'register_by',
        'autorised_by',
        'buy_by',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',

    ];
}
