<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForfaitInternational extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'forfait_international';

    protected $fillable = [
        'country',
        'continent',
        'forfait_country',
        'price_forfait',
        'nomenclature',
        'param1',
        'param2',
        'param3',
        'param4',
    ];

}
