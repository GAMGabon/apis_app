<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pvit extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'pvit_backup';
    protected $primaryKey = 'id';
    protected $fillable = [
        "treatment",
        "transactionId",
        "merchantReferenceId",
        "status",
        "customerID",
        "fees",
        "amount",
        "totalAmount",
        "chargeOwner",
        "freeInfo",
        "transactionOperation",
        "code",
        "operator",
        "accountOperationCode",
        "operatorOwnerCharge",
        "operatorFees",
        "amountCredited",
        "param1",
        "param2",
        "param3",
        "param4",
        "all_data"
    ];
}
