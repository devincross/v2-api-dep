<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = ['external_account_id', 'name', 'dep_account_id'];
    protected $guarded = ['id', 'created_at', 'updated_at'];
}
