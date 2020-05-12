<?php

namespace Spatie\Multitenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;

class Domain extends Model
{
    use UsesLandlordConnection;

    protected $fillable = ['domain'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(config('multitenancy.tenant_model'));
    }
}
