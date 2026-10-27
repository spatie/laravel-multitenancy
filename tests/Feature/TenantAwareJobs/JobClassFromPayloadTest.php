<?php

use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Queue\Events\JobProcessing;
use Spatie\Multitenancy\Actions\MakeQueueTenantAwareAction;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotificationNotTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotificationTenantAware;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\NotTenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TenantAwareTestJob;
use Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses\TestJob;

beforeEach(function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);
});

/*
 * The command in these payloads can't be decrypted or unserialized, so they
 * only pass when the job class is read from the payload instead.
 */
function isTenantAwareFromPayload(array $payload): bool
{
    $job = Mockery::mock(QueueJobContract::class);
    $job->allows('payload')->andReturn($payload);

    $action = new class () extends MakeQueueTenantAwareAction {
        public function determine(JobProcessing $event): bool
        {
            return $this->isTenantAware($event);
        }
    };

    return $action->determine(new JobProcessing('database', $job));
}

function payloadWithoutUsableCommand(string $commandName, ?string $displayName = null): array
{
    return [
        'displayName' => $displayName ?? $commandName,
        'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
        'data' => [
            'commandName' => $commandName,
            'command' => 'this command cannot be unserialized',
        ],
    ];
}

it('reads a tenant aware job from the payload', function () {
    expect(isTenantAwareFromPayload(payloadWithoutUsableCommand(TenantAwareTestJob::class)))->toBeTrue();
});

it('reads a not tenant aware job from the payload', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    expect(isTenantAwareFromPayload(payloadWithoutUsableCommand(NotTenantAwareTestJob::class)))->toBeFalse();
});

it('applies the job config lists to the class from the payload', function () {
    config()->set('multitenancy.tenant_aware_jobs', [TestJob::class]);

    expect(isTenantAwareFromPayload(payloadWithoutUsableCommand(TestJob::class)))->toBeTrue();
});

it('reads the wrapped notification from the display name', function () {
    config()->set('multitenancy.queues_are_tenant_aware_by_default', true);

    expect(isTenantAwareFromPayload(payloadWithoutUsableCommand(SendQueuedNotifications::class, NotificationNotTenantAware::class)))
        ->toBeFalse();

    config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

    expect(isTenantAwareFromPayload(payloadWithoutUsableCommand(SendQueuedNotifications::class, NotificationTenantAware::class)))
        ->toBeTrue();
});

it('unserializes a wrapper whose display name is not a class', function () {
    isTenantAwareFromPayload(payloadWithoutUsableCommand(CallQueuedClosure::class, 'Closure (routes/web.php:12)'));
})->throws(Illuminate\Contracts\Encryption\DecryptException::class);
