# EIAAW Workforce — Data Processing Agreement (drafting outline for counsel)

**Status:** pre-launch outline. NOT binding. Counsel converts this into
the signed DPA template used as an addendum to the Terms of Service
before public launch.

**Form:** Enterprise customers sign a paper copy. Starter / Growth / Scale
customers accept the published version by continuing to use the Service;
the accepted version's hash is recorded in the workspace's audit log.

---

## 1. Parties

**Controller:** The Customer, being the legal entity that operates an EIAAW Workforce workspace ("Customer").

**Processor:** EIAAW Solutions Sdn. Bhd. ("EIAAW"), COUNSEL: insert company number and registered office.

This DPA forms part of the Terms of Service between the parties ("Principal Agreement") and controls in the event of conflict with respect to processing of personal data.

## 2. Subject-matter, duration, nature, purpose

**Subject-matter:** processing of personal data submitted by or on behalf of Customer to the EIAAW Workforce Service.

**Duration:** for as long as Customer uses the Service, plus the retention windows in Section 9.

**Nature and purpose:** hosting, storing, processing, and presenting Customer's personal data to enable the Service's Core HR, Payroll, Claims, IT Asset Inventory, Accounting, and AI Assistant modules as described in the published product documentation.

**Types of personal data and data subjects:** see Appendix 1.

## 3. Roles and instructions

3.1 Customer is the Controller. EIAAW is the Processor acting on Customer's documented instructions.

3.2 The Principal Agreement and this DPA constitute Customer's complete and final documented instructions. Any additional instruction must be in writing (email is acceptable) and is subject to separate agreement on fees if outside the scope of the Service.

3.3 EIAAW informs Customer without undue delay if an instruction appears to breach the PDPA, GDPR, or other applicable data-protection law.

## 4. Security measures

4.1 EIAAW implements the technical and organisational measures in Appendix 2. They match or exceed the controls described at `ep.eiaawsolutions.com/security`.

4.2 EIAAW does not make material adverse changes to these measures without Customer notification. Additions that strengthen security require no notification.

## 5. Subprocessors

5.1 Customer grants EIAAW general authorisation to engage subprocessors, provided each subprocessor is bound by written terms providing protections substantially equivalent to this DPA.

5.2 The current subprocessors are listed in Appendix 3.

5.3 EIAAW notifies Customer at least 30 days before engaging a new subprocessor or replacing an existing one. Customer may object on reasonable data-protection grounds; unresolved objections permit Customer to terminate the affected Service with a pro-rata refund.

## 6. Confidentiality

Every EIAAW personnel with access to Customer's personal data is bound by a written confidentiality undertaking that survives termination of employment.

## 7. Assistance

7.1 **Data-subject requests.** EIAAW provides the tools (export, search, delete) for Customer to respond. For requests that EIAAW receives directly, EIAAW forwards them to Customer within 3 business days and does not respond on Customer's behalf unless instructed.

7.2 **Security incidents.** EIAAW assists Customer with notifications to authorities and data subjects as required.

7.3 **Impact assessments.** EIAAW provides information reasonably necessary for Customer's DPIA/TRA on the Service's processing activities.

7.4 **Prior consultations.** EIAAW cooperates with Customer's supervisory-authority consultations where legally required.

## 8. Personal data breach

8.1 EIAAW notifies Customer without undue delay, and in any event within 72 hours of confirming a Personal Data Breach affecting Customer's data.

8.2 The notification includes (as available) the nature of the breach, the categories and approximate numbers of data subjects and records, likely consequences, measures taken, and the contact point for follow-up.

## 9. Return and deletion

9.1 On termination of the Principal Agreement, Customer may export personal data via the admin console for up to 30 days ("read-only window").

9.2 After the 30-day window, EIAAW deletes personal data from primary storage. Encrypted backups containing deleted data are purged within 90 days per backup retention schedule.

9.3 EIAAW retains personal data beyond the 30-day window only where legally required (e.g. 7-year retention for LHDN-relevant payroll records). Those records remain subject to Section 4 security measures.

## 10. Audits

10.1 EIAAW provides Customer with the information reasonably necessary to demonstrate compliance with this DPA, including copies of independent audit reports (SOC 2 Type I once complete per the published roadmap, and later Type II).

