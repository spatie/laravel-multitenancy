<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Spatie\Multitenancy\Models\Tenant;
use Faker\Generator;

$factory->define(Tenant::class, fn(Generator $faker) => [
    'name' => $faker->name,
    'domain' => $faker->unique()->domainName,
    'database' => $faker->userName,
]);
