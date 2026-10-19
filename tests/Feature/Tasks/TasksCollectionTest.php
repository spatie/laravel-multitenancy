<?php

use Illuminate\Support\Facades\Exceptions;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\DummyTask;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\FailingTask;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\FailingToForgetTask;

it('will instantiate all class names', function () {
    $tasksCollection = new TasksCollection([DummyTask::class]);

    expect($tasksCollection->first())->toBeInstanceOf(DummyTask::class);
});

it('can pass parameters to the tasks', function () {
    $tasksCollection = new TasksCollection([
        DummyTask::class => ['a' => 1, 'b' => 2],
    ]);

    $task = $tasksCollection->first();

    expect($task->a)->toEqual(1)
        ->and($task->b)->toEqual(2);
});

it('can handle duplicate tasks with other parameters', function () {
    $tasksCollection = new TasksCollection([
        [DummyTask::class => ['a' => 1, 'b' => 2]],
        [DummyTask::class => ['a' => 3, 'b' => 4]],
    ]);

    expect($tasksCollection[0]->a)->toEqual(1)
        ->and($tasksCollection[0]->b)->toEqual(2)
        ->and($tasksCollection[1]->a)->toEqual(3)
        ->and($tasksCollection[1]->b)->toEqual(4);
});

it('will forget the tasks it ran, including the one that failed, when a task fails to make a tenant current', function () {
    $tasksCollection = new TasksCollection([DummyTask::class, FailingTask::class, DummyTask::class]);

    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();

    expect(fn () => $tasksCollection->makeCurrent($tenant))->toThrow(RuntimeException::class);

    expect($tasksCollection[0]->madeCurrentCalled)->toBeTrue()
        ->and($tasksCollection[0]->forgotCurrentCalled)->toBeTrue()
        ->and($tasksCollection[1]->forgotCurrentCalled)->toBeTrue()
        ->and($tasksCollection[2]->madeCurrentCalled)->toBeFalse()
        ->and($tasksCollection[2]->forgotCurrentCalled)->toBeFalse();
});

it('will keep forgetting the remaining tasks when a task fails to forget the current tenant', function () {
    $tasksCollection = new TasksCollection([DummyTask::class, FailingToForgetTask::class, DummyTask::class]);

    expect(fn () => $tasksCollection->forgetCurrent())->toThrow(RuntimeException::class, 'This task cannot forget the current tenant');

    expect($tasksCollection[0]->forgotCurrentCalled)->toBeTrue()
        ->and($tasksCollection[2]->forgotCurrentCalled)->toBeTrue();
});

it('will rethrow the exception of the task that failed to make a tenant current when a task fails to forget it too', function () {
    Exceptions::fake();

    $tasksCollection = new TasksCollection([DummyTask::class, FailingToForgetTask::class, FailingTask::class]);

    /** @var Tenant $tenant */
    $tenant = Tenant::factory()->create();

    expect(fn () => $tasksCollection->makeCurrent($tenant))->toThrow(RuntimeException::class, 'This task cannot make a tenant current');

    expect($tasksCollection[0]->forgotCurrentCalled)->toBeTrue();

    Exceptions::assertReported(fn (RuntimeException $exception) => $exception->getMessage() === 'This task cannot forget the current tenant');
});
