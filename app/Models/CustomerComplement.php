<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class CustomerComplement extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'customer_complement';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_customer',
        'phoneclient',
        'nom',
        'statut',
        'naissance',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
        'param9'
    ];

    public function customer(){
        return $this->belongsTo(Customer::class, 'phoneclient','phoneclient');
    }

}
