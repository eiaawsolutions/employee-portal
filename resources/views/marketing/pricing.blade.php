@extends('layouts.marketing')

@section('title', 'Pricing — EIAAW Workforce')
@section('description', 'Four modules, three paid tiers. Starter $6, Growth $14, Scale $29 per active employee per month. Enterprise custom. 14-day trial, no credit card.')

@push('head')
<style>
    .pr-hero {
        padding: clamp(60px, 8vw, 100px) 0 clamp(36px, 5vw, 56px);
        text-align: center;
    }
    .pr-intent-nudge {
        display: inline-block;
        margin: 0 auto 24px;
        padding: 10px 18px;
        background: var(--primary-tint);
        color: var(--primary-dark);
        border: 1px solid rgba(31,168,150,0.3);
        border-radius: 999px;
        font-size: 13.5px;
        font-weight: 500;
        letter-spacing: -0.005em;
    }
    .pr-hero .eyebrow { justify-content: center; }
    .pr-hero h1 { margin: 18px auto 20px; max-width: 900px; text-align: center; }
    .pr-hero p { color: var(--ink-2); font-size: 17px; max-width: 620px; margin: 0 auto; }

    .pr-controls {
        display: flex; flex-wrap: wrap; align-items: center; justify-content: center;
        gap: 16px; margin-top: 40px;
    }
    .pr-toggle {
        display: inline-flex; align-items: center; gap: 0;
        border: 1px solid var(--line); border-radius: 999px;
        background: var(--surface); padding: 4px;
    }
    .pr-toggle button {
        padding: 8px 18px; border-radius: 999px;
        border: 0; background: transparent; cursor: pointer;
        font-family: var(--sans); font-size: 13.5px; font-weight: 500;
        color: var(--ink-2); letter-spacing: -0.005em;
        transition: all 0.25s var(--ease);
    }
    .pr-toggle button[aria-pressed="true"] { background: var(--ink); color: var(--bg); }
    .pr-toggle .pr-toggle-badge {
        display: inline-block; margin-left: 8px;
        padding: 2px 8px; border-radius: 999px;
        background: var(--primary-tint); color: var(--primary-dark);
        font-family: var(--mono); font-size: 10px;
        text-transform: uppercase; letter-spacing: 0.1em;
    }

    /* ── Tier grid ── */
    .pr-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-top: 56px;
    }
    @media (max-width: 1100px) { .pr-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px)  { .pr-grid { grid-template-columns: 1fr; } }

    .pr-tier {
        background: var(--surface);
        border: 1px solid var(--line-soft);
        border-radius: 20px;
        padding: 32px 28px;
        display: flex; flex-direction: column; gap: 18px;
        position: relative;
        transition: transform 0.3s var(--ease), box-shadow 0.3s var(--ease);
    }
    .pr-tier:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(15,26,29,0.06); }
    .pr-tier.featured {
        background: var(--ink); color: var(--bg); border-color: var(--ink);
    }
    .pr-tier.featured:hover { box-shadow: 0 16px 40px rgba(15,26,29,0.16); }
    .pr-tier.featured .pr-tier-name,
    .pr-tier.featured .pr-tier-headcount { color: rgba(255,255,255,0.7); }
    .pr-tier.featured .pr-tier-tagline { color: rgba(255,255,255,0.85); }
    .pr-tier.featured .pr-tier-features li,
    .pr-tier.featured .pr-tier-excluded li { color: rgba(255,255,255,0.8); }
    .pr-tier.featured .pr-tier-features li::before { background: var(--primary); }
    .pr-tier.featured .pr-cta-primary { background: var(--primary); color: var(--bg); border-color: var(--primary); }
    .pr-tier.featured .pr-cta-primary:hover { background: #25c4b0; border-color: #25c4b0; }

    .pr-tier-badge {
        position: absolute; top: -12px; right: 28px;
        padding: 5px 12px; border-radius: 999px;
        background: var(--primary); color: var(--bg);
        font-family: var(--mono); font-size: 10.5px; font-weight: 500;
        text-transform: uppercase; letter-spacing: 0.12em;
    }

    .pr-tier-name {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--mute);
    }
    .pr-tier-price {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(34px, 3vw, 42px); line-height: 1;
        letter-spacing: -0.025em;
        display: flex; align-items: baseline; gap: 4px;
    }
    .pr-tier-price small {
        font-family: var(--mono); font-size: 11px; font-weight: 400;
        color: var(--mute); text-transform: uppercase; letter-spacing: 0.1em;
    }
    .pr-tier.featured .pr-tier-price small { color: rgba(255,255,255,0.55); }
    .pr-tier-annual {
        font-family: var(--mono); font-size: 11px;
        color: var(--mute);
        letter-spacing: 0.06em;
        margin-top: -8px;
    }
    .pr-tier.featured .pr-tier-annual { color: rgba(255,255,255,0.55); }
    .pr-tier-tagline { font-size: 14px; line-height: 1.55; color: var(--ink-2); }
    .pr-tier-headcount {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--mute);
        padding-bottom: 16px;
        border-bottom: 1px solid var(--line-soft);
    }
    .pr-tier.featured .pr-tier-headcount { border-bottom-color: rgba(255,255,255,0.12); }

    .pr-tier-features, .pr-tier-excluded {
        list-style: none; padding: 0; margin: 0;
        display: flex; flex-direction: column; gap: 10px;
    }
    .pr-tier-features li, .pr-tier-excluded li {
        font-size: 13.5px; line-height: 1.5;
        padding-left: 22px; position: relative;
        color: var(--ink-2);
    }
    .pr-tier-features li::before {
        content: ''; position: absolute;
        left: 0; top: 8px; width: 12px; height: 1px;
        background: var(--primary);
    }
    .pr-tier-excluded li {
        color: var(--mute); opacity: 0.7;
    }
    .pr-tier-excluded li::before {
        content: '×'; position: absolute;
        left: 0; top: -2px; font-family: var(--mono);
        color: var(--mute);
    }
    .pr-tier.featured .pr-tier-excluded li { color: rgba(255,255,255,0.5); }
    .pr-tier-excluded-label {
        font-family: var(--mono); font-size: 10.5px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--mute); margin: 4px 0 -4px;
    }

    .pr-cta-primary {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 12px 18px; border-radius: 999px;
        background: var(--ink); color: var(--bg);
        border: 1px solid var(--ink);
        font-family: var(--sans); font-size: 13.5px; font-weight: 500;
        text-decoration: none; cursor: pointer;
        transition: all 0.25s var(--ease);
        margin-top: auto;
    }
    .pr-cta-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }

    /* ── Modules strip ── */
    .pr-modules-strip {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 16px; margin: clamp(48px, 6vw, 72px) 0 clamp(24px, 3vw, 40px);
    }
    @media (max-width: 860px) { .pr-modules-strip { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 500px) { .pr-modules-strip { grid-template-columns: 1fr; } }
    .pr-module {
        padding: 22px 20px;
        background: var(--bg-warm);
        border: 1px solid var(--line-soft);
        border-radius: 16px;
    }
    .pr-module-tag {
        font-family: var(--mono); font-size: 10.5px;
        text-transform: uppercase; letter-spacing: 0.14em;
        color: var(--primary-dark);
    }
    .pr-module h3 {
        font-family: var(--sans); font-weight: 500;
        font-size: 15.5px; line-height: 1.3;
        letter-spacing: -0.01em; margin: 8px 0 8px; color: var(--ink);
    }
    .pr-module p {
        font-size: 12.5px; color: var(--ink-2); line-height: 1.5; margin: 0;
    }
    .pr-module-from {
        font-family: var(--mono); font-size: 10.5px;
        text-transform: uppercase; letter-spacing: 0.1em;
        color: var(--mute); margin-top: 12px;
    }

    /* ── FAQ strip ── */
    .pr-faqs {
        display: grid; grid-template-columns: repeat(3, 1fr);
        gap: 28px; margin-top: clamp(40px, 5vw, 64px);
    }
    @media (max-width: 860px) { .pr-faqs { grid-template-columns: 1fr; } }
    .pr-faq h4 {
        font-family: var(--sans); font-weight: 500;
        font-size: 15.5px; line-height: 1.3;
        letter-spacing: -0.01em;
        margin: 0 0 10px; color: var(--ink);
    }
    .pr-faq p { font-size: 13.5px; color: var(--ink-2); line-height: 1.55; margin: 0; }

    /* ── Period helper ── */
    [data-period-monthly], [data-period-annual] { display: none; }
    .pr-root[data-period="monthly"] [data-period-monthly] { display: inline; }
    .pr-root[data-period="annual"]  [data-period-annual] { display: inline; }
</style>
@endpush

@section('content')

<div class="pr-root" data-period="monthly" id="pr-root">

<section class="pr-hero">
    <div class="mk-container mk-container--narrow">
        @if(session('signup_intent') === 'choose_plan_first')
            <div class="pr-intent-nudge" role="status">
                Pick your plan first — your 14-day trial starts after you confirm by email.
            </div>
        @endif
        <span class="eyebrow">Pricing</span>
        <h1 class="mk-display">Four modules, <em>per active employee.</em></h1>
        <p>
            Pick the tier that matches how much of the platform you need. Only bill for employees who are
            actually active — no charge for invited-but-not-started, terminated, or inactive records.
            Annual billing gets you {{ $pricing['annual_months_free'] }} months free.
        </p>

        <div class="pr-controls">
            <div class="pr-toggle" role="group" aria-label="Billing period">
                <button type="button" aria-pressed="true"  data-period-btn="monthly">Monthly</button>
                <button type="button" aria-pressed="false" data-period-btn="annual">Annual <span class="pr-toggle-badge">2 months free</span></button>
            </div>
        </div>
    </div>
</section>

<section class="mk-section mk-section--tight">
    <div class="mk-container">

        {{-- Modules legend --}}
        <div class="pr-modules-strip">
            <div class="pr-module">
                <div class="pr-module-tag">Module 1</div>
                <h3>Employee Journey</h3>
                <p>Onboarding, employee listing, offboarding. The spine of every tier.</p>
                <div class="pr-module-from">Included from Starter</div>
            </div>
            <div class="pr-module">
                <div class="pr-module-tag">Module 2</div>
                <h3>Asset Management</h3>
                <p>Asset inventory, AARF, IT offboarding checklist.</p>
                <div class="pr-module-from">Included from Growth</div>
            </div>
            <div class="pr-module">
                <div class="pr-module-tag">Module 3</div>
                <h3>HRM</h3>
                <p>Leave, attendance, claims, payroll, payslips, EA forms.</p>
                <div class="pr-module-from">Included from Growth</div>
            </div>
            <div class="pr-module">
                <div class="pr-module-tag">Module 4</div>
                <h3>Finance</h3>
                <p>Full accounting — CoA, GL, AR/AP, budgets, tax returns, AI invoice scanning.</p>
                <div class="pr-module-from">Included from Scale</div>
            </div>
        </div>

        <div class="pr-grid">
            @foreach($pricing['tiers'] as $tierKey => $tier)
                @php
                    $monthly = $tier['monthly_usd'] ?? null;
                    $annualFactor = 12 - $pricing['annual_months_free']; // 10
                @endphp
                <article class="pr-tier {{ $tier['featured'] ? 'featured' : '' }}">
                    @if(!empty($tier['badge']))
                        <span class="pr-tier-badge">{{ $tier['badge'] }}</span>
                    @endif

                    <div class="pr-tier-name">{{ $tier['name'] }}</div>

                    <div class="pr-tier-price">
                        @if($monthly === null)
                            {{ $tier['price_label'] ?? 'Custom' }}
                        @else
                            <span>
                                <span data-period-monthly>${{ $monthly }}</span>
                                <span data-period-annual>${{ number_format($monthly * $annualFactor / 12, 0) }}</span>
                            </span>
                            <small>/emp/mo USD</small>
                        @endif
                    </div>

                    @if($monthly !== null)
                        <div class="pr-tier-annual">
                            <span data-period-monthly>Billed monthly</span>
                            <span data-period-annual>
                                Billed annually · ${{ $monthly * $annualFactor }}/emp/yr
                            </span>
                        </div>
                    @endif

                    <div class="pr-tier-tagline">{{ $tier['tagline'] }}</div>
                    <div class="pr-tier-headcount">{{ $tier['headcount_label'] }}</div>

                    <ul class="pr-tier-features">
                        @foreach($tier['features'] as $feature)
                            <li>{{ $feature }}</li>
                        @endforeach
                    </ul>

                    @if(!empty($tier['excluded']))
                        <div class="pr-tier-excluded-label">Not included</div>
                        <ul class="pr-tier-excluded">
                            @foreach($tier['excluded'] as $excluded)
                                <li>{{ $excluded }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if(!empty($tier['cta']['mailto']))
                        <a href="mailto:{{ config('eiaaw.sales_email') }}?subject=EIAAW Workforce Enterprise enquiry" class="pr-cta-primary">{{ $tier['cta']['label'] }}</a>
                    @else
                        <a href="{{ route($tier['cta']['route']) }}?plan={{ $tier['cta']['plan'] }}" class="pr-cta-primary">{{ $tier['cta']['label'] }}</a>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="pr-faqs">
            <div class="pr-faq">
                <h4>What counts as an "active employee"?</h4>
                <p>Anyone with an active record in your workspace on the day we bill. Invited-but-not-started users, terminated employees, and deactivated accounts don't count.</p>
            </div>
            <div class="pr-faq">
                <h4>What's the difference between Growth and Scale?</h4>
                <p>Growth bundles Employee Journey, IT Assets, and full HRM (leave, attendance, claims, payroll, EA forms) — everything most teams need to run people operations end-to-end. Scale adds Module 4 — full-fledged accounting (CoA, GL, AR/AP, budgets, tax returns, AI invoice scanning, claim → ledger auto-posting). Pick Scale when finance and HR run on the same backbone.</p>
            </div>
            <div class="pr-faq">
                <h4>Do I need a credit card for the trial?</h4>
                <p>No. The 14-day Growth-tier trial requires only a work email. On day 15 you pick a plan and add payment — or we auto-downgrade your workspace to Starter.</p>
            </div>
            <div class="pr-faq">
                <h4>What if I grow past my tier's headcount?</h4>
                <p>No surprise charges — we email you before the next invoice and let you choose: stay where you are (pay per extra seat), upgrade tier, or negotiate Enterprise.</p>
            </div>
            <div class="pr-faq">
                <h4>Is there a setup fee?</h4>
                <p>No setup fee on Starter, Growth, or Scale. Enterprise implementations (SSO, dedicated DB, custom integrations) have a scoped setup fee agreed upfront.</p>
            </div>
            <div class="pr-faq">
                <h4>How do I cancel?</h4>
                <p>From your workspace admin → Billing. Cancellation takes effect at the end of the current billing cycle; your data stays read-only for 30 days before deletion.</p>
            </div>
        </div>
    </div>
</section>

</div>{{-- /.pr-root --}}

@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
(function () {
    var root = document.getElementById('pr-root');
    if (!root) return;

    function setPeriod(period) {
        root.setAttribute('data-period', period);
        root.querySelectorAll('[data-period-btn]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn.getAttribute('data-period-btn') === period ? 'true' : 'false');
        });
        try { localStorage.setItem('eiaaw_pr_period', period); } catch (e) {}
    }

    root.querySelectorAll('[data-period-btn]').forEach(function (btn) {
        btn.addEventListener('click', function () { setPeriod(btn.getAttribute('data-period-btn')); });
    });

    try {
        var savedPeriod = localStorage.getItem('eiaaw_pr_period');
        if (savedPeriod === 'monthly' || savedPeriod === 'annual') setPeriod(savedPeriod);
    } catch (e) {}
})();
</script>
@endpush
