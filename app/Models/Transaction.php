<?php

namespace App\Models;

use App\Models\Traits\Json;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    CONST RESPONSE_STATUS_COMPLETE = "complete";
    CONST RESPONSE_STATUS_ERROR = "error";

    protected $table = 'transactions';
    protected $fillable = ['transaction_id', 'call_transaction_id', 'call', 'payload', 'response', 'response_code', 'response_msg', 'response_status', 'response_at'];
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $casts = ['payload' => Json::class, 'response' => Json::class];

    public function orders()
    {
        return $this->belongsToMany(Order::class,'order_transaction','transaction_id','order_id')->withTimestamps();
    }
}
