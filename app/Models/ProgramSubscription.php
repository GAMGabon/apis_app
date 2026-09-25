<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramSubscription extends Model
{
    use HasFactory;
    protected $table = 'program_subscription';

    protected $primaryKey = 'id';

    protected $fillable = [
        'phoneclient',
        'program_id',
        'program_name',
        'dead_line',
        'friend_subs',
        'state',
        'sub_contribution',
        'apport_contribution',
        'gift_contribution',
        'total_contribution',
        'param1',
        'param2',
        'param3',
        'param4',
        'param5',
        'param6',
        'param7',
        'param8',
        'param9',
        'param10',
    ];
    
    public function customer(){
        return $this->belongsTo(Customer::class, 'phoneclient','phoneclient');
    }
}
