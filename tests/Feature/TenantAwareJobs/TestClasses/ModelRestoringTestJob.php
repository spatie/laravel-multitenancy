<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Queue\SerializesModels;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Valuestore\Valuestore;

class ModelRestoringTestJob extends TestJob
{
    use SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        Valuestore $valuestore,
        public Tenant $model,
    ) {
        parent::__construct($valuestore);
    }
}
