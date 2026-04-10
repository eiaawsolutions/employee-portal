# Legal & Regulatory Compliance

Continuous compliance framework covering industry-specific, global, and country-level regulations. Unlike the technical compliance checklists (GDPR, HIPAA, PCI-DSS, SOC 2 in [compliance.md](./compliance.md)), this reference handles the **legal landscape** — identifying which laws apply, building compliance into the system lifecycle, and keeping up as regulations change.

---

## Phase 0: Regulatory Discovery

Run this **before** architecture design. The output feeds directly into Phase 1 requirements.

### Step 1: Map Your Regulatory Surface

Answer these questions to identify every applicable regulation:

#### By Geography (Where do users / data subjects live?)

| Region | Key Regulations |
|---|---|
| **European Union / EEA** | GDPR, ePrivacy Directive, Digital Services Act (DSA), Digital Markets Act (DMA), AI Act |
| **United Kingdom** | UK GDPR, Data Protection Act 2018, Online Safety Act |
| **United States (Federal)** | COPPA (children), CAN-SPAM, FCRA (credit), ECPA (communications), ADA (accessibility), FTC Act §5 |
| **United States (State)** | CCPA/CPRA (California), VCDPA (Virginia), CPA (Colorado), CTDPA (Connecticut), TDPSA (Texas), UCPA (Utah), Oregon Consumer Privacy Act, Montana Consumer Data Privacy Act, + more emerging |
| **Canada** | PIPEDA, Quebec Law 25, CASL (anti-spam) |
| **Brazil** | LGPD (Lei Geral de Proteção de Dados) |
| **Australia** | Privacy Act 1988, CDR (Consumer Data Right), Online Safety Act 2021 |
| **India** | DPDP Act 2023 (Digital Personal Data Protection) |
| **China** | PIPL (Personal Information Protection Law), CSL (Cybersecurity Law), DSL (Data Security Law) |
| **Japan** | APPI (Act on Protection of Personal Information) |
| **South Korea** | PIPA (Personal Information Protection Act) |
| **Singapore** | PDPA (Personal Data Protection Act) |
| **Malaysia** | PDPA 2010 (Personal Data Protection Act) |
| **Thailand** | PDPA (Personal Data Protection Act B.E. 2562) |
| **South Africa** | POPIA (Protection of Personal Information Act) |
| **Nigeria** | NDPR (Nigeria Data Protection Regulation) |
| **UAE / Saudi Arabia** | UAE Federal Decree-Law No. 45/2021, Saudi PDPL |
| **Argentina** | PDPA (Ley de Protección de Datos Personales) |
| **Mexico** | LFPDPPP (Federal Law on Protection of Personal Data) |

#### By Industry

| Industry | Key Regulations |
|---|---|
| **Healthcare** | HIPAA (US), HITECH, FDA 21 CFR Part 11, EU MDR (medical devices), PHIPA (Ontario) |
| **Financial Services** | PCI-DSS, SOX (Sarbanes-Oxley), GLBA (Gramm-Leach-Bliley), PSD2/PSD3 (EU payments), MiFID II, Basel III, DORA (EU digital operational resilience) |
| **Education** | FERPA (US student records), COPPA (children under 13) |
| **Telecommunications** | TCPA (US), ePrivacy Directive (EU), CASL (Canada) |
| **Government / Defense** | FedRAMP, ITAR, EAR, NIST 800-171, CMMC (Cybersecurity Maturity Model) |
| **Insurance** | NAIC Model Laws, Solvency II (EU), IDD (Insurance Distribution Directive) |
| **E-commerce / Retail** | PCI-DSS, consumer protection laws (vary by jurisdiction), distance selling regulations |
| **AI / Machine Learning** | EU AI Act, NIST AI RMF, NYC Local Law 144 (automated employment decisions) |
| **Employment / HR** | Labor laws per jurisdiction, EEOC (US), Working Time Directive (EU), right to disconnect laws |
| **Real Estate** | RESPA (US), Anti-Money Laundering (AML), Know Your Customer (KYC) |

#### By Data Type

| Data Type | Regulations Triggered |
|---|---|
| Personal data / PII | Privacy laws (GDPR, CCPA, LGPD, etc.) |
| Health records / PHI | HIPAA, HITECH, AU Privacy Act (health records) |
| Financial data | PCI-DSS, SOX, GLBA |
| Children's data | COPPA (US, under 13), GDPR Art. 8 (EU, under 16), Age Appropriate Design Code (UK) |
| Biometric data | BIPA (Illinois), GDPR Art. 9, CCPA (special category) |
| Employee data | Employment laws + privacy laws (dual coverage) |
| Geolocation data | GDPR (special consideration), CCPA, state wiretapping laws |
| Communications content | ECPA, Wiretap Act, Telecommunications regulations |
| Criminal records | GDPR Art. 10 (special processing rules), FCRA (US) |
| Genetic data | GINA (US), GDPR Art. 9 (special category) |

