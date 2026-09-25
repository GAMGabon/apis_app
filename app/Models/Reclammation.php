<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Reclammation extends Model
{
    use HasApiTokens, Notifiable, SoftDeletes, HasFactory;

     protected $table = 'reclammation';

     protected $primaryKey = 'id';

     protected $fillable = [
         'type_reclammation',
         'objet',
         'operation',
         'phoneclient',
         'phonebeneficiaire',
         'message',
         'piece_jointe',
         'etiquete',
         'statut',
         'preuve_texte',
         'preuve_file',
         'date_cloture',
         'clotured_by',
         'rate',
         'id_member',
         'id_customer',
         'notified',
         'param1',
         'param2',
         'param3',
         'param4',
         'param5',
         'performance',
         'comment',
         'avis'
     ];
}
