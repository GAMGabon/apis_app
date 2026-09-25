<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppVerify extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'whatapp_verifiy';

    protected $fillable = [
        "number","status","customerId","whatsAppName","param1","param2","param3","param4","param5","param6","param7"
    ];
}
