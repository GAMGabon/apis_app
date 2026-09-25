<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Followers extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'followers';

    protected $primaryKey = 'id';

    protected $fillable = [
        'phone',
        'name',
        'reseau',
        'param1',
        'param2',
        'param3',
        'param4'
    ];

}
