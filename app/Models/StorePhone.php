<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class StorePhone extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'store_phones';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'description',
        'file',
        'price',
        'pts_ussd',
        'pts_pack',
        'pts_app',
        'statut',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
    ];
}
