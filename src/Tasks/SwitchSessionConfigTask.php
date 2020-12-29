<?php

namespace Spatie\Multitenancy\Tasks;

class SwitchSessionConfigTask implements SwitchTenantTask
{
    private $originalConnection;

    private $originalDriver;

    public function __construct()
    {
        $this->originalDriver = config('session.driver');
        $this->originalConnection = config('session.connection');
    }

    public function makeCurrent($tenant): void
    {
        $this->setSessionDriver(data_get(
            $tenant,
            'session_driver',
            config(
                'session.tenant_driver',
                $this->originalDriver
            )
        ));

        $this->setSessionDatabaseConnection('tenant');
    }

    public function forgetCurrent(): void
    {
        $this->setSessionDriver($this->originalDriver);
        $this->setSessionDatabaseConnection($this->originalConnection);
    }

    private function setSessionDriver(string $sessionDriver): void
    {
        config()->set('session.driver', $sessionDriver);
    }

    private function setSessionDatabaseConnection(string $sessionDatabaseConnection): void
    {
        config()->set('session.connection', $sessionDatabaseConnection);
    }
}
