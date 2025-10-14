<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    CONST STATUS_PENDING = 'pending';
    CONST STATUS_SUBMITTED = 'submitted';
    CONST STATUS_COMPLETE = 'complete';
    CONST STATUS_ERROR = 'error';
    CONST STATUS_CHANGES = 'error';
    CONST STATUS_DELETE = 'delete';

    protected $fillable = ['order_id', 'is_dep', 'serial_number', 'dep_status'];
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
