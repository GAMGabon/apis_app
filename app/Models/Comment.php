<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'comment';

    protected $fillable = [
        'status_id',
        'article',
        'numero_client',
        'nom_client',
        'commentaire',
        'status',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
    ];


    public function customer(){
        return $this->belongsTo(Customer::class, 'param1','id');
    }
    public function trans(){
        return $this->belongsTo(Customer::class, 'param2','id');
    }
}
