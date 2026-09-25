<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'contact';

    protected $fillable = [
       	'services',
        'agent',
        'customer',
        'phoneclient',
        'info_customer',
        'trans_customer',
        'module',
        'support',
        'app',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5'
    ];

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer','id');
    }
    public function trans(){
        return $this->belongsTo(Customer::class, 'trans_customer','id');
    }
}
