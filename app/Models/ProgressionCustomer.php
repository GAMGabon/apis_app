<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class ProgressionCustomer extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'progression_customers';

    protected $primaryKey = 'id';

    protected $fillable = [
        'idCustomer',
        'phoneCustomer',
        'states',
        'statut',
        'id_phone',
        'date_validity_store',
        'price_phone_reduct',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5'
    ];
}
