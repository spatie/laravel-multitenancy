<?php

namespace Spatie\Multitenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Tenant extends Model
{
    protected $connection = 'landlord';

    public function makeCurrent(): self
    {
        $this->configure();

        $this->bindAsCurrentTenant();

        return $this;
    }

    protected function configure(): self
    {
        config([
            'database.connections.tenant.database' => $this->getDatabaseName(),
        ]);

        DB::purge('tenant');

        DB::reconnect('tenant');

        Schema::connection('tenant')->getConnection()->reconnect();

        return $this;
    }

    public static function current(): ?self
    {
        if (! app()->has('current_tenant')) {
            return null;
        }

        return app('current_tenant');
    }

    public function getDatabaseName(): string
    {
        return $this->database;
    }

    protected function bindAsCurrentTenant(): void
    {
        app()->forgetInstance('current_tenant');

        app()->instance('current_tenant', $this);
    }
}
