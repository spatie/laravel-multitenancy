<?php

namespace Spatie\Multitenancy\Contracts;

use Illuminate\Http\Request;

interface CachesTenantFinderResults
{
    public function forget(Request $request): bool;

    public function forgetByKey(string $key): bool;
}
