<?php

namespace Spatie\Multitenancy\Contracts;

use Illuminate\Http\Request;

interface InvalidatesTenantCache
{
    public function forget(Request $request): bool;

    public function forgetByKey(string $key): bool;
}
