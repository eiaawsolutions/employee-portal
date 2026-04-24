<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Dedicated DB request</title></head>
<body style="font-family: Inter, system-ui, sans-serif; color: #0F1A1D; max-width: 640px; margin: 32px auto; padding: 0 16px;">

<h2 style="font-weight: 500; letter-spacing: -0.02em;">Dedicated database request</h2>

<p>A workspace has requested migration off the shared Postgres pool.</p>

<table cellpadding="6" style="border-collapse: collapse; width: 100%; font-size: 14px; margin: 20px 0;">
    <tr><td style="color: #6B7A7F; width: 160px;">Tenant</td><td><strong>{{ $tenant->name }}</strong> (id={{ $tenant->id }}, slug={{ $tenant->slug }})</td></tr>
    <tr><td style="color: #6B7A7F;">Current plan</td><td>{{ ucfirst($tenant->plan) }}</td></tr>
    <tr><td style="color: #6B7A7F;">Requester</td><td>{{ $requester->name }} &lt;{{ $requester->work_email }}&gt;</td></tr>
    <tr><td style="color: #6B7A7F;">Region preference</td><td><code>{{ $data['region_preference'] }}</code></td></tr>
    <tr><td style="color: #6B7A7F;">Target go-live</td><td>{{ $data['target_go_live'] ?? '—' }}</td></tr>
</table>

@if(!empty($data['compliance_note']))
<p style="color: #6B7A7F; font-size: 13px;">Compliance / context note from requester:</p>
<blockquote style="border-left: 3px solid #D9CFBC; padding: 4px 14px; color: #2A3438; background: #FAF7F2;">
    {{ $data['compliance_note'] }}
</blockquote>
@endif

<hr style="border: 0; border-top: 1px solid #E8DFCC; margin: 24px 0;">

<p style="font-size: 13px; color: #6B7A7F;">
    <strong>Next steps for ops:</strong><br>
    1. Acknowledge to requester within 2 business days<br>
    2. Scope Postgres instance (size, region, HA)<br>
    3. Schedule migration window; copy tenant data<br>
    4. Flip DSN: <code>php artisan tenant:set-dedicated-dsn {{ $tenant->slug }} "&lt;dsn&gt;"</code><br>
    5. Verify leakage tests and RLS on the new instance
</p>

</body>
</html>
