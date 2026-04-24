# EIAAW Workforce — Privacy Policy (drafting outline for counsel)

**Status:** pre-launch outline. NOT binding. Counsel converts this into
the published Privacy Policy before public launch.

**Anchor legislation:**
- Malaysia: Personal Data Protection Act 2010 (PDPA) as amended
- EU data subjects (Enterprise customers with EU workforce): GDPR
- Singapore data subjects (future): PDPA Singapore
- Hong Kong (future): PDPO

COUNSEL: review every `COUNSEL:` marker. Cross-check against EIAAW's
internal data-processing register once it exists.

---

## 1. Who we are

EIAAW Solutions Sdn. Bhd. ("EIAAW", "we"). Registered in Malaysia, COUNSEL: insert company number and registered address.

Data Protection Officer: COUNSEL: insert DPO name + email. Pre-launch contact: `privacy@eiaawsolutions.com`.

## 2. Scope of this policy

This policy covers:

(a) **Personal data we process as a processor** on behalf of Customer workspaces (employee records, auth events, AI conversations, billing identifiers). The Customer is the controller; EIAAW is the processor. The Data Processing Agreement (DPA) governs the processor relationship.

(b) **Personal data we process as a controller** directly — marketing website visitors, sales prospects, support correspondents, and payment information on EIAAW's own Stripe account.

## 3. Data categories and purposes

### 3.1 Workspace personal data (processor role)

| Category | Examples | Purpose | PDPA lawful basis | Retention |
|---|---|---|---|---|
| Identity | Name, NRIC / passport, date of birth | Employment record, statutory reporting | Contract / legal obligation | Duration of employment + 7 years (LHDN) |
| Contact | Email, phone, address | Notifications, statutory records | Contract | Duration + 7 years |
| Employment | Role, department, salary, contract | Payroll, HR admin | Contract | Duration + 7 years |
| Leave / attendance | Leave records, attendance logs | Workforce admin | Contract / legal obligation | 7 years |
| Payroll | Bank account, EPF, SOCSO, PCB | Statutory payments | Legal obligation (EPF Act, SOCSO Act, ITA) | 7 years |
| IT assets | Device assignments, licence seats | Asset governance | Legitimate interest | Duration of assignment + 2 years |
| AI conversations | Prompts, answers, cost | AI feature delivery, abuse prevention, billing | Contract / legitimate interest | 24 months |
| Auth / audit | Login events, 403s, admin actions | Security monitoring | Legitimate interest / legal obligation | 24 months (HMAC-chained) |

COUNSEL: confirm the 7-year retention aligns with current LHDN / EPF schedules; some sectors require longer.

### 3.2 Website and sales personal data (controller role)

| Category | Examples | Purpose | Lawful basis | Retention |
|---|---|---|---|---|
| Contact form submissions | Name, email, company, message | Sales response | Consent / legitimate interest | 24 months from last contact |
| Support correspondence | Email thread content | Customer support | Contract | Duration of subscription + 24 months |
| Billing | Billing contact, VAT ID, Stripe customer | Invoicing, tax | Contract / legal obligation | 7 years |
| Cookies | Session, CSRF, preferred-currency | Service delivery | Strictly necessary (no consent required) / consent | Session to 12 months |

## 4. Sources

Data comes from:
- Information the Customer or its Authorised Users submit directly into the Workspace
- Stripe (billing metadata — EIAAW never sees card PAN)
- EIAAW's own systems (audit logs, AI conversations)
- Cookies set by our application or our subprocessors (see Section 7)

## 5. AI and training

EIAAW does NOT use Customer Data to train or fine-tune any model, owned or third-party. The third-party LLM providers EIAAW uses (Anthropic as primary; OpenAI as fallback) operate APIs that do not train on customer-submitted data by default; EIAAW maintains those defaults.

AI Assistant interactions are stored in the Customer's Workspace as part of the audit trail and are subject to the retention schedule in Section 3.1.

COUNSEL: confirm this statement matches Anthropic's and OpenAI's current terms at the time of publication; they are updated independently of us.

## 6. Disclosures and transfers

### 6.1 Subprocessors

EIAAW relies on the following subprocessors. Each is bound by a written DPA providing equivalent protection to the PDPA and (where applicable) GDPR SCCs.

