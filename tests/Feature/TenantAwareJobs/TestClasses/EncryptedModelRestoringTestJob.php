<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Contracts\Queue\ShouldBeEncrypted;

class EncryptedModelRestoringTestJob extends ModelRestoringTestJob implements ShouldBeEncrypted
{
}
