<?php

namespace Spatie\Multitenancy\Actions;

use Illuminate\Support\Facades\Artisan;
use Spatie\Multitenancy\Models\Tenant;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateTenantAction
{
    protected bool $fresh = false;

    protected bool $seed = false;

    protected OutputInterface $output;

    public function fresh(bool $fresh = true): self
    {
        $this->fresh = $fresh;

        return $this;
    }

    public function seed(bool $seed = true): self
    {
        $this->seed = $seed;

        return $this;
    }

    public function output(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function execute(Tenant $tenant): self
    {
        $tenant->execute(function () {
            $migrationCommand = $this->fresh ? 'migrate:fresh' : 'migrate';

            Artisan::call($migrationCommand, $this->getOptions(), $this->output);
        });

        return $this;
    }

    protected function getOptions(): array
    {
        $options = ['--force' => true];

        if ($this->seed) {
            $options['--seed'] = true;
        }

        return $options;
    }
}
