<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Offre de revente d'actions EMOTION mise en ligne par un actionnaire.
 * statut : en_vente | vendu | annule
 */
class VenteAction extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'vente_actions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'vendeur',
        'vendeur_phone',
        'projet_action_id',
        'projet',
        'nbr_action',
        'nbr_action_vendu',
        'price_action',
        'statut',
        'param1',
        'param2'
    ];
}
