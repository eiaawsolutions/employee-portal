# Compliance Frameworks Reference

Practical checklists for GDPR, HIPAA, PCI-DSS, and SOC 2. Use during Phase 1 (requirements) to identify applicable compliance requirements, and Phase 4 (validation) to verify compliance.

---

## Identifying Applicable Frameworks

```
What data does the system process?
├── Personal data of EU/UK residents → GDPR
├── Protected Health Information (PHI) → HIPAA
├── Credit card / payment data → PCI-DSS
├── Customer data for B2B SaaS → SOC 2
└── Multiple of the above → Apply all applicable frameworks
```

---

## GDPR (General Data Protection Regulation)

Applies to: Any system processing personal data of EU/EEA/UK residents, regardless of where the system is hosted.

### Technical Implementation Checklist

#### Lawful Basis & Consent
- [ ] Documented lawful basis for each processing activity (consent, contract, legitimate interest, etc.)
- [ ] Consent collection is explicit, granular, and freely given (no pre-checked boxes)
- [ ] Consent records stored with timestamp, IP, and version of the consent text
- [ ] Consent withdrawal mechanism is as easy as giving consent
- [ ] Privacy policy accessible before data collection

#### Data Subject Rights (Articles 15-22)
- [ ] **Right of access**: API or UI for users to export their personal data (JSON/CSV)
- [ ] **Right to rectification**: Users can update their personal data
- [ ] **Right to erasure** ("right to be forgotten"): Deletion endpoint that removes or anonymizes all PII
- [ ] **Right to data portability**: Machine-readable export (JSON) of user-provided data
- [ ] **Right to restrict processing**: Ability to flag an account for restricted processing
- [ ] **Right to object**: Opt-out mechanism for marketing/profiling
- [ ] All rights requests processed within 30 days (documented response process)

#### Data Minimization & Storage
- [ ] Collect only data necessary for the stated purpose
- [ ] Retention periods defined per data category (auto-delete or anonymize after expiry)
- [ ] Automated data cleanup jobs for expired data
- [ ] No unnecessary PII in logs, backups, or analytics

#### Data Protection by Design
- [ ] Encryption at rest for all PII (database-level or field-level encryption)
- [ ] Encryption in transit (TLS 1.2+ on all connections)
- [ ] Pseudonymization where full identity is not required
- [ ] Access controls — only authorized personnel can access PII
- [ ] Audit logging for all PII access and modifications
- [ ] Data Processing Impact Assessment (DPIA) performed for high-risk processing

#### Cross-Border Transfers
- [ ] Data transfer mechanisms in place (Standard Contractual Clauses, adequacy decisions)
- [ ] Third-party data processors have signed Data Processing Agreements (DPAs)
- [ ] Sub-processor list maintained and communicated to data subjects

#### Breach Notification
- [ ] Incident response plan documented
- [ ] 72-hour breach notification capability to supervisory authority
- [ ] Breach notification template prepared for data subjects
- [ ] Breach detection mechanisms (anomaly alerts, access monitoring)

---

## HIPAA (Health Insurance Portability and Accountability Act)

Applies to: Covered entities (healthcare providers, health plans, clearinghouses) and their business associates processing Protected Health Information (PHI).

### Technical Safeguards (§164.312)

#### Access Control
- [ ] Unique user identification — each user has a unique ID
- [ ] Emergency access procedure documented
- [ ] Automatic logoff after inactivity (session timeout ≤ 15 minutes for clinical systems)
- [ ] Encryption of ePHI at rest (AES-256)
- [ ] Role-based access control — minimum necessary access to PHI
- [ ] Multi-factor authentication for remote access

#### Audit Controls
- [ ] Audit logs for all access to ePHI (who, what, when, where)
- [ ] Logs tamper-proof (append-only, or shipped to immutable storage)
- [ ] Audit log retention: minimum 6 years
- [ ] Regular audit log review process (automated alerts for anomalies)
- [ ] Failed login attempts logged and monitored

