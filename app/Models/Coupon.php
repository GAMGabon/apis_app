<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Coupon extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'coupons';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'purchase_amount',
        'transaction',
        'coupon_type',
        'validity_date',
        'quantity_coupon',
        'coupon',
        'param1',
        'param2',
        'param3',
        'param4'
    ];
}