### Step 2: Build a Regulatory Register

Create a living document for the project:

```markdown
# Regulatory Register — [Project Name]

| # | Regulation | Jurisdiction | Applies Because | Key Requirements | Owner | Status | Last Reviewed |
|---|---|---|---|---|---|---|---|
| 1 | GDPR | EU/EEA | Users in EU | Consent, data rights, DPA, breach notification | Legal + Engineering | Compliant | 2026-04-01 |
| 2 | CCPA/CPRA | California, US | Users in CA | Opt-out, deletion, do-not-sell | Legal + Engineering | In Progress | 2026-03-15 |
| 3 | PCI-DSS v4.0 | Global | Process payments | Encryption, access control, logging | Security + Engineering | Compliant | 2026-02-01 |
| ... | ... | ... | ... | ... | ... | ... | ... |
```

**Quality Gate**: Regulatory register is complete with at least geography, industry, and data-type analysis done. Legal counsel has reviewed the register.

---

## Compliance Architecture Patterns

### Universal Privacy Pattern (works for GDPR, CCPA, LGPD, PDPA, etc.)

Most modern privacy laws share a common core. Build once, configure per jurisdiction:

```
┌─────────────────────────────────────────────────┐
│              PRIVACY COMPLIANCE LAYER            │
├─────────────────────────────────────────────────┤
│                                                  │
│  ┌──────────────┐  ┌──────────────────────────┐ │
│  │ Consent      │  │ Data Subject Rights      │ │
│  │ Management   │  │ Engine                   │ │
│  │              │  │                          │ │
│  │ • Collection │  │ • Access / Export        │ │
│  │ • Storage    │  │ • Rectification          │ │
│  │ • Withdrawal │  │ • Erasure / Anonymize    │ │
│  │ • Per-purpose│  │ • Portability            │ │
│  │ • Per-region │  │ • Restriction            │ │
│  └──────────────┘  │ • Objection             │ │
│                     │ • Opt-out of sale (CCPA)│ │
│  ┌──────────────┐  └──────────────────────────┘ │
│  │ Data         │  ┌──────────────────────────┐ │
│  │ Classification│  │ Regulatory Config        │ │
│  │              │  │                          │ │
│  │ • PII tagging│  │ • Jurisdiction rules     │ │
│  │ • Sensitivity│  │ • Retention periods      │ │
│  │ • Residency  │  │ • Cross-border transfer  │ │
│  └──────────────┘  │ • Age thresholds         │ │
│                     └──────────────────────────┘ │
│  ┌──────────────────────────────────────────┐   │
│  │ Audit & Evidence Engine                   │   │
│  │ • All rights requests logged              │   │
│  │ • Consent changes tracked                 │   │
│  │ • Processing activities recorded (Art.30) │   │
│  │ • Breach incident log                     │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

### Jurisdiction Configuration Model

Instead of hardcoding compliance rules, make them configurable:

```json
{
  "jurisdictions": {
    "EU": {
      "privacy_law": "GDPR",
      "consent_required_before_processing": true,
      "right_to_erasure": true,
      "right_to_portability": true,
      "breach_notification_hours": 72,
      "breach_notification_to": "supervisory_authority",
      "minimum_age_consent": 16,
      "data_residency_required": false,
      "dpo_required": true,
      "cross_border_mechanism": "SCC_or_adequacy",
      "retention_max_days": null,
      "opt_out_of_sale": false
    },
    "US_CA": {
      "privacy_law": "CCPA/CPRA",
      "consent_required_before_processing": false,
      "right_to_erasure": true,
      "right_to_portability": false,
      "breach_notification_hours": null,
      "breach_notification_to": "attorney_general",
      "minimum_age_consent": 16,
      "data_residency_required": false,
      "opt_out_of_sale": true,
      "do_not_share": true,
      "sensitive_data_opt_in": true
    },
    "BR": {
      "privacy_law": "LGPD",
      "consent_required_before_processing": true,
      "right_to_erasure": true,
      "right_to_portability": true,
      "breach_notification_hours": null,
      "breach_notification_to": "ANPD",
      "minimum_age_consent": 12,
      "dpo_required": true
    }
  }
}
```

### Data Residency & Sovereignty

```
Where must data be stored?
├── EU: No strict residency requirement, but transfers outside EU need legal basis (SCC, adequacy)
├── China: PIPL requires security assessment for cross-border transfers of personal info
├── Russia: Personal data of Russian citizens stored on servers in Russia (Federal Law 242-FZ)
├── India: DPDP allows cross-border transfers except to government-restricted countries
├── Indonesia: GR 71/2019 — public electronic systems may need local data center
├── Vietnam: Cybersecurity Law — important data stored locally
├── Saudi Arabia: Critical data may require in-kingdom storage
├── Turkey: KVKK — cross-border transfers require explicit consent or Board approval
└── Default: Store data in the region closest to users; document transfer mechanisms
```

**Implementation**: Use cloud provider regions + database replication to satisfy residency. Tag data with residency requirements in metadata.

---

## Continuous Compliance Monitoring

Compliance is not a one-time event. Build these processes into the system lifecycle.

### Automated Compliance Checks (CI/CD Integration)

```yaml
# Add to CI/CD pipeline
compliance-checks:
  stage: validate
  steps:
    # 1. PII Detection — scan code for PII handling without encryption
    - name: pii-scan
      run: |
        # Scan for potential PII fields stored without encryption
        # Scan for PII in log statements
        # Scan for hardcoded personal data in test fixtures

    # 2. Data flow mapping — verify data doesn't cross forbidden boundaries
    - name: data-flow-check
      run: |
        # Verify data residency constraints in infrastructure config
        # Check that cross-border data transfers have legal basis documented

    # 3. Consent enforcement — verify consent checks before processing
    - name: consent-check
      run: |
        # Static analysis: processing endpoints check consent status
        # Verify new data collection has consent mechanism

    # 4. Retention enforcement — verify TTLs and cleanup jobs exist
    - name: retention-check
      run: |
        # Verify data retention policies have corresponding cleanup jobs
        # Check that all PII tables have retention metadata

    # 5. Accessibility compliance
    - name: accessibility-check
      run: |
        # axe-core / pa11y for WCAG 2.1 AA compliance
        # Lighthouse accessibility audit
