<?php

namespace Spatie\Multitenancy\Tests\Feature\TenantAwareJobs\TestClasses;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Spatie\Multitenancy\Jobs\TenantAware;

class MailableTenantAware extends Mailable implements ShouldQueue, TenantAware
{
    public function build()
    {
        return $this->view('mailable');
    }
}
