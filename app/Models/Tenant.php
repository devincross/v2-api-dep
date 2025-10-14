<?php
namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    CONST INTEGRATION_ZOHO = 'zoho';
    CONST INTEGRATION_BYU = 'byu';

    protected $fillable = ['id', 'client_id', 'name', 'email', 'integration', 'automated'];
    protected $guarded = ['created_at', 'updated_at'];
    protected $connection = 'central';

    public function casts()
    {
        return [
            'data' => 'json'
        ];
    }

    public function credentials() {
        return $this->hasMany(Credential::class, 'tenant_id', 'id');
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'client_id',
            'integration',
            'automated'
        ];
    }
}
