<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Projet extends Model
{
    use HasApiTokens, Notifiable, SoftDeletes, HasFactory;
    protected $table = 'projets';
    protected $primaryKey = 'id';

    protected $fillable = [
        'action_achete',
        'nom',
        'description',
        'file',
        'date_lancement',
        'date_versement',
        'nbr_action',
        'nbr_action_achete',
        'price_action',
        'total_budget',
        'details_budget',
        'recette_day',
        'recette_month',
        'recette_year',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5'
    ];
}
