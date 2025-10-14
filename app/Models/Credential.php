<?php

namespace App\Models;

use App\Models\Traits\Encryptable;
use App\Models\Traits\Json;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    use HasFactory;
    use Encryptable;

    CONST STATUS_ACTIVE = 'current';
    CONST STATUS_DISABLED = 'disabled';

    CONST TYPE_ZOHO = 'zoho';
    CONST TYPE_DEP = 'dep';
    CONST TYPE_NETSUITE = 'netsuite';
    CONST TYPE_SSL = 'ssl';
    CONST TYPE_DATABASE = 'database';

    protected $fillable = ['type', 'status', 'connection_data'];
    protected $guarded = ['id', 'created_at', 'updated_at', 'deleted_at'];
    protected $encryptable = ['connection_data'];
    protected $hidden = ['connection_data'];
    protected $connection = 'tenant';

    public function casts() {
        return ['connection_data' => Json::class];
    }
}
