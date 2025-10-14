<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepStatus extends Model
{
    use HasFactory;

    CONST STATUS_PENDING = 'pending';
    CONST STATUS_COMPLETE = 'complete';

    protected $table = "request_status";
    protected $fillable = ['transaction_id', 'status', 'last_ran_at'];
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function orders() {
        return $this->belongsToMany(Order::class, 'order_dep_request', 'request_id', 'order_id')->withTimestamps();
    }
}
