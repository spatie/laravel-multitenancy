<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Collection;

class TasksCollection extends Collection
{
    public function __construct($taskClassNames)
    {
        $tasks = collect($taskClassNames)
            ->map(function ($taskParameters, $taskClass) {
                if (is_array($taskParameters) && is_numeric($taskClass)) {
                    $taskClass = array_key_first($taskParameters);
                    $taskParameters = $taskParameters[$taskClass];
                }

                if (is_numeric($taskClass)) {
                    $taskClass = $taskParameters;
                    $taskParameters = [];
                }

                return app()->makeWith($taskClass, $taskParameters);
            })
            ->toArray();

        parent::__construct($tasks);
    }
}
