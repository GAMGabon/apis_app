<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class OrderPushAM extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'order_push_am';

    protected $primaryKey = 'id';
    protected $fillable = [
        'partenaire',
        'status_code',
        'code',
        'airtel_money_id',
        'id_trans',
        'message',
        'all_data',
        'treatment',
        'param1',
        'param2',
        'param3',
        'param4'
    ];
}
