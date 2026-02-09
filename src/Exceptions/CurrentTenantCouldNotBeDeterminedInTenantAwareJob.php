<?php

namespace Spatie\Multitenancy\Exceptions;

use Exception;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;

class CurrentTenantCouldNotBeDeterminedInTenantAwareJob extends Exception
{
    public static function noIdSet(JobProcessing|JobRetryRequested $event)
    {
        $jobName = self::resolveJobName($event);

        return new static("The current tenant could not be determined in a job named `{$jobName}`. No `tenantId` was set in the payload.");
    }

    public static function noTenantFound(JobProcessing|JobRetryRequested $event): static
    {
        $jobName = self::resolveJobName($event);

        return new static("The current tenant could not be determined in a job named `{$jobName}`. The tenant finder could not find a tenant.");
    }

    protected static function resolveJobName(JobProcessing|JobRetryRequested $event): string
    {
        if ($event instanceof JobProcessing) {
            return $event->job->resolveName();
        }

        $payload = $event->payload();

        return $payload['displayName'] ?? $payload['data']['commandName'] ?? 'unknown';
    }
}
