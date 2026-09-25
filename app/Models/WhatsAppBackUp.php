<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppBackUp extends Model
{
    use HasFactory,SoftDeletes;
    protected $primaryKey= 'id';
    protected $table = 'whatsapp_backup';

    protected $fillable = [
       'messageId','sender','channel','recipeint','message','price','status','send_at','done_at','seen_at','bulkId','error', 'offre','service','app','trafic','treatment','param1','param2','param3','param4','param5','param6','param7',
    ];
}
