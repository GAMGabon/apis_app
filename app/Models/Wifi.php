<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Wifi extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'wifi';

    protected $primaryKey = 'id';

    protected $fillable = [
        'code',
        'montant',
        'statut',
        'dure',
        'date_debut',
        'date_fin',
        'param2',
        'param3',
        'param4',
        'param5'
    ];
}
