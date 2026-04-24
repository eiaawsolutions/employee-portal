<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Aarf extends Model
{
    use BelongsToTenant;

    protected $table = 'aarfs';

    protected $fillable = [
        'tenant_id', 'onboarding_id', 'employee_id', 'aarf_reference', 'acknowledged', 'acknowledged_at',
        'acknowledgement_token', 'it_manager_acknowledged', 'it_manager_acknowledged_at',
        'it_manager_user_id', 'it_manager_remarks',
        'it_notes', 'asset_changes', 'pending_asset_ids',
    ];

    protected $casts = [
        'acknowledged'               => 'boolean',
        'acknowledged_at'            => 'datetime',
        'it_manager_acknowledged'    => 'boolean',
        'it_manager_acknowledged_at' => 'datetime',
        'pending_asset_ids'          => 'array',
    ];

    public function isFullyAcknowledged(): bool
    {
        return $this->acknowledged;
    }

    public function isLocked(): bool
    {
        return $this->acknowledged;
    }

    public function onboarding() { return $this->belongsTo(Onboarding::class); }
    public function itManager()  { return $this->belongsTo(User::class, 'it_manager_user_id'); }
    public function employee()   { return $this->belongsTo(Employee::class); }

    /**
     * Add an asset ID to the pending acknowledgement list.
     */
    public function addPendingAsset(int $assetId): void
    {
        $ids = $this->pending_asset_ids ?? [];
        if (!in_array($assetId, $ids)) {
            $ids[] = $assetId;
        }
        $this->pending_asset_ids = $ids;
        $this->saveQuietly();
    }

    /**
     * Remove an asset ID from the pending acknowledgement list.
     */
    public function removePendingAsset(int $assetId): void
    {
        $ids = array_values(array_filter($this->pending_asset_ids ?? [], fn($id) => $id !== $assetId));
        $this->pending_asset_ids = $ids;
        $this->saveQuietly();
    }

    /**
     * Clear the pending acknowledgement list after acknowledgement.
     */
    public function clearPendingAssets(): void
    {
        $this->pending_asset_ids = [];
        $this->saveQuietly();
    }

    /**
     * Append a timestamped asset event to the AARF asset_changes log.
     */
    public function appendAssetChange(string $entry): void
    {
        $timestamp  = Carbon::now()->format('d/m/Y, h:i A');
        $newLine    = "[{$timestamp}] {$entry}";
        $existing   = trim($this->asset_changes ?? '');
        $this->asset_changes = $existing ? $existing . "\n" . $newLine : $newLine;
        $this->saveQuietly();
    }
}