<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Client2 extends Model
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;

    protected $table = 'client2';

    protected $primaryKey = 'num';

    protected $fillable = [
        'numclient',
        'score',
        'last_trans',
        'created_at',
        'reference',
        'numexpediteur',
        'grade',
        'etat',
        'whatsapp',
        'count_recap',
        'my_frequence_recap',
        'my_status_recap',
        'send_recap_at',
        'transaction_ID',
        'external_Reference',
        'reference_Number',
        'transaction_Date',
        'sender_Mobile_Number',
        'receiver_Mobile_Number',
        'transaction_Amount',
        'previous_Balance',
        'post_Balance',
        'service_Type',
        'status',
        'contact_at',
        'count_reminder',
        'count_pack',
        'call_center',
        'etat_pack',
        'comment',
        'contact_pack',
        'last_trans_pack',
        'last_amount_pack',
        'flow_version',
        'parrain',
        'expirationparrainage',
        'conf_send_data',
        'soldeparrainage',
        'soldeparrainagefilleul',
        'classement',
        'challenge',
        'gampay',
        'name',
        'zone',
        'num_receveur',
        'last_reference',
        'states_stores',
        'date_validity_store',
        'idPhone'
    ];
    
}
