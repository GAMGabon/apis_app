<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Communication extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'communications';
    protected $primaryKey = 'id';
    protected $fillable =[
        'title',
        'message',
        'type_communication',
        'people',
        'file',
        'type_file',
        'target',
        'send',
        'status',
        'delivered',
        'open',
        'interaction'

    ];
}
