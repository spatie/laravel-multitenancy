<?php declare(strict_types=1);

namespace Spatie\Multitenancy\TenantFinder;

use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\Models\Concerns\UsesTenantModel;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubdomainTenantFinder extends TenantFinder
{
    use UsesTenantModel;

    protected array $hostChunks;

    protected string $subdomain;

    protected array $landlordUrlChunks;

    public function findForRequest(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $this->hostChunks = explode('.', $host);

        return $this->insureSubdomainAvailable()
            ->findSubdomain()
            ->insureLandlordRequest()
            ->insureSubdomainNotExcluded()
            ->getTenantModel()::whereSubdomain($this->subdomain)->first();
    }

    private function insureSubdomainAvailable(): self
    {
        if (count($this->hostChunks) === 2) {
            throw new NotFoundHttpException('Not Prepared to handle it right now');
        }

        return $this;
    }

    private function findSubdomain(): self
    {
        $this->subdomain = array_shift($this->hostChunks);

        return $this;
    }

    private function insureLandlordRequest(): self
    {
        $this->landlordUrlChunks = explode('.', config('multitenancy.landlord_url'));

        if ($this->hostChunks !== $this->landlordUrlChunks) {
            throw new NotFoundHttpException('Not Prepared to handle it right now');
        }

        return $this;
    }

    private function insureSubdomainNotExcluded(): self
    {
        if (in_array($this->subdomain, config('multitenancy.excluded_subdomains'))) {
            // it should redirect to the request url because
            // ex. www.google.com/register should be redirected to google.com/register
            throw new NotFoundHttpException('Not Prepared to handle it right now');
        }

        return $this;
    }
}
