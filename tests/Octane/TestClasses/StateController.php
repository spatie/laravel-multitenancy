<?php

namespace Spatie\Multitenancy\Tests\Octane\TestClasses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Spatie\Multitenancy\Models\Tenant;

class StateController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'tenant' => Tenant::current()?->domain,
            'database' => DB::connection('tenant')->selectOne('select database() as name')->name,
            'routes_cache' => Env::get('APP_ROUTES_CACHE'),
        ]);
    }
}
