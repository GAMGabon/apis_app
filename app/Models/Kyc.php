<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Kyc extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'kyc';

    protected $primaryKey = 'id';


    protected $fillable = [

        'phonevendeur',
        'whatsapp',
        'typphone',
        'provenance',
        'point',
        'arrive_le',
        'sondage',
        'sms',
        'contacte_via',
        'rdv_le',
        'produits',
        'contacte_par',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'fonction1',
        'idwhatsapp'
    ];

}