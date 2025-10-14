<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    CONST STATUS_WAITING = 'waiting';
    CONST STATUS_PENDING = 'pending';
    CONST STATUS_SUBMITTED = 'submitted';
    CONST STATUS_COMPLETE = 'complete';
    CONST STATUS_ERROR = 'error';
    CONST STATUS_CHANGES = 'changes';

    CONST SOURCE_ZOHO = 'zoho';
    CONST SOURCE_NETSUITE = 'netsuite';
    CONST SOURCE_MANUAL = 'manual';

    protected $fillable = [
        'order_id', 'account_id', 'external_order_id', 'external_account_id', 'external_order_status',
        'status', 'po', 'changes', 'dep_order_id', 'dep_ordered_at', 'dep_shipped_at', 'source'
    ];
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function transactions() {
        return $this->belongsToMany(Transaction::class, 'order_transaction', 'order_id', 'transaction_id')->withTimestamps();
    }

    public function products() {
        return $this->hasMany(Product::class, 'order_id', 'id');
    }

    public function account() {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model)
        {
            $model->order_id = Str::uuid();
            $model->status = self::STATUS_PENDING;
        });
    }
}
