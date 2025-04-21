<?php

namespace Spatie\Multitenancy\Models\Concerns;

trait UsesBothConnection
{
    public function getConnectionName(): ?string
    {
        return $this->getDedectedDatabaseConnectionName();
    }
}