```

### Scheduled Compliance Reviews

| Frequency | Review Activity |
|---|---|
| **Weekly** | Monitor regulatory news feeds for jurisdictions in the register |
| **Monthly** | Review data subject rights request metrics (volume, response times, completion rates) |
| **Quarterly** | Audit consent records, data retention compliance, access control reviews |
| **Semi-annually** | Update regulatory register with new/changed regulations |
| **Annually** | Full compliance audit, penetration test, DPIA review, vendor/processor reassessment |
| **On change** | Any new feature collecting/processing data triggers mini compliance review |

### Regulatory Change Monitoring

Build a process to keep compliance current:

1. **Sources to monitor**:
   - Official government gazette / legislation databases (EUR-Lex, Federal Register, etc.)
   - IAPP (International Association of Privacy Professionals) news
   - Industry-specific regulatory body announcements
   - Cloud provider compliance updates (AWS, Azure, GCP compliance pages)
   - Legal counsel advisories

2. **Impact assessment process** for new/changed regulations:
   ```
   New regulation identified
   ├── 1. Determine applicability (geography × data type × industry)
   ├── 2. Gap analysis (current state vs new requirements)
   ├── 3. Estimate implementation effort and deadline
   ├── 4. Prioritize by enforcement date and risk
   ├── 5. Create implementation tasks (architecture, code, process, documentation)
   ├── 6. Implement and test
   ├── 7. Update regulatory register
   └── 8. Train affected teams
   ```

3. **Regulatory tracker automation** — model in the system:
   ```sql
   CREATE TABLE regulatory_tracker (
       id              SERIAL PRIMARY KEY,
       regulation_name VARCHAR(255) NOT NULL,
       jurisdiction    VARCHAR(100) NOT NULL,
       effective_date  DATE,
       enforcement_date DATE,
       status          VARCHAR(50) NOT NULL DEFAULT 'monitoring',
           -- monitoring | gap_analysis | implementing | compliant | non_compliant
       summary         TEXT,
       impact_level    VARCHAR(20), -- critical | high | medium | low
       owner           VARCHAR(255),
       last_reviewed   TIMESTAMPTZ NOT NULL DEFAULT NOW(),
       next_review     TIMESTAMPTZ,
       notes           TEXT,
       created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
       updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
   );

   CREATE TABLE compliance_evidence (
       id                SERIAL PRIMARY KEY,
       regulation_id     INT REFERENCES regulatory_tracker(id),
       requirement       VARCHAR(500) NOT NULL,
       evidence_type     VARCHAR(100), -- 'automated_test','manual_review','documentation','audit_log'
       evidence_location VARCHAR(500), -- URL, file path, or description
       verified_by       VARCHAR(255),
       verified_at       TIMESTAMPTZ,
       valid_until       TIMESTAMPTZ,
       status            VARCHAR(50) NOT NULL DEFAULT 'pending'
           -- pending | verified | expired | non_compliant
   );
   ```

---

## Industry-Specific Compliance Blueprints

### SaaS / Cloud Platform

```
Required:
├── SOC 2 Type II (trust + credibility for B2B customers)
├── Privacy (GDPR + CCPA minimum, expand per user geography)
├── Accessibility (WCAG 2.1 AA for web, Section 508 for US gov customers)
└── Security (pen testing, vulnerability management, incident response)

