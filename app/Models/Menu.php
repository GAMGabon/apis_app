<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Menu extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'menus';

    protected $primaryKey = 'id';

    protected $fillable = [
        'title',
        'subtitle',
        'logo',
        'description',
        'code',
        'status',
        'color',
        'promo',
        'text_promo',
        'delai_promo',
        'file',
        'message',
        'file_message',
        'code',
        'param1',
        'param2',
        'param3'
    ];
}
