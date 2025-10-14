<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepError extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'dep_errors';
    protected $fillable = ['order_id', 'request_id', 'product_id', 'error_code', 'error'];
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public function orders() {
        return $this->belongsTo(Order::class,'order_id', 'id');
    }

    public function statusRequest() {
        return $this->belongsTo(DepStatus::class, 'request_id', 'id');
    }

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
