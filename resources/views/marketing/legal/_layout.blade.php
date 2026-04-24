@extends('layouts.marketing')

@push('head')
<style>
    .lg-hero {
        padding: clamp(48px, 6vw, 80px) 0 clamp(24px, 3vw, 40px);
        text-align: center;
    }
    .lg-hero .eyebrow { justify-content: center; }
    .lg-hero h1 { margin: 18px auto 10px; max-width: 820px; }
    .lg-hero p { color: var(--ink-2); font-size: 16px; max-width: 560px; margin: 0 auto; }
    .lg-meta {
        font-family: var(--mono); font-size: 11px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--mute); margin-top: 16px;
    }

    .lg-body {
        max-width: 720px; margin: 0 auto;
        padding: clamp(28px, 4vw, 48px);
        background: var(--surface); border: 1px solid var(--line-soft);
        border-radius: 20px;
        font-size: 15px; line-height: 1.65; color: var(--ink-2);
    }
    .lg-body h2 {
        font-family: var(--sans); font-weight: 500;
        font-size: clamp(20px, 2.2vw, 26px); line-height: 1.2;
        letter-spacing: -0.015em;
        margin: 32px 0 10px; color: var(--ink);
    }
    .lg-body h2:first-child { margin-top: 0; }
    .lg-body h3 {
        font-family: var(--sans); font-weight: 500; font-size: 16px;
        margin: 22px 0 8px; color: var(--ink);
    }
    .lg-body p { margin: 0 0 14px; }
    .lg-body ul, .lg-body ol { margin: 10px 0 18px; padding-left: 22px; }
    .lg-body li { margin-bottom: 8px; }
    .lg-body strong { color: var(--ink); font-weight: 600; }
    .lg-body a { color: var(--primary-dark); text-decoration: underline; }

    .lg-nav {
        display: flex; flex-wrap: wrap; gap: 10px; justify-content: center;
        margin-top: 40px; margin-bottom: 80px;
    }
    .lg-nav a {
        padding: 7px 14px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: var(--surface);
        font-family: var(--mono); font-size: 11.5px;
        text-transform: uppercase; letter-spacing: 0.12em;
        color: var(--ink-2);
    }
    .lg-nav a.active { background: var(--ink); color: var(--bg); border-color: var(--ink); }
</style>
@endpush

@section('content')

<section class="lg-hero">
    <div class="mk-container mk-container--narrow">
        <span class="eyebrow">Legal</span>
        <h1 class="mk-display">@yield('legal-title')</h1>
        <p>@yield('legal-lede')</p>
        <div class="lg-meta">Last updated · @yield('legal-updated', now()->format('M Y'))</div>
    </div>
</section>

@include('marketing.legal._stub-banner')

<div class="mk-container">
    <div class="lg-body">
        @yield('legal-body')
    </div>

    <nav class="lg-nav" aria-label="Legal documents">
        <a href="{{ route('marketing.terms') }}" class="{{ request()->routeIs('marketing.terms') ? 'active' : '' }}">Terms</a>
        <a href="{{ route('marketing.privacy') }}" class="{{ request()->routeIs('marketing.privacy') ? 'active' : '' }}">Privacy</a>
        <a href="{{ route('marketing.dpa') }}" class="{{ request()->routeIs('marketing.dpa') ? 'active' : '' }}">DPA</a>
    </nav>
</div>

@endsection
