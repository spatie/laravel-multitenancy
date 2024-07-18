<?php

namespace Spatie\Multitenancy\Contracts;

use Spatie\Multitenancy\TenantCollection;

interface IsTenant
{
    public static function current(): ?static;
    public static function checkCurrent(): bool;
    public static function forgetCurrent(): ?static;
    public function makeCurrent(): static;
    public function forget(): static;
    public function isCurrent(): bool;
    public function getDatabaseName(): string;
    public function newCollection(array $models = []): TenantCollection;
    public function execute(callable $callable): mixed;
    public function callback(callable $callable): \Closure;
}
