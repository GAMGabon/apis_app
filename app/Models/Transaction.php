<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Transaction extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'transaction';

    protected $primaryKey = 'id';

    protected $fillable = [
        'montant',
        'quartier',
        'agent',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5'
    ];
}
