<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'status';

    protected $fillable = [
        'titre',
        'description',
        'contenu',
        'nom',
        'client_id',
        'vue_demandees',
        'vue_obtenues',
        'nbr_comment',
        'file_type',
        'prix',
        'last_comment',
        'parametre',
        'status',
        'level',
        'whatsapp',
        'phone',
        'www',
        'facebook',
        'file',
        'files',
        'zones',
        'secteurs',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
    ];


    public function comments(){
        return $this->hasMany(Comment::class, 'phoneparent','phoneclient')->orderBy('id', 'desc');
    }


    public function customer(){
        return $this->belongsTo(Customer::class, 'param1','id');
    }
    public function trans(){
        return $this->belongsTo(Customer::class, 'param2','id');
    }
}
