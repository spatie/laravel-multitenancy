<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

class NonDeletingModelRestoringTestJob extends ModelRestoringTestJob
{
    public bool $deleteWhenMissingModels = false;
}
