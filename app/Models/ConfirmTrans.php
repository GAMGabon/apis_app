<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ConfirmTrans extends Model
{
    use HasApiTokens, HasFactory;

    protected $table = 'confirmtrans';

    protected $primaryKey = 'id';

    protected $fillable = [
        'reference',
        'etat',
        'phonevendeur',
        'content',
        'montant',
        'port',
        'autre',
        'sender',
        'date',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
    ];
}
