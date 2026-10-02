<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Achat (réservation puis paiement) d'une partie d'une offre de revente.
 * statut : reserve | paye | annule | a_rembourser
 */
class VenteActionAchat extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'vente_action_achats';
    protected $primaryKey = 'id';

    protected $fillable = [
        'vente_action',
        'acheteur',
        'acheteur_phone',
        'nbr_action',
        'price_action',
        'montant',
        'statut',
        'id_trans',
        'paye_at',
        'param1',
        'param2'
    ];
}
