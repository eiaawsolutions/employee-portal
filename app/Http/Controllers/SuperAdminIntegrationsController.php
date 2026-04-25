<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Models\SecurityAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Platform-level API key & integration settings.
 *
 * Edits are restricted to `superadmin` role (EIAAW staff). Values are
 * AES-256-CBC encrypted at rest via the PlatformSetting model.
 *
 * The form never echoes raw secret values back. Existing secrets show as
 * masked; submitting blank leaves the stored value untouched.
 */
class SuperAdminIntegrationsController extends Controller
{
    public function show(Request $request)
    {
        $catalog = config('platform_integrations');
        $rows = PlatformSetting::all()->keyBy('key');

        return view('superadmin.integrations', compact('catalog', 'rows'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $catalog = config('platform_integrations');

        $allFields = collect($catalog)->flatMap(fn ($s) => $s['fields'])->keyBy('key');

        $rules = [];
        foreach ($allFields as $key => $field) {
            $rules[$key] = ['nullable', 'string', 'max:1000'];
        }

        $data = Validator::make($request->all(), $rules)->validate();

        $changed = [];
        foreach ($allFields as $key => $field) {
            $submitted = $data[$key] ?? null;
            if ($submitted === null || $submitted === '') {
                continue;
            }
            PlatformSetting::put($key, $submitted, (bool) ($field['is_secret'] ?? true), $user->id);
            $changed[] = $key;
        }

        SecurityAuditLog::record('platform_settings_updated', [
            'user_id' => $user->id,
            'work_email' => $user->work_email ?? null,
            'role' => $user->role ?? null,
            'url' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'details' => json_encode(['keys' => $changed]),
        ]);

        return back()->with('status',
            empty($changed)
                ? 'No changes — submit a value to update.'
                : 'Updated: ' . implode(', ', $changed));
    }

    public function delete(Request $request, string $key)
    {
        $allowed = collect(config('platform_integrations'))
            ->flatMap(fn ($s) => $s['fields'])
            ->pluck('key')
            ->all();

        if (! in_array($key, $allowed, true)) {
            abort(404);
        }

        PlatformSetting::forget($key);

        $user = $request->user();
        SecurityAuditLog::record('platform_setting_cleared', [
            'user_id' => $user->id,
            'work_email' => $user->work_email ?? null,
            'role' => $user->role ?? null,
            'url' => $request->path(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'details' => json_encode(['key' => $key]),
        ]);

        return back()->with('status', "Cleared: {$key}");
    }
}
