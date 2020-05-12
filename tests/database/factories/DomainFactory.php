<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator;
use Spatie\Multitenancy\Models\Domain;

$factory->define(Domain::class, fn (Generator $faker) => [
    'domain' => $faker->unique()->domainName,
]);
