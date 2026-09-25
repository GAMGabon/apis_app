<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lot extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'lot';

    protected $fillable = [
        'nom',
        'photo',
        'description',
        'gallerie',
        'quantite',
        'challenge',
        'rang_gagnant',
        'challenge_for',
        'status',
        'param2',
        'param3',
        'param4',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
        'param9',
    ];
}
