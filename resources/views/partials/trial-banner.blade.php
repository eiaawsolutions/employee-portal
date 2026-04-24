{{--
    Trial-ends banner.
    Shows when the current tenant's trial_ends_at is within 7 days AND has not expired.
    Dismissible per-session via sessionStorage (not localStorage — user should see it again
    next login until trial is resolved).
--}}
@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
    $trialEnds = $tenant?->trial_ends_at;
    $daysLeft = $trialEnds ? (int) now()->diffInDays($trialEnds, false) : null;
    $show = $trialEnds && $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7;
@endphp

@if($show)
<div id="trial-banner" class="trial-banner" role="status" aria-live="polite">
    <div class="trial-banner-content">
        <span class="trial-banner-dot"></span>
        <span class="trial-banner-message">
            @if($daysLeft === 0)
                <strong>Your trial ends today.</strong>
            @elseif($daysLeft === 1)
                <strong>Your trial ends tomorrow.</strong>
            @else
                <strong>{{ $daysLeft }} days left in your trial.</strong>
            @endif
            Add a payment method to keep all Growth-tier features — or we'll auto-downgrade to Starter on {{ $trialEnds->format('M j') }}.
        </span>
        @php
            $billingUrl = \Illuminate\Support\Facades\Route::has('superadmin.billing')
                ? route('superadmin.billing')
                : 'mailto:' . config('eiaaw.sales_email');
        @endphp
        <a href="{{ $billingUrl }}" class="trial-banner-cta">Add payment method</a>
    </div>
    <button type="button" class="trial-banner-dismiss" id="trial-banner-dismiss" aria-label="Dismiss">×</button>
</div>

<style>
    .trial-banner {
        display: flex; align-items: center; justify-content: space-between; gap: 14px;
        padding: 12px 16px;
        background: linear-gradient(90deg, #E5F4F1, #F3EDE0);
        border: 1px solid var(--line, #D9CFBC);
        border-radius: 12px;
        margin-bottom: 16px;
        font-family: var(--sans, 'Inter', sans-serif);
        font-size: 13.5px;
        color: var(--ink, #0F1A1D);
    }
    .trial-banner-content { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; flex: 1; }
    .trial-banner-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: var(--primary, #1FA896);
        box-shadow: 0 0 8px rgba(31,168,150,0.4);
        flex-shrink: 0;
    }
    .trial-banner-message strong { color: var(--ink, #0F1A1D); font-weight: 600; }
    .trial-banner-cta {
        padding: 6px 14px;
        background: var(--ink, #0F1A1D); color: var(--bg, #FAF7F2);
        border-radius: 999px;
        font-weight: 500; font-size: 12.5px;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.25s var(--ease, cubic-bezier(.2,.7,.2,1));
    }
    .trial-banner-cta:hover { background: var(--primary-dark, #11766A); color: var(--bg, #FAF7F2); }
    .trial-banner-dismiss {
        background: transparent; border: 0; cursor: pointer;
        font-size: 22px; line-height: 1; color: var(--mute, #6B7A7F);
        padding: 0 6px; flex-shrink: 0;
    }
    .trial-banner-dismiss:hover { color: var(--ink, #0F1A1D); }
    @media (max-width: 640px) {
        .trial-banner { flex-direction: column; align-items: flex-start; }
        .trial-banner-dismiss { align-self: flex-end; margin-top: -28px; }
    }
</style>

<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    try {
        if (sessionStorage.getItem('eiaaw_trial_banner_dismissed') === '1') {
            var banner = document.getElementById('trial-banner');
            if (banner) banner.style.display = 'none';
        }
    } catch (e) {}

    var dismiss = document.getElementById('trial-banner-dismiss');
    if (dismiss) {
        dismiss.addEventListener('click', function () {
            var banner = document.getElementById('trial-banner');
            if (banner) banner.style.display = 'none';
            try { sessionStorage.setItem('eiaaw_trial_banner_dismissed', '1'); } catch (e) {}
        });
    }
})();
</script>
@endif