10.2 Enterprise Customers may conduct an on-site audit once per 12-month period on 30 days' written notice, during business hours, subject to reasonable scope and confidentiality protections. Costs are borne by Customer unless the audit reveals a material breach.

## 11. International transfers

11.1 Personal data may be transferred to the countries listed for each subprocessor in Appendix 3.

11.2 Transfers outside the jurisdiction of Customer's controlling law rely on:
(a) For EU data subjects — Standard Contractual Clauses (EU 2021/914) attached as Appendix 4, COUNSEL: attach.
(b) For Malaysian data subjects — PDPA Section 129 (contractual transfer) plus equivalent safeguards via this DPA.
(c) For UK data subjects — International Data Transfer Addendum to the EU SCCs.

## 12. Term and survival

This DPA commences on the effective date of the Principal Agreement and continues until the return or deletion of all Customer personal data per Section 9. Sections 4 (security), 6 (confidentiality), 7 (assistance), 8 (breach), 9 (return/deletion), and 11 (transfers) survive termination.

## 13. Liability

Liability under this DPA is subject to the limits in the Principal Agreement. COUNSEL: confirm whether DPA liability stacks with or shares the Principal Agreement's cap; industry norm is shared cap with carve-outs for data-protection fines.

## 14. Governing law

This DPA is governed by the laws of Malaysia. For EU data subjects where the SCCs apply, the SCCs' own clause 17 governs disputes about the SCCs.

---

## Appendix 1 — Details of processing

**Categories of data subjects:** Customer's employees, contractors, applicants, spouses / dependants registered for statutory tax purposes, emergency contacts.

**Categories of personal data:**
- Identity: name, NRIC / passport, date of birth, photograph
- Contact: email, phone, address
- Employment: role, department, start/end dates, salary, contract
- Financial: bank account (partial), EPF account, SOCSO number
- Leave / attendance records
- IT asset assignments
- AI Assistant interactions (prompts, answers)
- Security events (logins, access attempts)

**Special-category / sensitive data (where applicable):**
- Health data (medical leave reasons, where the Customer's policy requires documentation) — controller must ensure PDPA Schedule 2 / GDPR Art. 9 lawful basis
- COUNSEL: flag whether NRIC-related data constitutes sensitive data under PDPA 2010 amendments

**Processing operations:** storage, retrieval, display, export, computation (payroll), transmission (notifications), AI-assisted summarisation, automated statutory-contribution calculation.

## Appendix 2 — Technical and organisational measures

(Abbreviated; the full measure register is maintained internally and provided to Enterprise Customers on request.)

- Postgres Row-Level Security FORCE on every tenant-scoped table
- Non-superuser, non-BYPASSRLS DB role with boot-time verification
- TLS 1.3 in transit; AES-256 disk encryption at rest; encrypted file storage
- HMAC-chained audit log with daily integrity verification job
- MFA / TOTP support with per-tenant enforcement option
- Session rotation on login; single-session enforcement
- Per-request Content-Security-Policy with nonce-based script allow-listing
- Magic-byte content validation and EXIF stripping on uploaded files
- Rate limiting on auth, upload, signup, SSO callback, and API surfaces
- Daily encrypted full backup (30-day retention) + 6-hourly DB snapshot (7-day retention)
- Vulnerability scanning and dependency audit
- Annual external penetration test (scheduled Q4 2026 per public roadmap)

## Appendix 3 — Subprocessors

Identical to Section 6.1 of the Privacy Policy. Maintained at a stable URL for subscriber reference: COUNSEL: decide URL (e.g. `ep.eiaawsolutions.com/legal/subprocessors`).

## Appendix 4 — SCCs

COUNSEL: attach the EU 2021/914 Module 2 (controller-to-processor) with EIAAW as data importer, plus the UK IDTA, for Customers with EU / UK data subjects.

---

## Checklist for counsel

- [ ] Confirm company registration details (Section 1)
- [ ] Decide whether NRIC data is "sensitive" under current PDPA and update Appendix 1
- [ ] Attach the EU SCCs with correct module selection (Appendix 4)
- [ ] Decide liability-stacking behaviour (Section 13)
- [ ] Verify the 72-hour breach window matches the specific sector regulations for regulated-industry Customers (BNM RMiT, MOH)
- [ ] Decide subprocessor-objection resolution process (Section 5.3) — "pro-rata refund" is our default
- [ ] Prepare a Bahasa Malaysia counterpart for Enterprise Customers that request it
