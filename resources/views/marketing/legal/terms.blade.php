@extends('marketing.legal._layout')

@section('title', 'Terms of Service — EIAAW Workforce')
@section('description', 'The terms for using EIAAW Workforce: trial, per-employee billing, your data, AI features, cancellation and liability. Governed by Malaysian law.')

@section('legal-title', 'Terms of service')
@section('legal-lede', 'The agreement between your organisation and EIAAW SOLUTIONS for using EIAAW Workforce.')
@section('legal-updated', '24 September 2026')

@php $contact = config('eiaaw.privacy_email'); @endphp

@section('legal-body')
    <h2>1. Who these terms are between</h2>
    <p>These terms are an agreement between EIAAW SOLUTIONS, registered in Malaysia under SSM {{ config('eiaaw.company_reg_no') }}, Kuala Lumpur (“EIAAW”, “we”), and the organisation that signs up for an EIAAW Workforce workspace (“you”). The person who signs up confirms they are authorised to accept these terms for that organisation. Our <a href="{{ route('marketing.privacy') }}">Privacy Notice</a> and <a href="{{ route('marketing.dpa') }}">Data Processing Agreement</a> form part of these terms. An Enterprise order form signed by both of us takes priority over these terms where they differ.</p>

    <h2>2. The service</h2>
    <p>EIAAW Workforce is web software for managing employees, IT assets, HR (leave, attendance, claims and payroll) and accounting. What each plan includes is shown on the <a href="{{ route('marketing.pricing') }}">pricing page</a>. We keep improving the service; we won’t remove a core feature of your plan during a period you have already paid for without telling you first and offering a pro-rata refund if the change materially reduces what you paid for.</p>

    <h2>3. Accounts and security</h2>
    <p>Keep the details you give us accurate, keep sign-in credentials confidential, and tell us promptly at <a href="mailto:{{ $contact }}">{{ $contact }}</a> if you suspect unauthorised access. You decide who gets access to your workspace and what role they have, and you are responsible for what your users do in it. We recommend turning on two-factor authentication for every administrator.</p>

    <h2>4. Free trial</h2>
    <p>A new workspace starts with a 14-day free trial of the plan you chose, for up to 5 users, without a payment card. If you haven’t chosen a paid plan when the trial ends, the workspace moves to the Starter plan and keeps your data. Starter is a paid plan: to keep using the workspace after the trial, tell us you want to subscribe and we’ll send you an invoice. We never charge a card you haven’t given us.</p>

    <h2>5. Fees and billing</h2>
    <ul>
        <li>Plans are priced in US dollars per active employee per month. An active employee is an employee record with an active status in your workspace; invited employees who haven’t started, and deactivated or exited employees, are not counted.</li>
        <li>Starter, Growth and Scale have a minimum of 5 billable employees. Enterprise pricing, minimums and terms are agreed in an order form.</li>
        <li>You pay monthly or annually, in advance. We send invoices through Stripe, which you can pay by card. Annual billing costs 10 times the monthly price.</li>
        <li>Prices exclude any applicable taxes, which we show on the invoice.</li>
        <li>You can change plan at any time by telling us; the new plan’s features apply straight away and its price applies from your next billing period.</li>
        <li>If a payment fails and isn’t fixed within the grace period we tell you about, we may suspend the workspace until it is. We don’t refund partial periods except where the law requires it or these terms say so.</li>
        <li>We may change prices with at least 30 days’ notice; the change applies from your next billing period.</li>
    </ul>

    <h2>6. Your data</h2>
    <p>You own the data you put into your workspace (“Customer Data”). You give us permission to host, process and display it only as needed to provide and support the service, and our Data Processing Agreement governs how we handle the personal data in it. We don’t sell Customer Data and we don’t use it to train AI models. You are responsible for having a lawful basis to put personal data into the workspace, including giving your employees the notices your local law requires. You can export employee, asset, claims and onboarding records as CSV from within the workspace at any time, and we will provide a full export of your workspace on request.</p>

    <h2>7. Acceptable use</h2>
    <p>Don’t use the service to break the law, infringe others’ rights, send spam, upload malware, probe or overload our systems, access another customer’s workspace, or resell the service without our written agreement. We may suspend access that puts the service or other customers at risk, and we’ll tell you why unless the law or an ongoing investigation prevents it.</p>

    <h2>8. AI features and payroll calculations</h2>
    <p>The Workforce Assistant and other AI features can make mistakes. Check AI output before relying on it. Payroll deductions (EPF, SOCSO, EIS and PCB) are calculated from the statutory rates held in your workspace. You remain responsible for reviewing and approving each pay run, for the accuracy of the employee data behind it, and for your own statutory filings and payments to LHDN, KWSP, PERKESO and other authorities.</p>

    <h2>9. Availability and support</h2>
    <p>We work to keep the service available and to fix problems quickly, but we don’t promise it will be uninterrupted or error-free. Service levels, uptime commitments and service credits apply only where an Enterprise order form sets them out. Support is by email at <a href="mailto:{{ $contact }}">{{ $contact }}</a>.</p>

    <h2>10. Cancellation and termination</h2>
    <p>You can cancel at any time; cancellation takes effect at the end of the current billing period. We may end these terms if you seriously breach them and don’t fix the breach within 14 days of our notice, or immediately if the law requires it. After the subscription ends, the workspace is read-only for 30 days so you can export your data; we then delete Customer Data from the primary database and remove any remaining copies within 90 days of cancellation, unless the law requires us to keep something longer.</p>

    <h2>11. Warranties</h2>
    <p>We provide the service with reasonable skill and care. Apart from that and anything the law doesn’t allow us to exclude, the service is provided “as is”, without other warranties.</p>

    <h2>12. Liability</h2>
    <p>Neither of us is liable to the other for indirect or consequential loss, or for lost profits, revenue or data that could have been avoided by keeping reasonable exports. Each party’s total liability under these terms in any 12-month period is limited to the fees you paid us in that period. These limits don’t apply to your payment obligations, to either party’s liability for fraud, or to anything else the law doesn’t allow to be limited.</p>

    <h2>13. Confidentiality</h2>
    <p>Each of us will keep the other’s non-public information confidential and use it only for this agreement, except where the law requires disclosure.</p>

    <h2>14. Changes to these terms</h2>
    <p>We may update these terms. For changes that materially affect you, we give at least 30 days’ notice by email or in the workspace; if you don’t accept them, you can cancel before they take effect.</p>

    <h2>15. Governing law</h2>
    <p>These terms are governed by the laws of Malaysia, and the courts of Kuala Lumpur have jurisdiction. Before going to court, we’ll both try in good faith to resolve any dispute by talking to each other.</p>

    <h2>16. Contact</h2>
    <p>EIAAW SOLUTIONS, Kuala Lumpur, Malaysia · SSM {{ config('eiaaw.company_reg_no') }} · <a href="mailto:{{ $contact }}">{{ $contact }}</a></p>
@endsection
