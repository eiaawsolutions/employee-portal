<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes every uploaded file that belongs to one tenant, on both the private
 * ('local') and 'public' disks — whether those are the container disk or R2.
 *
 * Stored paths are not prefixed by tenant, so the files are found through the
 * columns that reference them. Every query filters on tenant_id explicitly
 * (on top of RLS), so another tenant's files can never be selected.
 *
 * When a new upload column is added anywhere, add it to COLUMNS — the privacy
 * notice, terms and DPA promise that a cancelled workspace's files are deleted.
 */
final class TenantFilePurger
{
    /** table => [column => 'string'|'json'] */
    public const COLUMNS = [
        'acc_ai_invoice_scans' => ['file_path' => 'string'],
        'announcements' => ['attachment_paths' => 'json'],
        'asset_inventories' => [
            'asset_photos' => 'json',
            'invoice_document' => 'string',
            'invoice_documents' => 'json',
            'rental_contract_documents' => 'json',
        ],
        'companies' => ['logo_path' => 'string'],
        'employee_contracts' => ['file_path' => 'string'],
        'employee_education_histories' => ['certificate_path' => 'string', 'certificate_paths' => 'json'],
        'employees' => [
            'aarf_file_path' => 'string',
            'handbook_path' => 'string',
            'nric_file_path' => 'string',
            'nric_file_paths' => 'json',
            'orientation_path' => 'string',
        ],
        'expense_claim_items' => ['receipt_path' => 'string'],
        'leave_applications' => ['attachment_path' => 'string'],
        'personal_details' => ['nric_file_path' => 'string', 'nric_file_paths' => 'json'],
        'ticket_attachments' => ['file_path' => 'string'],
        'ticket_message_attachments' => ['file_path' => 'string'],
        'ticket_messages' => ['attachment_path' => 'string'],
        'users' => ['profile_picture' => 'string'],
    ];

    /** @return list<string> the tenant's stored file paths */
    public function pathsFor(int $tenantId): array
    {
        $paths = [];
        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }
            foreach ($columns as $column => $kind) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }
                $values = DB::table($table)->where('tenant_id', $tenantId)->whereNotNull($column)->pluck($column);
                foreach ($values as $value) {
                    $items = $kind === 'json' ? (json_decode((string) $value, true) ?: []) : [$value];
                    foreach ((array) $items as $item) {
                        if (is_string($item) && $item !== '' && ! str_contains($item, '..')) {
                            $paths[] = ltrim($item, '/');
                        }
                    }
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /** Deletes the tenant's files from both disks; returns how many paths were referenced. */
    public function purge(int $tenantId): int
    {
        $paths = $this->pathsFor($tenantId);
        foreach (array_chunk($paths, 500) as $chunk) {
            Storage::disk('local')->delete($chunk);
            Storage::disk('public')->delete($chunk);
        }

        return count($paths);
    }
}
