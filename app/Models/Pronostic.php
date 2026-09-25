<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pronostic extends Model
{
    use HasApiTokens, Notifiable, SoftDeletes, HasFactory;
    protected $table = 'pronostique';
    protected $primaryKey = 'id';

    protected $fillable = [
        'customerId',
        'phone',
        'status',
        'gains',
        'pronostique',
        'param1',
        'param2',
        'param3',
        'param4',
    ];
}
