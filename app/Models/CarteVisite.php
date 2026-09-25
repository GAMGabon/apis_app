<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class CarteVisite extends Model {
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'carte_visite';

    protected $primaryKey = 'id';
     protected $fillable = [
        'nom',
        'phone',
        'whatsapp',
        'customer_id',
        'email',
        'domicile',
        'logo',
        'color',
        'site',
        'fbk',
        'instagramm',
        'poste',
        'entreprise',
        'tiktok',
        'linkdin',
        'partage',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
        'param9',
    ];

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id','id');
    }
}
