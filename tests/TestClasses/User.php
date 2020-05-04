<?php

namespace Spatie\Multitenancy\Tests\TestClasses;

use Illuminate\Foundation\Auth\User as BaseUser;

class User extends BaseUser
{
    public $connection = 'tenant';
}
