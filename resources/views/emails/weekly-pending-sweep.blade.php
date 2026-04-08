<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
<div style="max-width:620px;margin:30px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.08);">

    @php
        $headers = [
            'consent'        => ['icon' => "\u{1F4CB}", 'title' => 'Pending Profile Acknowledgement',           'color' => '#7c3aed,#6d28d9'],
            'aarf_employee'  => ['icon' => "\u{1F4E6}", 'title' => 'Pending Asset Form Acknowledgement',        'color' => '#0891b2,#0e7490'],
            'aarf_it'        => ['icon' => "\u{1F5A5}\u{FE0F}", 'title' => 'AARF Forms Awaiting IT Acknowledgement',   'color' => '#0284c7,#0369a1'],
            'leave'          => ['icon' => "\u{1F4C5}", 'title' => 'Pending Leave Requests',                    'color' => '#f59e0b,#d97706'],
            'claims_manager' => ['icon' => "\u{1F4B0}", 'title' => 'Expense Claims Awaiting Your Approval',     'color' => '#059669,#047857'],
            'claims_hr'      => ['icon' => "\u{1F4B0}", 'title' => 'Expense Claims Awaiting HR Approval',       'color' => '#1e3a5f,#2563eb'],
        ];
        $h = $headers[$type] ?? ['icon' => "\u{1F514}", 'title' => 'Pending Action Required', 'color' => '#475569,#334155'];
    @endphp

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,{{ $h['color'] }});padding:28px 32px;text-align:center;">
        <h1 style="margin:0;color:#fff;font-size:22px;font-weight:700;">
            {{ $h['icon'] }} {{ $h['title'] }}
        </h1>
        <p style="margin:8px 0 0;color:rgba(255,255,255,0.9);font-size:14px;">
            Weekly Reminder &mdash; {{ now()->format('d M Y') }}
        </p>
    </div>

    {{-- Body --}}
    <div style="padding:28px 32px;">
        <p style="color:#334155;font-size:15px;line-height:1.6;margin:0 0 20px;">
            Hi <strong>{{ $recipientName }}</strong>,
        </p>

        {{-- ── CONSENT ──────────────────────────────────────────────── --}}
        @if($type === 'consent')
        <p style="color:#334155;font-size:15px;line-height:1.6;margin:0 0 20px;">
            Your employee profile was recently updated and requires your re-acknowledgement. Please review and acknowledge the changes at your earliest convenience.
        </p>
        <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Sections Changed</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Changed On</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Waiting</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $log)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 12px;color:#1e293b;">
                        @if(is_array($log->sections_changed))
                            {{ implode(', ', $log->sections_changed) }}
                        @else
                            Profile update
                        @endif
                    </td>
                    <td style="padding:10px 12px;color:#1e293b;">{{ $log->created_at->format('d M Y') }}</td>
                    <td style="padding:10px 12px;color:#dc2626;font-weight:600;">{{ (int) $log->created_at->diffInDays(now()) }} day(s)</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($items->first()?->consent_token)
        @php $tokenUrl = url("/employee/consent/{$items->first()->consent_token}"); @endphp
        <div style="text-align:center;margin:24px 0;">
            <a href="{{ $tokenUrl }}"
               style="display:inline-block;background:#7c3aed;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:600;font-size:14px;">
                Review & Acknowledge
            </a>
        </div>
        @endif

        {{-- ── AARF EMPLOYEE ────────────────────────────────────────── --}}
        @elseif($type === 'aarf_employee')
        <p style="color:#334155;font-size:15px;line-height:1.6;margin:0 0 20px;">
            You have <strong>{{ $items->count() }}</strong> asset record form(s) pending your acknowledgement. Please review and acknowledge to confirm your assigned assets.
        </p>
        <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">AARF Reference</th>
                    <th style="padding:10px 12px;text-align:center;border-bottom:2px solid #e2e8f0;color:#475569;">Pending Assets</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $aarf)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 12px;color:#1e293b;font-weight:600;">{{ $aarf->aarf_reference }}</td>
                    <td style="padding:10px 12px;text-align:center;color:#1e293b;">
                        {{ is_array($aarf->pending_asset_ids) ? count($aarf->pending_asset_ids) : 0 }} asset(s)
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($items->first()?->acknowledgement_token)
        @php $aarfUrl = url("/aarf/acknowledge/{$items->first()->acknowledgement_token}"); @endphp
        <div style="text-align:center;margin:24px 0;">
            <a href="{{ $aarfUrl }}"
               style="display:inline-block;background:#0891b2;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:600;font-size:14px;">
                Acknowledge Asset Form
            </a>
        </div>
        @endif

        {{-- ── AARF IT MANAGER ──────────────────────────────────────── --}}
        @elseif($type === 'aarf_it')
        <p style="color:#334155;font-size:15px;line-height:1.6;margin:0 0 20px;">
            There are <strong>{{ $items->count() }}</strong> AARF form(s) awaiting IT manager acknowledgement.
        </p>
        <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">AARF Reference</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Employee</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Employee Ack</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $aarf)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 12px;color:#1e293b;font-weight:600;">{{ $aarf->aarf_reference }}</td>
                    <td style="padding:10px 12px;color:#1e293b;">{{ $aarf->employee?->preferred_name ?? $aarf->employee?->full_name ?? '—' }}</td>
                    <td style="padding:10px 12px;">
                        @if($aarf->acknowledged)
                            <span style="color:#059669;font-weight:600;">Done</span>
                        @else
                            <span style="color:#d97706;">Pending</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="text-align:center;margin:24px 0;">
            <a href="{{ url('/assets') }}"
               style="display:inline-block;background:#0284c7;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:600;font-size:14px;">
                Review in Asset Listing
            </a>
        </div>

        {{-- ── LEAVE ────────────────────────────────────────────────── --}}
        @elseif($type === 'leave')
        <p style="color:#334155;font-size:15px;line-height:1.6;margin:0 0 20px;">
            You have <strong>{{ $items->count() }}</strong> pending leave request(s) from your team awaiting your approval.
        </p>
        <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Employee</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Leave Type</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Dates</th>
                    <th style="padding:10px 12px;text-align:center;border-bottom:2px solid #e2e8f0;color:#475569;">Days</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Waiting</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $app)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 12px;color:#1e293b;">
                        <strong>{{ $app->employee?->preferred_name ?? $app->employee?->full_name ?? '—' }}</strong>
                    </td>
                    <td style="padding:10px 12px;color:#1e293b;">{{ $app->leaveType?->name ?? 'Leave' }}</td>
                    <td style="padding:10px 12px;color:#1e293b;">{{ $app->start_date->format('d M') }}–{{ $app->end_date->format('d M Y') }}</td>
                    <td style="padding:10px 12px;text-align:center;color:#1e293b;">
                        {{ $app->total_days }}@if($app->is_half_day) <small>(1/2)</small>@endif
                    </td>
                    <td style="padding:10px 12px;">
                        @php $daysWaiting = (int) $app->created_at->diffInDays(now()); @endphp
                        @if($daysWaiting >= 3)
                            <span style="color:#dc2626;font-weight:600;">{{ $daysWaiting }} days</span>
                        @elseif($daysWaiting >= 1)
                            <span style="color:#d97706;">{{ $daysWaiting }} day(s)</span>
                        @else
                            <span style="color:#64748b;">Today</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="text-align:center;margin:24px 0;">
            <a href="{{ url('/my/team-leave?status=pending') }}"
               style="display:inline-block;background:#f59e0b;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:600;font-size:14px;">
                Review Leave Requests
            </a>
        </div>

        {{-- ── CLAIMS MANAGER ───────────────────────────────────────── --}}
        @elseif($type === 'claims_manager')
        <p style="color:#334155;font-size:15px;line-height:1.6;margin:0 0 20px;">
            You have <strong>{{ $items->count() }}</strong> expense claim(s) submitted by your team members awaiting your approval.
        </p>
        <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Claim #</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Employee</th>
                    <th style="padding:10px 12px;text-align:right;border-bottom:2px solid #e2e8f0;color:#475569;">Amount (RM)</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Submitted</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $claim)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 12px;color:#1e293b;font-weight:600;">{{ $claim->claim_number }}</td>
                    <td style="padding:10px 12px;color:#1e293b;">{{ $claim->employee?->preferred_name ?? $claim->employee?->full_name ?? '—' }}</td>
                    <td style="padding:10px 12px;text-align:right;color:#1e293b;">{{ number_format($claim->total_with_gst ?? $claim->total_amount, 2) }}</td>
                    <td style="padding:10px 12px;color:#64748b;">{{ $claim->submitted_at?->format('d M Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="text-align:center;margin:24px 0;">
            <a href="{{ url('/claims?status=submitted') }}"
               style="display:inline-block;background:#059669;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:600;font-size:14px;">
                Review Claims
            </a>
        </div>

        {{-- ── CLAIMS HR ────────────────────────────────────────────── --}}
        @elseif($type === 'claims_hr')
        <p style="color:#334155;font-size:15px;line-height:1.6;margin:0 0 20px;">
            There are <strong>{{ $items->count() }}</strong> expense claim(s) that have been approved by managers and are now awaiting HR final approval.
        </p>
        <table style="width:100%;border-collapse:collapse;margin:20px 0;font-size:13px;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Claim #</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Employee</th>
                    <th style="padding:10px 12px;text-align:right;border-bottom:2px solid #e2e8f0;color:#475569;">Amount (RM)</th>
                    <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e2e8f0;color:#475569;">Manager Approved</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $claim)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:10px 12px;color:#1e293b;font-weight:600;">{{ $claim->claim_number }}</td>
                    <td style="padding:10px 12px;color:#1e293b;">{{ $claim->employee?->preferred_name ?? $claim->employee?->full_name ?? '—' }}</td>
                    <td style="padding:10px 12px;text-align:right;color:#1e293b;">{{ number_format($claim->total_with_gst ?? $claim->total_amount, 2) }}</td>
                    <td style="padding:10px 12px;color:#64748b;">{{ $claim->manager_approved_at?->format('d M Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="text-align:center;margin:24px 0;">
            <a href="{{ url('/claims?status=manager_approved') }}"
               style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:600;font-size:14px;">
                Review Claims
            </a>
        </div>
        @endif

        {{-- Reminder notice --}}
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:14px 16px;margin-top:20px;">
            <p style="margin:0;font-size:12px;color:#0c4a6e;line-height:1.5;">
                <strong>This is an automated weekly reminder.</strong>
                If you have already taken action, please disregard this email. Pending items are checked every Wednesday at midnight.
            </p>
        </div>
    </div>

    {{-- Footer --}}
    <div style="background:#f8fafc;padding:20px 32px;text-align:center;border-top:1px solid #e2e8f0;">
        <p style="margin:0;color:#94a3b8;font-size:12px;">
            This is an automated weekly sweep from the HR system.<br>
            Please do not reply to this email.
        </p>
    </div>

</div>
</body>
</html>
