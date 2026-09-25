<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ErrorApp extends Model
{ use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'error_app';

    protected $primaryKey = 'id';

    protected $fillable = [

        'customer', 'phoneclient', 'operation','message','payment','app','version',
        'operateur',	'montant',	'type_customer',	'param1',	'param2',
        'param3',	'param4',	'param5'

    ];
}
