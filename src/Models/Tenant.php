<?php

namespace Spatie\Multitenancy\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $connection = 'landlord';

    public function configure(): self
    {
        config([
            'database.connections.tenant.database' => $this->getDatabaseName()
        ]);

        DB::purge('tenant');

        DB::reconnect('tenant');

        Schema::connection('tenant')->getConnection()->reconnect();

        return $this;
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    public function use(): self
    {
        app()->forgetInstance('tenant');

        app()->instance('tenant', $this);

        return $this;
    }
}
