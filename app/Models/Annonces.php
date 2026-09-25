<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Annonces extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    protected $table = 'annonces';

    protected $primaryKey = 'id';

    protected $fillable = [
       
        'nom',
        'titre_annonce',
        'prix',
        'image',
        'listImages',
        'phoneclient',
        'latitude',
        'longitude',
        'id_customer',
        'cat',
        'region',
        'description',
        'whatsapp',
        'ref',
        'boosting',
        'statut',
        'params5',
        'params6',
        'params7',
        'params8',
        'params9',
        'params10',
  
    ];


    
    public function customer() 
      {
        return $this->belongsTo('App\Models\Customer', 'id');
      }

      public function categorie() 
      {
        return $this->belongsTo('App\Models\Categorie', 'id');
      }
   
}
