<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetatilsOperation extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'details_operations';

    protected $fillable = [
        'operation',
        'qte',
        'name',
        'status',
        'validity',
        'transaction_id',
        'numclient',
        'operateur',
        'reference',
        'param1',
        'param2',
        'param3',
        'param4',
    ];
}
