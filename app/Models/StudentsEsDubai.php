<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class StudentsEsDubai extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'students_es_dubai';

    protected $primaryKey = 'id';

    protected $fillable = [
        'customer',
        'montant',
        'nom',
        'prenom',
        'created_at',
        'nationalite',
        'statut',
        'photo',
        'email',
        'code_pays',
        'numero',
        'niveau',
        'cours',
        'programme',
        'date_naissance',
        'periode',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
        'param9'
    ];
}
