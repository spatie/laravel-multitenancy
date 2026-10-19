<?php

namespace Spatie\Multitenancy\Tests\Octane\TestClasses;

use Laravel\Octane\OctaneResponse;
use Laravel\Octane\RequestContext;
use Laravel\Octane\Testing\Fakes\FakeClient;
use RuntimeException;

class DisconnectingFakeClient extends FakeClient
{
    protected bool $disconnectOnNextResponse = false;

    public function disconnectOnNextResponse(): void
    {
        $this->disconnectOnNextResponse = true;
    }

    public function respond(RequestContext $context, OctaneResponse $octaneResponse): void
    {
        if ($this->disconnectOnNextResponse) {
            $this->disconnectOnNextResponse = false;

            throw new RuntimeException('The client went away');
        }

        parent::respond($context, $octaneResponse);
    }
}