#### Integrity Controls
- [ ] Mechanism to verify ePHI has not been altered or destroyed (checksums, digital signatures)
- [ ] Database integrity constraints on PHI fields
- [ ] Backup integrity verification (regular restore tests)

#### Transmission Security
- [ ] TLS 1.2+ for all ePHI in transit
- [ ] ePHI encrypted in emails (S/MIME, TLS gateway, or encrypted portal)
- [ ] VPN for remote administrative access
- [ ] No ePHI transmitted over unencrypted channels (SMS, unencrypted email)

### Administrative Safeguards
- [ ] Business Associate Agreements (BAAs) with all vendors processing PHI
- [ ] Security Risk Assessment performed annually
- [ ] Workforce training on HIPAA policies
- [ ] Sanctions policy for violations
- [ ] Contingency plan (data backup, disaster recovery, emergency mode operations)

### Physical Safeguards (for infrastructure)
- [ ] Cloud provider is HIPAA-eligible and BAA is signed (AWS, Azure, GCP all offer this)
- [ ] Data center physical access controls (managed by cloud provider — verify in BAA)
- [ ] Workstation security policies for developers with PHI access

### Implementation Notes
- **AWS**: Use dedicated HIPAA-eligible services. Enable CloudTrail, encrypt all S3/RDS/EBS.
- **Azure**: Use HIPAA/HITRUST compliance offerings. Enable Defender for Cloud.
- **GCP**: Use HIPAA-covered services. Enable Cloud Audit Logs, CMEK encryption.

---

## PCI-DSS (Payment Card Industry Data Security Standard)

Applies to: Any system that stores, processes, or transmits cardholder data (CHD) or sensitive authentication data (SAD).

### Scope Reduction Strategy

**Best approach**: Never touch card data directly.
```
Can you use a third-party payment processor?
├── Yes → Use Stripe/Braintree/Adyen hosted payment fields
│         (PCI scope reduced to SAQ-A or SAQ A-EP)
└── No  → Full PCI-DSS compliance required (SAQ-D)
          (Significant infrastructure and process requirements)
```

### Requirements Checklist (v4.0)

#### Requirement 1: Network Security Controls
- [ ] Firewall/security groups restrict all inbound/outbound traffic to necessary ports
- [ ] Cardholder data environment (CDE) isolated in a separate network segment
- [ ] DMZ between public internet and CDE
- [ ] No direct public access to database servers storing CHD

#### Requirement 2: Secure Configuration
- [ ] Default passwords changed on all systems
- [ ] Unnecessary services, ports, protocols disabled
- [ ] System hardening standards documented and applied
- [ ] Only one primary function per server (or container)

#### Requirement 3: Protect Stored Account Data
- [ ] PAN stored only when business need justified
- [ ] PAN encrypted at rest (AES-256 or equivalent)
- [ ] Encryption key management procedures documented
- [ ] PAN masked when displayed (show only last 4 digits)
- [ ] Sensitive authentication data (CVV, PIN) NEVER stored after authorization
- [ ] Retention policy — delete CHD when no longer needed

#### Requirement 4: Encrypt Transmission
- [ ] TLS 1.2+ for all transmission of CHD over public networks
- [ ] Strong cryptography for all CHD transmission over internal networks
- [ ] Certificate validation enforced

#### Requirement 5: Malware Protection
- [ ] Anti-malware on all systems in CDE (or compensating controls for Linux)
- [ ] Container image scanning in CI/CD pipeline

#### Requirement 6: Secure Development
- [ ] Secure coding training for developers
- [ ] Code review or SAST for all custom code before production
- [ ] OWASP Top 10 addressed in development standards
- [ ] Change control procedures for all CDE changes
- [ ] Vulnerability scanning of web applications (DAST)
- [ ] Web Application Firewall (WAF) deployed for public-facing web apps

#### Requirement 7: Restrict Access
- [ ] Access to CHD on a need-to-know basis
- [ ] Default deny access control system
- [ ] Access rights reviewed at least every 6 months

