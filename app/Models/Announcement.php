<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title',
        'body',
        'companies',
        'attachment_paths',
        'created_by',
    ];

    protected $casts = [
        'companies'        => 'array',
        'attachment_paths' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: announcements visible to a given company.
     * null companies column = all companies; otherwise check if company is in the array.
     */
    public function scopeVisibleTo($query, ?string $company): void
    {
        $query->where(function ($q) use ($company) {
            $q->whereNull('companies');
            if ($company) {
                $q->orWhereJsonContains('companies', $company);
            }
        });
    }
}
