<?php

namespace Spatie\Multitenancy\Events;

use Illuminate\Http\Request;

class TenantNotFoundForRequestEvent
{
    public function __construct(
        public Request $request
    ) {
    }
}
