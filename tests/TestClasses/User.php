<?php

namespace Spatie\Multitenancy\Tests\TestClasses;

use Illuminate\Foundation\Auth\User as BaseUser;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class User extends BaseUser
{
    use UsesTenantConnection;
}
