<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'devices';

    protected $fillable = [
        'marque',
        'model',
        'id_device',
        'id_customer',
        'phoneclient',
        'status',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
    ];


    public function customer(){
        return $this->belongsTo(Customer::class, 'id_customer','id');
    }

}
