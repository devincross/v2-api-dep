<?php

namespace App\Models;

use App\Models\Traits\Json;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{
    protected $fillable = ['service', 'access_token', 'refresh_token', 'expires_at', 'refresh_expires_at'];
    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $connection = 'tenant';

    public function casts() {
        return [
            'expires_at' => 'Date',
            'refresh_expires_at' => 'Date'
        ];
    }
}
