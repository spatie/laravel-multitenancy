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
use Spatie\Multitenancy\Tasks\SwitchRouteCacheTask;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    config()->set('multitenancy.switch_tenant_tasks', [SwitchRouteCacheTask::class]);
});

function receiveRequest(): Request
{
    $request = Request::create('/');

    event(new RequestReceived(app(), app(), $request));

    return $request;
}

function terminateRequest(Request $request): void
{
    event(new RequestTerminated(app(), app(), $request, new Response()));
}

function failRequest(): void
{
    event(new WorkerErrorOccurred(new RuntimeException('The client went away'), app()));
}

it('will register the queue listeners once for all requests', function () {
    $jobProcessingListeners = count(app('events')->getListeners(JobProcessing::class));
    $jobRetryRequestedListeners = count(app('events')->getListeners(JobRetryRequested::class));

    terminateRequest(receiveRequest());
    terminateRequest(receiveRequest());

    expect(app('events')->getListeners(JobProcessing::class))->toHaveCount($jobProcessingListeners)
        ->and(app('events')->getListeners(JobRetryRequested::class))->toHaveCount($jobRetryRequestedListeners);
});

it('will forget the current tenant when the request terminates', function () {
    $request = receiveRequest();

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    terminateRequest($request);

    expect(Tenant::checkCurrent())->toBeFalse()
        ->and(env('APP_ROUTES_CACHE'))->toBeNull();
});

it('will forget the current tenant when the worker fails before the request terminates', function () {
    receiveRequest();

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    failRequest();

    expect(Tenant::checkCurrent())->toBeFalse()
        ->and(env('APP_ROUTES_CACHE'))->toBeNull();
});

it('will run the switch tenant tasks in a concurrent task and forget the tenant when the task terminates', function () {
    event(new TaskReceived(app(), app(), []));

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    event(new TaskTerminated(app(), app(), [], null));

    expect(Tenant::checkCurrent())->toBeFalse()
        ->and(env('APP_ROUTES_CACHE'))->toBeNull();
});

it('will run the switch tenant tasks in a tick and forget the tenant when the tick terminates', function () {
    event(new TickReceived(app(), app()));

    /** @var \Spatie\Multitenancy\Models\Tenant $tenant */
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();
    expect(env('APP_ROUTES_CACHE'))->toEqual("bootstrap/cache/routes-v7-tenant-{$tenant->id}.php");

    event(new TickTerminated(app(), app()));

    expect(Tenant::checkCurrent())->toBeFalse()
        ->and(env('APP_ROUTES_CACHE'))->toBeNull();
});
