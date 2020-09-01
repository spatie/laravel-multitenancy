<?php declare(strict_types=1);

namespace Spatie\Multitenancy\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubdomainTenantFinder extends TenantFinder
{
    use UsesTenantModel;

    public function findForRequest(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $hostChunk = explode('.', $host);

        if (count($hostChunk) === 2) {
            throw new NotFoundHttpException('Not Prepared to handle it right now');
        }

        $subdomain = $hostChunk[0];

        if (in_array($subdomain, config('multitenancy.excluded_subdomains'))) {
            throw new NotFoundHttpException('Not Prepared to handle it right now');
        }

        return $this->getTenantModel()::whereSubdomain($subdomain)->first();
    }
}
