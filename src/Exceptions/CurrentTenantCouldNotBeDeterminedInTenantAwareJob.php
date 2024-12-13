<?php

namespace Spatie\Multitenancy\Exceptions;

use Exception;
use Illuminate\Queue\Events\JobProcessing;

class CurrentTenantCouldNotBeDeterminedInTenantAwareJob extends Exception
{
    public static function noIdSet(JobProcessing $event)
    {
        return new static("The current tenant could not be determined in a job named `" . $event->job->resolveName() . "`. No `tenantId` was set in the payload.");
    }

    public static function noTenantFound(JobProcessing $event): static
    {
        return new static("The current tenant could not be determined in a job named `" . $event->job->resolveName() . "`. The tenant finder could not find a tenant.");
    }
}
