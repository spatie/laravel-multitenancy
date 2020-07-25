<?php

namespace Spatie\Multitenancy\Tests\Feature\Tasks\TestClasses;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class TestFailedJob implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle()
    {
        throw new Exception('Test failed job are recorded in current tenant database');
    }
}
