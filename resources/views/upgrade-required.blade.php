@extends('layouts.app')

@section('title', 'Upgrade required')

@section('content')
<style>
    .ur-wrap {
        max-width: 720px; margin: 60px auto;
        padding: clamp(32px, 5vw, 56px);
        background: var(--surface, #FFFFFF);
        border: 1px solid var(--line-soft, #E8DFCC);
        border-radius: 20px;
    }
    .ur-pill {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 999px;
        background: var(--primary-tint, #E5F4F1);
        color: var(--primary-dark, #11766A);
        font-family: var(--mono, 'JetBrains Mono', monospace);
        font-size: 11px; font-weight: 500;
        text-transform: uppercase; letter-spacing: 0.12em;
    }
    .ur-pill::before {
        content: ''; width: 6px; height: 6px; border-radius: 50%;
        background: var(--warn, #C68A2E);
    }
    .ur-wrap h1 {
        font-family: var(--sans, 'Inter', sans-serif);
        font-weight: 500; font-size: clamp(28px, 3.4vw, 40px); line-height: 1.1;
        letter-spacing: -0.025em;
        margin: 20px 0 12px; color: var(--ink, #0F1A1D);
    }
    .ur-wrap h1 em {
        font-family: var(--serif, 'Instrument Serif', serif);
        font-style: italic; font-weight: 400;
        color: var(--primary-dark, #11766A);
    }
    .ur-wrap > p {
        color: var(--ink-2, #2A3438); font-size: 16px;
        line-height: 1.55; margin: 0 0 28px;
    }
    .ur-compare {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 16px; margin: 28px 0 32px;
    }
    @media (max-width: 600px) { .ur-compare { grid-template-columns: 1fr; } }
    .ur-cell {
        border: 1px solid var(--line-soft, #E8DFCC);
        border-radius: 14px; padding: 22px;
        background: var(--bg-warm, #F3EDE0);
    }
    .ur-cell.target {
        background: var(--ink, #0F1A1D); color: var(--bg, #FAF7F2);
        border-color: var(--ink, #0F1A1D);
    }
    .ur-cell-label {
        font-family: var(--mono, monospace); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--mute, #6B7A7F);
    }
    .ur-cell.target .ur-cell-label { color: rgba(255,255,255,0.55); }
    .ur-cell-plan {
        font-family: var(--sans, sans-serif); font-weight: 500;
        font-size: 24px; line-height: 1.1;
        margin: 6px 0 10px;
    }
    .ur-cell-feat {
        font-size: 13.5px; line-height: 1.5;
    }
    .ur-cell.target .ur-cell-feat { color: rgba(255,255,255,0.85); }
    .ur-ctas {
        display: flex; gap: 12px; flex-wrap: wrap;
        padding-top: 24px;
        border-top: 1px solid var(--line-soft, #E8DFCC);
    }
    .ur-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 11px 18px; border-radius: 999px;
        font-family: var(--sans, sans-serif); font-size: 14px; font-weight: 500;
        text-decoration: none;
        border: 1px solid transparent;
        transition: all 0.25s var(--ease, cubic-bezier(.2,.7,.2,1));
    }
    .ur-btn-primary {
        background: var(--ink, #0F1A1D); color: var(--bg, #FAF7F2);
        border-color: var(--ink, #0F1A1D);
    }
    .ur-btn-primary:hover {
        background: var(--primary-dark, #11766A); border-color: var(--primary-dark, #11766A);
        color: var(--bg, #FAF7F2);
    }
    .ur-btn-outline {
        background: transparent; color: var(--ink-2, #2A3438);
        border-color: var(--line, #D9CFBC);
    }
    .ur-btn-outline:hover {
        background: var(--bg-warm, #F3EDE0); color: var(--ink, #0F1A1D);
    }
</style>

<div class="ur-wrap">
    <span class="ur-pill">Upgrade required</span>
    <h1>{{ $featureLabel }} needs <em>a higher plan.</em></h1>
    <p>
        Your workspace is on the <strong>{{ ucfirst($currentPlan) }}</strong> plan.
        {{ $featureLabel }} is included from the <strong>{{ ucfirst($recommendedPlan) }}</strong> tier upward.
    </p>

    <div class="ur-compare">
        <div class="ur-cell">
            <div class="ur-cell-label">Current</div>
            <div class="ur-cell-plan">{{ ucfirst($currentPlan) }}</div>
            <div class="ur-cell-feat">{{ $featureLabel }} — not included</div>
        </div>
        <div class="ur-cell target">
            <div class="ur-cell-label">Recommended</div>
            <div class="ur-cell-plan">{{ ucfirst($recommendedPlan) }}</div>
            <div class="ur-cell-feat">{{ $featureLabel }} — included</div>
        </div>
    </div>

    <div class="ur-ctas">
        <a href="mailto:{{ config('eiaaw.sales_email', 'sales@eiaawsolutions.com') }}?subject=Upgrade to {{ ucfirst($recommendedPlan) }}&body=I'd like to upgrade our {{ $tenant?->name ?? 'workspace' }} workspace to the {{ ucfirst($recommendedPlan) }} plan."
           class="ur-btn ur-btn-primary">
            Contact sales to upgrade →
        </a>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="ur-btn ur-btn-outline">
            Back
        </a>
    </div>
</div>
@endsection
