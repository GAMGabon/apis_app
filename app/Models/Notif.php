<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notif extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'notif';

    protected $fillable = [
        'demandeur',
        'titre' ,
        'content',
        'icon',
        'type' ,
        'operation',
        'receiver',
        'nom',
        'operateur',
        'montant',
        'pays',
        'compteur',
        'codepays',
        'notified',
        'param1',
        'param2',
        'param3',
        'param4',
        'param4',
        'param5',
        'param6',
        'param7',
    ];
}
