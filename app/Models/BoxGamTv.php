<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BoxGamTv extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'box';

    protected $primaryKey = 'id';


    protected $fillable = [
        'id_client',
        'num_box',
        'nom_box',
        'abonnement',
        'date_debut',
        'date_fin',
        'statut',
        'option1',
        'option2',
        'option3',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',

    ];



}
