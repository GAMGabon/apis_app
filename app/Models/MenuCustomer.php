<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class MenuCustomer extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'menu_customer';

    protected $primaryKey = 'id';

    protected $fillable = [
        'menu',
        'customer',
        'score',
        'code',
        'status',
        'last',
        'amount',
        'param1',
        'param2',
        'param3',
        'param4'
    ];
}
