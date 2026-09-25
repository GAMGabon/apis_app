<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForfaitDataCredit extends Model
{
    use SoftDeletes, HasFactory;
    protected $table = 'forfait_data_credit';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'validity',
        'price',
        'price_offline',
        'operateur',
        'qte_credit',
        'validity_credit',
        'qte_flex',
        'validity_flex',
        'qte_mega',
        'validity_mega',
        'qte_min',
        'validity_min',
        'ticket_wifi',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5'
    ];
}
