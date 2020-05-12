<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator;
use Spatie\Multitenancy\Models\Tenant;

$factory->define(Tenant::class, fn (Generator $faker) => [
    'name' => $faker->name,
    'database' => $faker->userName,
]);
