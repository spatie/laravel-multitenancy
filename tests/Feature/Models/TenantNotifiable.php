<?php
namespace Spatie\Multitenancy\Tests\Feature\Models;

use Illuminate\Notifications\Notifiable;
use Spatie\Multitenancy\Models\Tenant;

class TenantNotifiable extends Tenant
{
    use Notifiable;

    protected $table = 'tenants';

    protected $appends = [
        'email',
    ];

    public function getEmailAttribute()
    {
        return 'test@spatie.be';
    }
}
