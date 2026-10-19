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
                $tasksMadeCurrent[] = $task;

                $task->makeCurrent($tenant);
            }
        } catch (Throwable $exception) {
            rescue(fn () => $this->forgetCurrentUsing(array_reverse($tasksMadeCurrent)));

            throw $exception;
        }
    }

    public function forgetCurrent(): void
    {
        $this->forgetCurrentUsing($this->all());
    }

    /** @param array<int, SwitchTenantTask> $tasks */
    protected function forgetCurrentUsing(array $tasks): void
    {
        $exception = null;

        foreach ($tasks as $task) {
            try {
                $task->forgetCurrent();
            } catch (Throwable $throwable) {
                $exception ??= $throwable;
            }
        }

        if ($exception) {
            throw $exception;
        }
    }
}
