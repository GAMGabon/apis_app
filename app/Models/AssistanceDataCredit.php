<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class AssistanceDataCredit extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'assistance_data_credit';

    protected $fillable = [
        'customer_id',
        'customer_phone',
        'sims',
        'solde',
        'last_activity',
        'defaut_forfait',
        'defaut_credit',
        'defaut_mega',
        'defaut_wifi',
        'depannage',
        'operateur',
        'ref_recharge',
        'number_recharge',
        'amount_recharge',
        'param1',
        'param2',
        'param3',
        'call_time',
        'call_time_recharge',
        'call_time_validity',
        'call_time_change',
        'forfait_qte',
        'forfait_qte_recharge',
        'forfait_qte_change',
        'forfait_last_verify_at'
    ];
}
