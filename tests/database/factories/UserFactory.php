<?php declare(strict_types=1);

use Faker\Generator;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factory;
use Spatie\Multitenancy\Tests\TestClasses\User;

/** @var Factory $factory */
$factory->define(User::class, fn (Generator $faker) => [
    'name' => $faker->name,
    'email' => $faker->unique()->safeEmail,
    'email_verified_at' => now(),
    'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
    'remember_token' => Str::random(10),
]);
