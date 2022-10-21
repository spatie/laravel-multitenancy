<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\DummyTask;
use Spatie\Multitenancy\Tests\TestCase;

test('it will instantiate all class names', function () {
    $tasksCollection = new TasksCollection([DummyTask::class]);

    $this->assertInstanceOf(DummyTask::class, $tasksCollection->first());
});

test('it can pass parameters to the tasks', function () {
    $tasksCollection = new TasksCollection([
        DummyTask::class => ['a' => 1, 'b' => 2],
    ]);

    $task = $tasksCollection->first();

    $this->assertEquals(1, $task->a);
    $this->assertEquals(2, $task->b);
});

test('it  can handle duplicate tasks with other parameters', function () {
    $tasksCollection = new TasksCollection([
        [DummyTask::class => ['a' => 1, 'b' => 2]],
        [DummyTask::class => ['a' => 3, 'b' => 4]],
    ]);

    $this->assertEquals(1, $tasksCollection[0]->a);
    $this->assertEquals(2, $tasksCollection[0]->b);
    $this->assertEquals(3, $tasksCollection[1]->a);
    $this->assertEquals(4, $tasksCollection[1]->b);
});
