<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeviceInternal extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'devicesinternational';

    protected $fillable = [
        'systeme',
        'type',
        'marque',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
    ];

}
