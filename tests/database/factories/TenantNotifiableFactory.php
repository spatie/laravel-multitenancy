<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator;
use Spatie\Multitenancy\Tests\Feature\Models\TenantNotifiable;

$factory->define(TenantNotifiable::class, fn (Generator $faker) => [
    'name' => $faker->name,
    'domain' => $faker->unique()->domainName,
    'database' => $faker->userName,
]);
