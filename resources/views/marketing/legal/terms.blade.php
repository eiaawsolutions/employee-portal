@extends('marketing.legal._layout')

@section('title', 'Terms of service — EIAAW Workforce')
@section('description', 'Terms of service for EIAAW Workforce, the AI-native HR platform by EIAAW Solutions Sdn. Bhd.')

@section('legal-title', 'Terms of service')
@section('legal-lede', 'The agreement between your workspace and EIAAW Solutions Sdn. Bhd.')

@section('legal-body')
    <h2>1. Outline</h2>
    <p>This document will define the contract under which workspaces use EIAAW Workforce, covering at minimum:</p>
    <ul>
        <li>Service description and scope (modules, AI assistant, storage)</li>
        <li>Fees, billing cadence, currency, and tax handling</li>
        <li>Trial terms and auto-conversion behaviour</li>
        <li>Acceptable use policy (including prohibitions on scraping, abuse, and unlawful content)</li>
        <li>Subscription term, renewal, and termination</li>
        <li>Warranties, disclaimers, and liability caps</li>
        <li>Governing law (Malaysia, courts of Kuala Lumpur) and dispute resolution</li>
        <li>Service-level targets for Enterprise tenants</li>
    </ul>

    <h2>2. Where the real text goes</h2>
    <p>
        The binding terms will be drafted by Malaysian counsel before public launch
        and will replace this outline in full. If you need a signed copy of the
        pre-launch terms for procurement or an evaluation, contact
        <a href="mailto:{{ config('eiaaw.sales_email') }}">{{ config('eiaaw.sales_email') }}</a>
        — we can supply an NDA and a draft MSA on request.
    </p>

    <h2>3. Companion documents</h2>
    <p>
        The <a href="{{ route('marketing.privacy') }}">Privacy policy</a> and the
        <a href="{{ route('marketing.dpa') }}">Data processing agreement</a>
        form part of the same contract stack and are referenced from these terms.
    </p>
@endsection
