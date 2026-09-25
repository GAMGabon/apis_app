<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GamRechargeHist extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'gamrechargehist';

    protected $fillable = [
        'numerecharge',
        'username',
        'password',
        'sender',
        'reciever',
        'port',
        'charset2',
        'content',
        'reference',
        'autre',
        'etat',
        'heure',
        'date',
        'montant',
        'phonevendeur',
        'numeroclient',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
    ];

    public function customer(){
        return $this->belongsTo(Customer::class, 'phonevendeur','phoneclient');
    }

}
