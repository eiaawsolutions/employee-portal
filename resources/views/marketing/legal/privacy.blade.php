@extends('marketing.legal._layout')

@section('title', 'Privacy policy — EIAAW Workforce')
@section('description', 'How EIAAW Workforce collects, uses, and protects personal data under the Malaysian PDPA and equivalent laws.')

@section('legal-title', 'Privacy policy')
@section('legal-lede', 'How we collect, use, and protect personal data — PDPA-aligned and auditable.')

@section('legal-body')
    <h2>1. Scope</h2>
    <p>This policy will cover personal data processed on behalf of workspaces and personal data of visitors to the marketing site, including:</p>
    <ul>
        <li>Data categories collected (employee records, auth events, AI conversations, billing identifiers)</li>
        <li>Lawful basis under PDPA 2010 for each category</li>
        <li>Retention periods and deletion triggers</li>
        <li>Cross-border transfers and the safeguards we rely on</li>
        <li>Subprocessors (Railway, Stripe, Anthropic, Cloudflare) with links to their sub-DPAs</li>
        <li>Individual rights: access, correction, withdrawal, complaint</li>
        <li>Contact point: Data Protection Officer at <a href="mailto:{{ config('eiaaw.support_email') }}">{{ config('eiaaw.support_email') }}</a></li>
    </ul>

    <h2>2. Training and AI</h2>
    <p>
        EIAAW Workforce does <strong>not</strong> use your workspace data to train or
        fine-tune any AI model, whether our own or a third party's. Anthropic's API —
        which powers the Workforce Assistant — does not train on customer data by
        default, and we keep that default in force at the API tier.
    </p>

    <h2>3. Where the real text goes</h2>
    <p>
        The binding privacy policy will be drafted by counsel to meet PDPA 2010 (Malaysia),
        and — for Enterprise workspaces with EU data subjects — GDPR compliance, before
        public launch.
    </p>
@endsection
