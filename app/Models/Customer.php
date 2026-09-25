<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'customer';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nom',
        'prenom',
        'username',
        'email',
        'pays',
        'color',
        'sexe',
        'phoneclient',
        'phonenumber_assoc1',
        'phonenumber_assoc2',
        'phonenumber_assoc3',
        'reset_password_token',
        'reset_password_sent_at',
        'remember_created_at',
        'confirmation_token',
        'confirmed_at',
        'confirmation_sent_at',
        'mdpclient',
        'mdpcrypt',
        'solde',
        'has_flp_account',
        'flp_account',
        'flp_grade',
        'flp_point_caisse',
        'flp_nbr_transaction',
        'flp_created_at',
        'id_boutique',
        'has_gam_card',
        'num_gam_card',
        'gam_card_ceated_at',
        'etat',
        'avatar',
        'type',
        'pseudo',
        'phoneparent',
        'phonegrandparent',
        'is_partner_credit',
        'failed_attempts',
        'option1',
        'option2',
        'option3',
        'locked_at',
        'last_connexion_at',
        'score',
        'profession',
        'score_credit',
        'is_partner_gamcloud',
        'score_gamcloud',
        'is_partner_ebusiness',
        'score_ebusiness',
        'expirationparrainage',
        'solde_parrainage',
        'team',
        'whatsapp',
        'grade',
        'customer_type',
        'code_confirm',
        'code_confirmed_at',
        'unlock_token',
        'status',
         'statut',
        'filleuls',
        'petit_fils',
        'zone',
        'stock_gab',
        'otp',
        'priority',
        'naissance',
        'Fonction1',
        'Fonction2',
        'Fonction3',
        'Fonction4',
        'Fonction5',
        'Fonction6',
        'Fonction7',
        'Fonction8',
        'contact_at',
        'contry_code',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
        'challenge',
        'my_device',
        'last_transaction_at',
        'last_transaction_amount', 
        'last_transaction_operation',
        'suivi',
        'city',
        'state',
        'actu_current',
        'actu_read',
        'actu_finish',
        'flow_version',
        'info_location',
        'pack_subscription',
        'last_message',
        'last_message_time',
        'no_read',
        'credit',
        'payments',
        'config_sims',
        'classement',
        'versionApp',
        'scoreChallenge',
        'amountChallenge',
    ];


    public function children(){
        return $this->hasMany(Customer::class, 'phoneparent','phoneclient')->orderBy('id', 'desc');
    }

    public function childrens(){
        return $this->hasMany(Customer::class, 'phonegrandparent','phoneclient')->orderBy('id', 'desc');
    }

    public function parrain(){
        return $this->belongsTo(Customer::class, 'phoneclient','phoneparent')->orderBy('id', 'desc');
    }

    public function trans(){
        return $this->hasMany(Historiquetrans::class, 'phonevendeur','phoneclient')->orderBy('updated_at', 'desc');
    }

    public function transChallenge(){
        return $this->hasMany(Historiquetrans::class, 'phonevendeur','phoneclient')->whereNotIn('operation', ['gam_transfert', 'recharge_compte_gam'])->whereNotIn('origine_operation', ['AGENCE_SIGMA'])->whereIn('etat',['CONFIRMEE', 'confirme', 'atraiter', 'en_attente', 'TRAITE'])->orderBy('id', 'desc');
    }

    public function transaction(){
        return $this->belongsTo(Historiquetrans::class, 'phonevendeur','phoneclient')->orderBy('updated_at', 'desc');
    }

    public function program(){
        return $this->belongsTo(ProgramSubscription::class, 'phoneclient','phoneclient');
    }

    public function app(){
        return $this->hasMany(ProgramSubscription::class, 'phoneclient','phoneclient');
    }

    public function comments(){
        return $this->hasMany(Comment::class, 'param1','id');
    }

    public function cartes(){
        return $this->hasMany(Carte::class, 'id_customer','id');
    }

    public function compteurs(){
        return $this->hasMany(Compteur::class, 'num_client','phoneclient');
    }

    public function electricity(){
        return $this->hasMany(GamElectriciteHist::class, 'num_client','phoneclient')->orderBy('updated_at', 'desc');
    }

    public function boxes(){
        return $this->hasMany(Box::class, 'id_client','id');
    }

    public function annonces(){
        return $this->hasMany(Box::class, 'id_client','id')->orderBy('updated_at', 'desc');
    }

    public function ussdTrans(){
        return $this->hasMany(GamRechargeHist::class, 'phonevendeur','phoneclient')->orderBy('id', 'desc');
    }

    public function complement(){
        return $this->belongsTo(CustimerComplement::class, 'phoneclient','phoneclient');
    }


}
