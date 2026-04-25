{{-- Shared Dashboard Widget Styles — EIAAW minimalist-ui profile.
     Tokens come from public/brand/eiaaw-tokens.css. No hard colour blocks. --}}
<style>
    /* ── Card shell ── */
    .dash-widget {
        border: 1px solid var(--line-soft);
        border-radius: 18px;
        overflow: hidden;
        background: var(--surface);
        box-shadow:
            0 1px 2px rgba(15,26,29,0.04),
            0 8px 24px -10px rgba(15,26,29,0.10);
        transition: transform .35s var(--ease), box-shadow .35s var(--ease);
        min-height: 220px;
    }
    .dash-widget:hover {
        transform: translateY(-3px);
        box-shadow:
            0 2px 4px rgba(15,26,29,0.06),
            0 16px 36px -12px rgba(15,26,29,0.16);
    }

    /* ── Header — warm cream, ink text, hairline rule ── */
    .dash-widget .widget-header {
        padding: 22px 24px 18px;
        background: linear-gradient(180deg, var(--bg) 0%, var(--bg-warm) 100%);
        border-bottom: 1px solid var(--line-soft);
        position: relative;
    }
    .dash-widget .widget-header::before,
    .dash-widget .widget-header::after { content: none; }

    /* ── Header icon — soft tint chip with brand accent ── */
    .dash-widget .widget-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        background: var(--primary-tint);
        border: 1px solid rgba(17,118,106,0.14);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .dash-widget .widget-icon i {
        font-size: 20px;
        color: var(--primary-dark);
    }

    /* ── Numerals — display weight in ink, not white ── */
    .dash-widget .widget-number {
        font-family: var(--sans);
        font-size: 32px;
        font-weight: 600;
        color: var(--ink);
        line-height: 1;
        letter-spacing: -0.025em;
    }
    .dash-widget .widget-label {
        font-family: var(--mono);
        font-size: 10.5px;
        font-weight: 500;
        color: var(--mute);
        text-transform: uppercase;
        letter-spacing: 0.12em;
        margin-top: 4px;
    }

    /* ── Body ── */
    .dash-widget .widget-body {
        padding: 18px 24px 22px;
        background: var(--surface);
    }

    /* ── Breakdown rows ── */
    .dash-widget .breakdown-title {
        font-family: var(--mono);
        font-size: 10.5px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        color: var(--mute);
        margin-bottom: 8px;
    }
    .dash-widget .breakdown-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 7px 0;
        border-bottom: 1px solid var(--line-soft);
        font-size: 13px;
        color: var(--ink-2);
    }
    .dash-widget .breakdown-row:last-child { border-bottom: none; }
    .dash-widget .breakdown-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 999px;
        background: var(--primary-tint);
        color: var(--primary-dark);
        border: 1px solid rgba(17,118,106,0.14);
    }

    /* ── Section header (above each card) ── */
    .section-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }
    .section-header .section-icon {
        width: 30px; height: 30px;
        border-radius: 8px;
        background: var(--primary-tint);
        border: 1px solid rgba(17,118,106,0.14);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .section-header .section-icon i {
        font-size: 14px !important;
        color: var(--primary-dark) !important;
    }
    .section-header h6 {
        font-family: var(--mono);
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.14em;
        color: var(--primary-dark);
        margin: 0;
        display: inline-flex; align-items: center; gap: 10px;
    }
    .section-header h6::before {
        content: ''; width: 24px; height: 1px;
        background: currentColor; opacity: 0.45;
    }

    /* ── Filter pill (header right, ink-on-cream) ── */
    .dash-widget .widget-filter { position: relative; z-index: 1; }
    .dash-widget .widget-filter select {
        background: var(--surface);
        border: 1px solid var(--line);
        color: var(--ink-2);
        font-family: var(--sans);
        font-size: 11px;
        border-radius: 999px;
        padding: 5px 24px 5px 12px;
        cursor: pointer;
    }
    .dash-widget .widget-filter select option {
        color: var(--ink);
        background: var(--surface);
    }

    /* ── Header context pill (replaces "All Companies" white-on-purple) ── */
    .dash-widget .widget-context {
        font-family: var(--mono);
        font-size: 10px; font-weight: 500;
        padding: 5px 12px;
        border-radius: 999px;
        background: var(--surface);
        border: 1px solid var(--line);
        color: var(--mute);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .dash-widget .widget-context i { color: var(--primary-dark); }

    /* ── Status pills (ink-on-tint) ── */
    .dash-widget .status-pills { display: flex; gap: 6px; flex-wrap: wrap; }
    .dash-widget .status-pill {
        font-family: var(--sans);
        font-size: 11px; font-weight: 600;
        padding: 4px 12px;
        border-radius: 999px;
        background: var(--primary-tint);
        color: var(--primary-dark);
        border: 1px solid rgba(17,118,106,0.14);
    }
</style>