Consider:
├── ISO 27001 (international security management standard)
├── CSA STAR (cloud security assurance)
└── Industry-specific certs based on customers (HIPAA, PCI, FedRAMP)
```

### Healthcare Platform

```
Required:
├── HIPAA + HITECH (US)
├── BAAs with all vendors
├── Privacy (GDPR if EU patients)
├── Accessibility (Section 508, WCAG)
└── Data residency per patient geography

Consider:
├── HITRUST CSF (comprehensive healthcare security framework)
├── FDA 21 CFR Part 11 (if electronic records/signatures involved)
└── State-specific health privacy laws (e.g., California CMIA)
```

### Fintech / Payments

```
Required:
├── PCI-DSS (if handling card data — prefer tokenization to reduce scope)
├── AML / KYC (Anti-Money Laundering, Know Your Customer)
├── Privacy (GDPR + CCPA + local laws)
├── Open Banking / PSD2/PSD3 (if in EU)
└── Strong Customer Authentication (SCA) for EU transactions

Consider:
├── SOX (if publicly traded or audited financials)
├── DORA (Digital Operational Resilience Act — EU financial entities)
├── SOC 2 Type II
└── State money transmitter licenses (US)
```

### E-commerce

```
Required:
├── PCI-DSS (reduced scope via hosted payment fields)
├── Privacy (GDPR + CCPA + local consumer protection)
├── Cookie consent (ePrivacy, CCPA)
├── Consumer rights (return policies, terms of service)
├── Accessibility (WCAG 2.1 AA — increasingly enforced)
└── Email compliance (CAN-SPAM, CASL, GDPR marketing consent)

Consider:
├── Product safety regulations per jurisdiction
├── Tax compliance (sales tax, VAT, GST)
└── Import/export regulations for cross-border commerce
```

### HR / Employment Platform

```
Required:
├── Employment law per jurisdiction (labor codes, working hours, leave entitlements)
├── Privacy (employee data has special rules in many jurisdictions)
├── Anti-discrimination (EEOC/US, Equality Act/UK, EU directives)
├── Background check regulations (FCRA/US, DBS/UK)
├── Payroll tax compliance per jurisdiction
└── Data retention (employment records retention varies: 3-7 years typical)

