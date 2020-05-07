<?php

namespace Spatie\Multitenancy\Exceptions;

use Exception;
use Spatie\Multitenancy\Tasks\SwitchTenantTask;

class TaskCannotBeExecuted extends Exception
{
    public static function make(SwitchTenantTask $task, string $reason): self
    {
        $taskClass = get_class($task);

        return new static("Task `{$taskClass}` could not be executed. Reason: {$reason}");
    }
}
