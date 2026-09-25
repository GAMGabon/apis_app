<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimInternal extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'simInternationales';

    protected $fillable = [
         'msisdn_esim',
        'type',
        'Iccd',
        'customerId',
        'customerNum',
        'pin1',
        'puk1',
        'activationCode',
        'qr_code',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
    ];

}
