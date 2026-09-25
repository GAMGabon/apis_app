<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Diffusion extends Model
{

    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'diffusions';
    protected $primaryKey = 'id';
    protected $fillable =[
        'com',
        'title',
        'people',
        'target',
        'start',
        'send',
        'status',
        'delivered',
        'open',
        'interaction',
        'data_interaction',
        'comment',
        'param1',
        'param2',
        'param3',
        'param4'
    ];
}
