<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Notification extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'notif';

    protected $primaryKey = 'id';

    protected $fillable = [
        'demandeur',
        'type',
        'content',
        'icon',
        'titre',
        'operation',
        'receiver',
        'nom',
        'operateur',
        'montant',
        'pays',
        'codepays',
        'notified',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
    ];
//     public function customer(){
//        return $this->belongsTo(Customer::class, 'phonevendeur','phoneclient');
//     }

//    public function comments(){
//        return $this->hasMany(Comment::class, 'param2','id');
//    }
}