#### Requirement 8: Identify Users
- [ ] Unique user ID for each person with access
- [ ] Multi-factor authentication for all access to CDE
- [ ] Password requirements (12+ characters, complexity, rotation)
- [ ] Account lockout after 10 failed attempts
- [ ] Session timeout after 15 minutes of inactivity

#### Requirement 9: Physical Access (Cloud)
- [ ] Cloud provider PCI-DSS compliance certificate verified
- [ ] Shared responsibility matrix documented

#### Requirement 10: Logging and Monitoring
- [ ] All access to CHD logged with user ID, timestamp, action, success/failure
- [ ] Log integrity protection (immutable storage)
- [ ] Log retention: at least 12 months (3 months immediately accessible)
- [ ] Daily log review (automated alerting acceptable)
- [ ] Time synchronization (NTP) on all systems

#### Requirement 11: Security Testing
- [ ] Quarterly vulnerability scans (ASV for external, internal scans)
- [ ] Annual penetration test
- [ ] File integrity monitoring on critical system files
- [ ] IDS/IPS monitoring network traffic to CDE

#### Requirement 12: Security Policies
- [ ] Information security policy documented and reviewed annually
- [ ] Incident response plan tested annually
- [ ] Third-party service providers have signed responsibility acknowledgements

---

## SOC 2 (Service Organization Control 2)

Applies to: SaaS companies and service providers handling customer data. Based on Trust Services Criteria.

### Trust Services Criteria

#### Security (Common Criteria — required for all SOC 2)
- [ ] Access control policies defined and enforced
- [ ] MFA enabled for all employee access to production systems
- [ ] Encryption at rest and in transit
- [ ] Firewall and network segmentation
- [ ] Vulnerability management program (regular scanning + patching)
- [ ] Security awareness training for all employees
- [ ] Incident response procedure documented and tested
- [ ] Change management process for production changes
- [ ] Vendor risk management (security reviews of third parties)
- [ ] Background checks for employees with production access

#### Availability
- [ ] SLA/uptime targets defined (99.9%, 99.95%, etc.)
- [ ] Monitoring and alerting for system availability
- [ ] Backup and disaster recovery plan documented and tested
- [ ] Capacity planning process (auto-scaling or manual review)
- [ ] Incident communication process (status page, customer notifications)
- [ ] RTO (Recovery Time Objective) and RPO (Recovery Point Objective) defined

#### Processing Integrity
- [ ] Data processing accuracy validated (input validation, output verification)
- [ ] Error handling and correction procedures documented
- [ ] Data quality monitoring
- [ ] Processing completeness checks (batch processing reconciliation)

#### Confidentiality
- [ ] Data classification scheme (public, internal, confidential, restricted)
- [ ] Confidential data identified and encrypted
- [ ] Data retention and destruction policies
- [ ] NDA/confidentiality agreements with employees and contractors
- [ ] Data access logged and monitored

#### Privacy
- [ ] Privacy notice/policy published
- [ ] Consent management for personal data collection
- [ ] Data subject rights supported (access, deletion, portability)
- [ ] Data Processing Agreements with sub-processors
- [ ] Privacy impact assessments for new features
- [ ] Data breach notification procedures

### Engineering Controls for SOC 2

| Control | Implementation |
|---|---|
| Access reviews | Quarterly access reviews with evidence (spreadsheet or tool export) |
| Change management | All prod changes via PR with approval. Git history is the audit trail. |
| Vulnerability management | Dependabot/Snyk + quarterly scans. Track findings to resolution. |
| Backup testing | Monthly restore test with documented results |
| Incident response | PagerDuty/Opsgenie + runbooks. Post-incident reviews documented. |
| Logging | Centralized logging (DataDog, Splunk, ELK) with 12-month retention |
| Encryption | TLS 1.2+ everywhere. AES-256 at rest. KMS-managed keys. |
| MFA | Enforced via SSO provider (Okta, Azure AD, Google Workspace) |

---

## Compliance in Code: Automation Patterns

