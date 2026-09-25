<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Payment extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'payments';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'subname',
        'logo',
        'code',
        'status',
        'push',
        'not_supported',
        'param1',
        'param2',
        'param3',
        'param4'
    ];
}
