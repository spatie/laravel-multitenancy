<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;

class WrappedTestJob implements ShouldQueue
{
    public function __construct(
        public TestJob $wrapped,
        public ?string $name = null,
    ) {
    }

    public function getJob(): TestJob
    {
        return $this->wrapped;
    }

    public function displayName(): string
    {
        return $this->name ?? static::class;
    }

    public function handle(): void
    {
        $this->wrapped->handle();
    }
}
