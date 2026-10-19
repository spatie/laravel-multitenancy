<?php

use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Multitenancy;
use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchRouteCacheTask::class]);
});

it('supports protected queue configuration overrides when booting', function () {
    $multitenancy = new class (app()) extends Multitenancy {
        protected function configureQueue(): static
        {
            config()->set('multitenancy.queues_are_tenant_aware_by_default', false);

            return parent::configureQueue();
        }
    };

    $multitenancy->boot();

    expect(config('multitenancy.queues_are_tenant_aware_by_default'))->toBeFalse();
});

it('will register the queue listeners once for all requests', function () {
    $jobProcessingListeners = count(app('events')->getListeners(JobProcessing::class));
    $jobRetryRequestedListeners = count(app('events')->getListeners(JobRetryRequested::class));

    foreach (['/first', '/second'] as $uri) {
        $request = Request::create($uri);

        event(new RequestReceived(app(), app(), $request));
        event(new RequestTerminated(app(), app(), $request, new Response()));
    }

    expect(app('events')->getListeners(JobProcessing::class))->toHaveCount($jobProcessingListeners)
        ->and(app('events')->getListeners(JobRetryRequested::class))->toHaveCount($jobRetryRequestedListeners);
});

it('forgets the current tenant when an Octane operation ends', function (object $received, object $terminated) {
    event($received);

    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();

    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    event($terminated);

    expect(Tenant::checkCurrent())->toBeFalse()
        ->and(env('APP_ROUTES_CACHE'))->toBeNull();
})->with([
    'request' => [
        fn () => new RequestReceived(app(), app(), Request::create('/')),
        fn () => new RequestTerminated(app(), app(), app('request'), new Response()),
    ],
    'worker error' => [
        fn () => new RequestReceived(app(), app(), Request::create('/')),
        fn () => new WorkerErrorOccurred(new RuntimeException('The client went away'), app()),
    ],
    'concurrent task' => [
        fn () => new TaskReceived(app(), app(), []),
        fn () => new TaskTerminated(app(), app(), [], null),
    ],
    'tick' => [
        fn () => new TickReceived(app(), app()),
        fn () => new TickTerminated(app(), app()),
    ],
]);
