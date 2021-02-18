@if (\Spatie\Multitenancy\Models\Tenant::checkCurrent())
    Current tenant ID: {{ \Spatie\Multitenancy\Models\Tenant::current()->id }}
@else
    This is the Landlord
@endif
