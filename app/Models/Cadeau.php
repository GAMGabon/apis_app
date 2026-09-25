<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cadeau extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'cadeau';

    protected $fillable = [
      'name','customer','phoneclient','type','description','file','operation','montant','statut','content','trans','validity','param1','param2','param3','param4','param5','param6','param7','param8','param9'
    ];


    public function customer(){
        return $this->belongsTo(Customer::class, 'customer','id');
    }
}
