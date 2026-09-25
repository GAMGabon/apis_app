<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjetAction extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'projet_actions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'projet_action',
        'customer',
        'customer_nbr_action',
        'recette_day_action',
        'recette_month_action',
        'recette_year_action',
        'delay_action',
        'pourcentage_action',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5'
    ];
}
