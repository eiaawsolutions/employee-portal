@extends('marketing.legal._layout')

@section('title', 'Data Processing Agreement — EIAAW Workforce')
@section('description', 'How EIAAW SOLUTIONS processes employee and payroll data as your data processor: security, sub-processors, breach notice, transfers and deletion. PDPA-aligned.')

@section('legal-title', 'Data processing agreement')
@section('legal-lede', 'The processor terms that apply to the employee, payroll and business records you keep in EIAAW Workforce.')
@section('legal-updated', '24 September 2026')

@php $contact = config('eiaaw.privacy_email'); @endphp

@section('legal-body')
    <h2>1. Parties, roles and when this applies</h2>
    <p>This Data Processing Agreement (“DPA”) is part of the <a href="{{ route('marketing.terms') }}">Terms of Service</a> between EIAAW SOLUTIONS, registered in Malaysia under SSM {{ config('eiaaw.company_reg_no') }} (“EIAAW”, the processor), and the organisation that owns an EIAAW Workforce workspace (the “Customer”, the data user or controller). It applies automatically when the Customer accepts the Terms, and covers personal data the Customer or its users put into the workspace (“Customer Personal Data”). Enterprise customers can sign a version of this DPA with changes agreed in their order form.</p>

    <h2>2. What we process</h2>
    <ul>
        <li><strong>Subject matter and purpose:</strong> hosting and processing Customer Personal Data to provide, secure and support EIAAW Workforce, as set out in the Terms.</li>
        <li><strong>Duration:</strong> for the life of the subscription, then until deletion under section 10.</li>
        <li><strong>People concerned:</strong> the Customer’s employees, former employees, interns, contractors, their dependants and emergency contacts, and the Customer’s users, suppliers and customers recorded in the accounting module.</li>
        <li><strong>Types of data:</strong> identity and contact details, identity-document numbers (such as NRIC or passport), employment details, education history, family details, bank details, salary, statutory contributions and tax, leave, attendance, expense claims and receipts, assigned IT assets, accounting records, and the uploaded documents that support them.</li>
    </ul>

    <h2>3. The Customer’s instructions</h2>
    <p>We process Customer Personal Data only on the Customer’s documented instructions, which are the Terms, this DPA and the Customer’s use of the service’s features, unless the law requires otherwise; if so, we tell the Customer first unless the law forbids it. If we believe an instruction breaks data-protection law, we will say so. The Customer is responsible for having a lawful basis for the data it puts into the workspace and for giving its employees the notices their law requires.</p>

    <h2>4. Our people</h2>
    <p>Only EIAAW personnel who need access to run and support the service can reach Customer Personal Data, and they are bound by confidentiality.</p>

    <h2>5. Security</h2>
    <p>We apply technical and organisational measures appropriate to the risk, as the Security Principle of Malaysia’s PDPA requires of processors, including:</p>
    <ul>
        <li>HTTPS for all traffic to and from the service;</li>
        <li>row-level security in the database, so each workspace can only read its own rows;</li>
        <li>passwords stored as one-way hashes, two-factor authentication for every user, and role-based access inside each workspace;</li>
        <li>a tamper-evident (hash-chained) audit log of actions in the workspace;</li>
        <li>AI features that read only records the signed-in user may already see, and cannot change data.</li>
    </ul>

    <h2>6. Sub-processors</h2>
    <p>The Customer authorises us to use these sub-processors for Customer Personal Data:</p>
    <div class="lg-table">
        <table>
            <thead><tr><th>Sub-processor</th><th>Purpose</th><th>Location</th></tr></thead>
            <tbody>
                <tr><td>Railway</td><td>Application and database hosting</td><td>Singapore</td></tr>
                <tr><td>Cloudflare</td><td>DNS, content delivery and security filtering</td><td>Global network</td></tr>
                <tr><td>Anthropic</td><td>AI model behind the Workforce Assistant and AI features</td><td>United States</td></tr>
                <tr><td>Resend</td><td>Sending workspace emails (invitations, approvals, notifications)</td><td>United States</td></tr>
            </tbody>
        </table>
    </div>
    <p>Stripe processes billing details of the people who manage the subscription, not employee records. We bind every sub-processor to data-protection terms at least as protective as this DPA and remain responsible for them. We give at least 14 days’ notice, by email to the workspace owner, before adding or replacing a sub-processor of Customer Personal Data. If the Customer objects on reasonable data-protection grounds and we can’t address the concern, the Customer may cancel and receive a pro-rata refund of prepaid fees.</p>

    <h2>7. Transfers outside Malaysia</h2>
    <p>Customer Personal Data is stored in Singapore. Some sub-processors process it in other countries, including the United States. We rely on sub-processors bound by terms that protect the data to a standard comparable to Malaysian law, consistent with section 129 of the PDPA as amended, and will put in place any additional transfer mechanism the Customer’s law requires where we reasonably can.</p>

    <h2>8. Helping the Customer</h2>
    <p>Taking into account what the service does, we help the Customer respond to requests from individuals exercising their rights (access, correction, deletion, portability and withdrawal of consent), and with data protection impact assessments and regulator enquiries about the service. If an individual contacts us directly about Customer Personal Data, we pass the request to the Customer and don’t respond to it ourselves unless the Customer asks us to.</p>

    <h2>9. Personal data breaches</h2>
    <p>If we become aware of a breach affecting Customer Personal Data, we notify the Customer without undue delay and within 48 hours, with what we know about its nature, the data and people affected, likely consequences and the steps we are taking, and we update the Customer as we learn more. This lets the Customer meet its own duty to notify the Personal Data Protection Commissioner within 72 hours and affected individuals where required.</p>

    <h2>10. Return and deletion</h2>
    <p>The Customer can export employee, asset, claims and onboarding records as CSV from the workspace at any time, and we provide a full export on request. After the subscription ends, the workspace is read-only for 30 days; we then delete Customer Personal Data from the primary database and remove any remaining copies within 90 days of cancellation, unless the law requires us to keep it.</p>

    <h2>11. Information and audits</h2>
    <p>We make available the information reasonably needed to show we meet this DPA, and answer reasonable security questionnaires once a year, or after a breach. Other audits, including on-site audits, can be agreed in an Enterprise order form, at the Customer’s cost and with reasonable notice.</p>

    <h2>12. Precedence and contact</h2>
    <p>If this DPA and the Terms conflict on the processing of personal data, this DPA prevails. The liability limits in the Terms apply. Questions about this DPA: <a href="mailto:{{ $contact }}">{{ $contact }}</a> (subject “DPA”).</p>
@endsection
