<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks;

use Spatie\Multitenancy\Tasks\TasksCollection;
use Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses\DummyTask;
use Spatie\Multitenancy\Tests\TestCase;

class TasksCollectionTest extends TestCase
{
    /** @test */
    public function it_will_instantiate_all_class_names()
    {
        $tasksCollection = new TasksCollection([DummyTask::class]);

        $this->assertInstanceOf(DummyTask::class, $tasksCollection->first());
    }

    /** @test */
    public function it_can_will_pass_parameters_to_the_tasks()
    {
        $tasksCollection = new TasksCollection([
            DummyTask::class => ['a' => 1, 'b' => 2],
        ]);

        $task = $tasksCollection->first();

        $this->assertEquals(1, $task->a);
        $this->assertEquals(2, $task->b);
    }

    /** @test */
    public function it_can_handle_duplicate_tasks_with_other_parameters()
    {
        $tasksCollection = new TasksCollection([
            [DummyTask::class => ['a' => 1, 'b' => 2]],
            [DummyTask::class => ['a' => 3, 'b' => 4]],
        ]);

        $this->assertEquals(1, $tasksCollection[0]->a);
        $this->assertEquals(2, $tasksCollection[0]->b);
        $this->assertEquals(3, $tasksCollection[1]->a);
        $this->assertEquals(4, $tasksCollection[1]->b);
    }
}
