@extends('marketing.legal._layout')

@section('title', 'Data processing agreement — EIAAW Workforce')
@section('description', 'The DPA between your workspace (data controller) and EIAAW Solutions (data processor), PDPA / GDPR aligned.')

@section('legal-title', 'Data processing agreement')
@section('legal-lede', 'The processor contract that sits underneath your subscription.')

@section('legal-body')
    <h2>1. Parties and roles</h2>
    <p>
        For personal data processed through EIAAW Workforce, your workspace is the
        <strong>controller</strong> and EIAAW SOLUTIONS (SSM Reg. No. 202603133419 / CT0164540-H) is the
        <strong>processor</strong>. This DPA governs the processor relationship.
    </p>

    <h2>2. Outline of the binding DPA</h2>
    <ul>
        <li>Nature and purpose of processing (defined by the Terms of service)</li>
        <li>Categories of data subjects (employees, contractors, candidates)</li>
        <li>Categories of personal data (identity, contact, employment, payroll, device)</li>
        <li>Security measures: Postgres RLS, encryption at rest, HMAC audit log, MFA, single-session enforcement</li>
        <li>Subprocessor list and change-notification procedure</li>
        <li>Data-subject request handling and controller assistance obligations</li>
        <li>Breach notification (72-hour window, aligned with GDPR Art. 33)</li>
        <li>Audit rights and SOC 2 report handling</li>
        <li>Cross-border transfer mechanisms (SCCs + DPA addenda for EU data)</li>
        <li>Term, termination, and return / deletion of personal data</li>
    </ul>

    <h2>3. Signing the DPA today</h2>
    <p>
        Enterprise workspaces receive a counter-signed DPA as part of the onboarding
        pack. Starter / Growth / Scale tenants accept the published DPA by continuing
        to use the service; the final version is linked from the Terms of service.
        Request a wet-signed copy at <a href="mailto:{{ config('eiaaw.sales_email') }}">{{ config('eiaaw.sales_email') }}</a>.
    </p>
@endsection
