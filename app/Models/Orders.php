<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'outlet',
        'reference',
        'email',
        'currency',
        'amount',
        'partenaire',
        'transId',
        'status',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',

    ];
}