Consider:
├── GDPR Art. 88 (specific provisions for employment context)
├── Works council / union notification requirements (EU)
├── Right to disconnect laws (France, Portugal, Belgium, etc.)
├── AI Act (EU — automated hiring decisions require disclosure)
└── NYC Local Law 144 (automated employment decision tools)
```

---

## Accessibility Compliance (Often Overlooked)

### Legal Requirements

| Jurisdiction | Law | Standard |
|---|---|---|
| US (public sector) | Section 508 of Rehabilitation Act | WCAG 2.1 AA |
| US (private sector) | ADA Title III (increasingly applied to websites) | WCAG 2.1 AA |
| EU | European Accessibility Act (EAA), EN 301 549 | WCAG 2.1 AA |
| UK | Equality Act 2010, Public Sector Bodies Accessibility Regulations | WCAG 2.1 AA |
| Canada | ACA (Accessible Canada Act), AODA (Ontario) | WCAG 2.1 AA |
| Australia | Disability Discrimination Act 1992 | WCAG 2.0 AA |

### Technical Implementation

- [ ] Semantic HTML (headings, landmarks, lists, tables)
- [ ] All images have meaningful `alt` text (or `alt=""` for decorative)
- [ ] Color contrast ratio ≥ 4.5:1 (text), ≥ 3:1 (large text, UI components)
- [ ] All functionality accessible via keyboard (tab order, focus visible, no keyboard traps)
- [ ] Form fields have associated labels
- [ ] Error messages are descriptive and associated with the field
- [ ] ARIA attributes used correctly (prefer native HTML elements first)
- [ ] Media has captions (video) and transcripts (audio)
- [ ] Content is responsive and works at 200% zoom
- [ ] No content that flashes more than 3 times per second
- [ ] Language declared in HTML (`lang` attribute)
- [ ] Skip-to-content link provided

### Automated Testing
```bash
# CI pipeline — add accessibility checks
npx axe-core-cli https://localhost:3000 --exit   # Zero violations
npx pa11y https://localhost:3000                   # WCAG 2.1 AA
lighthouse --only-categories=accessibility --output=json https://localhost:3000
```

---

## Contract & Agreement Templates

Track these legal documents as part of the project:

| Document | When Needed | Review Frequency |
|---|---|---|
| **Privacy Policy** | Before collecting any personal data | On every feature change affecting data collection |
| **Terms of Service** | Before users can access the system | Annually + on significant feature changes |
| **Cookie Policy / Banner** | If using cookies or tracking (all web apps) | When tracking technologies change |
| **Data Processing Agreement (DPA)** | For every third-party processor of personal data | Annually + on processor changes |
| **Business Associate Agreement (BAA)** | Healthcare — with every vendor touching PHI | Annually |
| **Sub-processor List** | GDPR — maintain and publish list of sub-processors | On any sub-processor change |
| **Data Protection Impact Assessment (DPIA)** | High-risk processing (profiling, large-scale PII, new tech) | On relevant feature changes |
| **Records of Processing Activities (RoPA)** | GDPR Art. 30 — mandatory for organizations ≥250 employees | Quarterly |
| **Incident Response Plan** | All regulated systems | Annually + after every incident |
| **Data Retention Schedule** | All systems storing personal or regulated data | Annually |

---

## Compliance Evidence Collection

For audits (SOC 2, ISO 27001, PCI-DSS assessments), evidence must be pre-collected:

### Automated Evidence Sources

| Evidence Type | Source | Retention |
|---|---|---|
| Access control reviews | IAM provider export (Okta, Azure AD) | Quarterly snapshots, keep 2 years |
| Change management | Git commit history + PR approvals | Indefinite (inherent to VCS) |
| Vulnerability management | Dependabot/Snyk reports, scan results | Keep all, minimum 2 years |
| Penetration test reports | Third-party assessment documents | Keep 3 years |
| Incident response records | Post-mortem documents, ticket history | Keep 5 years |
| Backup test results | Restore test logs with timestamps | Keep 2 years |
| Employee training records | LMS completion certificates | Duration of employment + 3 years |
| Data subject rights requests | Rights request log with response times | Keep per retention policy (3-6 years typical) |
| Consent records | Consent database with timestamps | Duration of processing relationship + 5 years |
| Audit logs | Centralized logging system | Per regulation (HIPAA: 6yr, PCI: 1yr, SOC2: 1yr) |

### Evidence-as-Code

Embed compliance checks into the codebase so evidence is generated automatically:

```
# Pseudocode — compliance test suite
describe "GDPR Compliance" do
  test "user can export their data" do
    user = create_test_user()
    export = request_data_export(user.id)
    assert export.contains(user.personal_data)
    assert export.format == "JSON"
  end

  test "user can delete their account" do
    user = create_test_user()
    delete_user(user.id)
    assert user_data_anonymized(user.id)
    assert audit_log_contains("USER_ERASURE", user.id)
  end

  test "consent required before data processing" do
    user = create_test_user(consent: false)
    result = attempt_processing(user.id)
    assert result.denied?
    assert result.reason == "consent_not_given"
  end

  test "data retention enforced" do
    old_record = create_record(created_at: 3.years.ago)
    run_retention_job()
    assert record_deleted_or_anonymized(old_record.id)
  end
end
```

---

## Compliance Incident Response

When a compliance incident occurs (data breach, regulatory inquiry, audit finding):

### Breach Response Timeline

```
T+0:    Incident detected
├── T+1h:   Incident response team assembled, preliminary assessment
├── T+4h:   Scope determined (what data, how many affected, root cause)
├── T+24h:  Containment complete, forensic preservation started
├── T+48h:  Regulatory notification prepared
├── T+72h:  GDPR supervisory authority notified (if applicable)
├── T+30d:  Affected individuals notified (GDPR)
├── T+60d:  Affected individuals notified (HIPAA)
├── T+90d:  Full incident report completed
└── T+180d: Remediation verified, lessons learned documented
```

### Regulatory Inquiry Response

```
1. Acknowledge receipt immediately
2. Engage legal counsel specialized in the relevant regulation
3. Preserve all relevant records (legal hold)
4. Gather requested evidence from automated sources
5. Prepare response within required timeline
6. Implement any corrective actions identified
7. Update regulatory register with inquiry outcome
8. Adjust processes to prevent recurrence
```