| Subprocessor | Purpose | Jurisdiction |
|---|---|---|
| Railway | Application hosting, Postgres managed DB | US (primary), regional edges |
| Stripe | Payment processing, billing | Ireland / US |
| Anthropic (PBC) | AI model inference | US |
| OpenAI | AI model inference (fallback) | US |
| Cloudflare | Edge, WAF, TLS, R2 object storage | Global |
| Mailgun | Transactional email delivery | US / EU |

COUNSEL: confirm Cloudflare R2 region selection at launch (EU vs APAC vs global).

### 6.2 Cross-border transfers

Transfers to the US rely on the EU-US Data Privacy Framework where the subprocessor participates, and Standard Contractual Clauses 2021/914 as back-up. For Malaysian data subjects, cross-border transfer relies on PDPA Section 129 (transfer pursuant to contract + consent where required).

### 6.3 Legal disclosures

EIAAW discloses data to law enforcement only on receipt of a valid legal process, and — unless legally prohibited — notifies the Customer before responding so the Customer may object.

## 7. Cookies

We set:
- **Strictly necessary** cookies for session, CSRF, and tenant routing (no consent required under PDPA / GDPR)
- **Functional** cookies for preferred currency, trial-banner dismissal (consent)
- **No** third-party advertising or tracking cookies

## 8. Security

Controls include (non-exhaustive):
- Postgres Row-Level Security enforced at the database (tenant isolation)
- TLS 1.3 in transit; AES-256 at rest
- HMAC-chained audit log with daily integrity verification
- MFA support with per-tenant enforcement option
- Rate-limited file uploads, magic-byte validation, EXIF stripping
- Per-request CSP nonce; no `unsafe-eval`
- Background vulnerability scanning and dependency audit

Full architecture at `ep.eiaawsolutions.com/security`.

## 9. Individual rights

Under PDPA (Malaysia), data subjects have the right to:
- Access the personal data EIAAW holds about them (s.30)
- Correct inaccurate personal data (s.34)
- Withdraw consent where consent is the lawful basis (s.38)
- Limit processing and direct marketing (s.42, s.43)
- Complain to the Personal Data Protection Commissioner

Under GDPR (where applicable), data subjects additionally have rights to erasure, portability, restriction of processing, and objection.

**How to exercise:** contact `privacy@eiaawsolutions.com`. For personal data processed on a Customer's Workspace, EIAAW acts as processor — we will forward the request to the Customer (controller) within 3 business days and assist with fulfilment.

## 10. Children

The Service is not directed at individuals under 16. EIAAW does not knowingly collect personal data from children. Customers processing data on dependants of their employees (e.g. the `employee_child_registrations` table for statutory tax purposes) remain the controller and are responsible for the lawful basis.

## 11. Breach notification

EIAAW notifies affected Customers and (where required) the relevant supervisory authority of any confirmed Personal Data Breach within 72 hours of confirmation, with the information available at that time and updates as the investigation progresses.

## 12. Changes to this policy

EIAAW will post material changes to this policy with 30 days' advance notice to Customers. The "last updated" date at the head of the published version reflects the current effective date.

## 13. Contact

| | |
|---|---|
| General privacy queries | `privacy@eiaawsolutions.com` |
| DPO (Malaysia) | COUNSEL: insert name |
| Supervisory authority (MY) | Personal Data Protection Commissioner — `pdp.gov.my` |
| Supervisory authority (EU) | Lead supervisor is the Customer's jurisdiction; for direct Customers, COUNSEL: insert |

---

## Checklist for counsel

- [ ] Confirm EIAAW company registration details and DPO identity
- [ ] Review the retention schedule against LHDN, EPF, SOCSO, and any sector regulators
- [ ] Confirm Cloudflare R2 region and update Section 6.2 accordingly
- [ ] Review statement in Section 5 against the current Anthropic + OpenAI DPAs (they change)
- [ ] Decide whether to call out specific APAC jurisdictions (SG, ID, TH) or cover under a general "other APAC" clause
- [ ] Decide cookie-banner / consent-manager implementation; currently we claim "no non-essential cookies" — verify with Marketing
- [ ] Localisation — produce a Bahasa Malaysia version for PDPA display requirements
