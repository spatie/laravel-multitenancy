<?php

use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\DummyTask;

test('it will instantiate all class names', function () {
    $tasksCollection = new TasksCollection([DummyTask::class]);

    expect($tasksCollection->first())->toBeInstanceOf(DummyTask::class);
});

test('it can pass parameters to the tasks', function () {
    $tasksCollection = new TasksCollection([
        DummyTask::class => ['a' => 1, 'b' => 2],
    ]);

    $task = $tasksCollection->first();

    expect($task->a)->toEqual(1)
        ->and($task->b)->toEqual(2);
});

test('it can handle duplicate tasks with other parameters', function () {
    $tasksCollection = new TasksCollection([
        [DummyTask::class => ['a' => 1, 'b' => 2]],
        [DummyTask::class => ['a' => 3, 'b' => 4]],
    ]);

    expect($tasksCollection[0]->a)->toEqual(1)
        ->and($tasksCollection[0]->b)->toEqual(2)
        ->and($tasksCollection[1]->a)->toEqual(3)
        ->and($tasksCollection[1]->b)->toEqual(4);
});
