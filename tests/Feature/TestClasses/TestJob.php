<?php

namespace Spatie\Multitenancy\Tests\Feature\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;

class TestJob implements ShouldQueue
{
    public static bool $jobHandled = false;

    public $tenantId;

    public function handle()
    {
        self::$jobHandled = true;
    }
}
