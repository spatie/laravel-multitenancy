<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Spatie\Multitenancy\Jobs\TenantAware;

class EncryptedTenantAware extends TestJob implements TenantAware, ShouldBeEncrypted
{
}
