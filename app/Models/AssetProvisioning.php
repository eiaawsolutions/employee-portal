<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AssetProvisioning extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'onboarding_id', 'laptop_provision', 'monitor_set', 'converter',
        'company_phone', 'sim_card', 'access_card_request', 'office_keys', 'others'
    ];

    protected $casts = [
        'laptop_provision' => 'boolean',
        'monitor_set' => 'boolean',
        'converter' => 'boolean',
        'company_phone' => 'boolean',
        'sim_card' => 'boolean',
        'access_card_request' => 'boolean',
    ];

    public function onboarding() { return $this->belongsTo(Onboarding::class); }
}
