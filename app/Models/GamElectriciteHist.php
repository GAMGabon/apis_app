<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GamElectriciteHist extends Model
{
    use HasFactory;

    protected $table = 'gam_electricite_hist';
    protected $primaryKey = 'id';
    protected $fillable = [
        'num_client',
        'nom_compteur',
        'num_compteur',
        'montant_transaction',
        'id_transaction',
        'code_transaction',
        'prix_unitaire_kwh',
        'total_unites',
        'consommation',
        'tva',
        'cse',
        'etat',
        'special_key',
        'checked',
        'shared',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
        'param9',
        'param10'
    ];
}
