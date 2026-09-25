<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PaymentCustomer extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'payment_customer';

    protected $primaryKey = 'id';

    protected $fillable = [
        'payment',
        'customer',
        'score',
        'code',
        'status',
        'last',
        'last_amount',
        'param1',
        'param2',
        'param3'
    ];
}
