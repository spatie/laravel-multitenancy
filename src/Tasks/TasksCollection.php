<?php

namespace Spatie\Multitenancy\Tasks;

use Illuminate\Support\Collection;
use Spatie\Multitenancy\Contracts\IsTenant;
use Throwable;

class TasksCollection extends Collection
{
    public function __construct($taskClassNames = [])
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

    public function makeCurrent(IsTenant $tenant): void
    {
        $tasksMadeCurrent = [];

        try {
            foreach ($this as $task) {
                $task->makeCurrent($tenant);

                $tasksMadeCurrent[] = $task;
            }
        } catch (Throwable $exception) {
            foreach (array_reverse($tasksMadeCurrent) as $task) {
                $task->forgetCurrent();
            }

            throw $exception;
        }
    }

    public function forgetCurrent(): void
    {
        $this->each(fn (SwitchTenantTask $task) => $task->forgetCurrent());
    }
}
