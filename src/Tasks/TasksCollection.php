<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Collection;

class TasksCollection extends Collection
{
    public function __construct($taskClassNames)
    {
        $tasks = collect($taskClassNames)
            ->map(function ($task) {
                $taskClass = $task;
                $taskParameters = [];

                if (is_array($task)) {
                    $taskClass = array_key_first($task);
                    $taskParameters = $task[$taskClass];
                }

                return app()->makeWith($taskClass, $taskParameters);
            })
            ->toArray();

        parent::__construct($tasks);
    }
}