### Data Retention Automation
```
# Pseudocode — scheduled job
for each data_category in retention_policy:
    records = find_records_older_than(category.retention_period)
    if category.action == "delete":
        hard_delete(records)
    elif category.action == "anonymize":
        anonymize_pii_fields(records)
    log_retention_action(category, count=len(records))
```

### Right to Erasure (GDPR Article 17)
```
# Pseudocode — user deletion endpoint
function delete_user_data(user_id):
    # 1. Delete or anonymize in primary database
    anonymize_user(user_id)  # Replace PII with "[DELETED]"
    
    # 2. Delete from search indexes
    remove_from_search_index(user_id)
    
    # 3. Delete from analytics/data warehouse
    queue_analytics_deletion(user_id)
    
    # 4. Delete from backups (or document retention exception)
    document_backup_retention_exception(user_id)
    
    # 5. Notify third-party processors
    notify_processors_of_deletion(user_id)
    
    # 6. Log the erasure
    audit_log("USER_ERASURE", user_id, timestamp=now())
```

### Audit Logging Schema
```sql
CREATE TABLE audit_log (
    id            BIGSERIAL PRIMARY KEY,
    timestamp     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    actor_id      VARCHAR(255) NOT NULL,
    actor_type    VARCHAR(50) NOT NULL,    -- 'user', 'system', 'api_key'
    action        VARCHAR(100) NOT NULL,   -- 'read', 'create', 'update', 'delete'
    resource_type VARCHAR(100) NOT NULL,   -- 'employee', 'payment', 'report'
    resource_id   VARCHAR(255) NOT NULL,
    changes       JSONB,                   -- { "field": {"old": "...", "new": "..."} }
    ip_address    INET,
    user_agent    TEXT,
    status        VARCHAR(20) NOT NULL     -- 'success', 'failure', 'denied'
);

-- Index for compliance queries
CREATE INDEX idx_audit_actor ON audit_log (actor_id, timestamp);
CREATE INDEX idx_audit_resource ON audit_log (resource_type, resource_id, timestamp);
CREATE INDEX idx_audit_action ON audit_log (action, timestamp);
```

### Encryption at Rest — Field-Level Pattern
```
# When full-database encryption isn't sufficient (multi-tenant, need per-tenant keys)
function encrypt_pii(plaintext, tenant_id):
    key = kms.get_data_key(tenant_key_id=tenant_id)
    ciphertext = aes_256_gcm_encrypt(plaintext, key)
    return base64_encode(ciphertext)

function decrypt_pii(ciphertext, tenant_id):
    key = kms.get_data_key(tenant_key_id=tenant_id)
    plaintext = aes_256_gcm_decrypt(base64_decode(ciphertext), key)
    return plaintext
```

---

## Quick Reference: Which Framework Requires What

| Requirement | GDPR | HIPAA | PCI-DSS | SOC 2 |
|---|---|---|---|---|
| Encryption at rest | Required for PII | Required for ePHI | Required for CHD | Required |
| Encryption in transit | Required | Required | Required (TLS 1.2+) | Required |
| Access control (RBAC) | Required | Required (min. necessary) | Required (need-to-know) | Required |
| Audit logging | Required for PII access | Required (6yr retention) | Required (12mo retention) | Required |
| MFA | Recommended | Required for remote access | Required for CDE access | Required |
| Breach notification | 72 hours to authority | 60 days to individuals | Immediate to processor | Per contract |
| Data retention policy | Required | 6 years for records | Per business need | Required |
| Vulnerability scanning | Recommended | Required | Quarterly (ASV external) | Required |
| Penetration testing | Recommended | Recommended | Annual | Required |
| Right to deletion | Required | Not required (6yr retention) | Not applicable | If privacy criteria |
| Data portability | Required (machine-readable) | Not required | Not applicable | If privacy criteria |
| Employee training | Required | Required | Required | Required |
| Incident response plan | Required | Required | Required | Required |
| Third-party agreements | DPA required | BAA required | Responsibility ack. | Vendor review |
