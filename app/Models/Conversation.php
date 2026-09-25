<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Conversation extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'conversations';

    protected $primaryKey = 'id';

    protected $fillable = [
        'customer_phonestatus', 'users', 'usersDatas', 'customer_id', 'customer_phone', 'created_by', 'name', 'no_read', 'customer_whatsapp', 'param1', 'param2', 'param3', 'param4', 'param5', 'last_message', 'last_message_time', 'type'
    ];
}
