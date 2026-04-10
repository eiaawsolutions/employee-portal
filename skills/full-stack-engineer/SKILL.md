---
name: full-stack-engineer
description: "Full-stack engineering skill: architect, design, and implement complete solutions spanning application code (any language/framework), infrastructure (cloud/on-prem), databases (SQL/NoSQL), mobile apps (React Native/Flutter/Swift/Kotlin), DevOps/IaC (Docker/K8s/Terraform/CI-CD), and cybersecurity expertise. Use when: building new features end-to-end, designing system architecture, creating database schemas, planning infrastructure, scaffolding projects, reviewing technical designs, optimizing performance, migrating systems, threat modeling, vulnerability assessment, security hardening, mobile app development, DevOps pipeline setup, or compliance implementation (GDPR/HIPAA/PCI-DSS/SOC2). Covers coding, infra architecture, database design, security, mobile, DevOps, compliance, and legal regulatory compliance across industry, global, and country-specific regulations with continuous monitoring. Includes penetration testing readiness — builds systems that pass pentests by addressing all common findings proactively.Ai integration expert, skilled in leveraging AI tools and APIs to enhance software development, automate tasks, and build intelligent features. Proficient in integrating language models (like GPT) for code generation, documentation, testing, and user interactions. Use when: automating code scaffolding, generating documentation, creating intelligent chatbots, implementing AI-driven features, optimizing workflows with AI, or enhancing user experience with natural language processing."
argument-hint: "Describe the feature, system, or problem to solve end-to-end"
---

# Full-Stack Engineer

End-to-end delivery skill combining software engineering, infrastructure architecture, and database design. Produces working implementations from requirements through validated, deployed code.

## When to Use

- Building a new feature, service, or system from scratch
- Designing or refactoring system architecture
- Creating or migrating database schemas
- Planning infrastructure for a project
- Scaffolding a new project with full stack decisions
- Reviewing or optimizing an existing technical design
- Performing a technology migration or upgrade
- Building mobile applications (native or cross-platform)
- Setting up DevOps pipelines, Docker, Kubernetes, or IaC
- Implementing compliance requirements (GDPR, HIPAA, PCI-DSS, SOC 2)
- Legal regulatory compliance across industry, global, and country-specific regulations
- Continuous compliance monitoring and regulatory change management
- Penetration testing readiness and pre-pentest hardening

## Procedure

Follow these phases sequentially. Each phase has a quality gate that must pass before proceeding.

---

### Phase 1: Requirements Analysis

**Goal**: Understand what needs to be built, the constraints, and the success criteria.

1. **Gather context**
   - Read existing codebase structure, configs, and READMEs
   - Identify the tech stack already in use (languages, frameworks, cloud provider, DB engines)
   - Check for existing patterns (naming conventions, project layout, testing approach)

2. **Clarify requirements**
   - Functional: What does the system need to do?
   - Non-functional: Performance targets, availability, scalability, security, compliance
   - Constraints: Budget, team expertise, timeline, existing infrastructure
   - Integration points: APIs, third-party services, existing systems
   - Platform targets: Web, mobile (iOS/Android), desktop, API-only
   - Compliance: Identify applicable frameworks — refer to [compliance.md](./references/compliance.md) for framework selection guide
   - Legal: Map regulatory surface (geography × industry × data type) — refer to [legal-compliance.md](./references/legal-compliance.md) for the full regulatory discovery process and jurisdiction-specific regulations
   - Build regulatory register listing every applicable law with enforcement dates and owners

3. **Define deliverables**
   - List the concrete artifacts this task will produce (code, schemas, configs, diagrams)

**Quality Gate**: Requirements are specific enough to make technology and design decisions. If ambiguous, ask the user before proceeding.

---

### Phase 2: Architecture Design

**Goal**: Make high-level technical decisions and define the system structure.

#### 2A: Application Architecture

1. **Select patterns** appropriate to the problem:
   - Monolith vs microservices vs serverless
   - MVC / CQRS / Event-driven / Hexagonal / Clean Architecture
   - Sync vs async communication (REST, gRPC, message queues, WebSockets)

2. **Define component boundaries**
   - Draw module/service boundaries with clear responsibilities
   - Identify API contracts between components
   - Plan error handling and resilience strategies (retries, circuit breakers, fallbacks)

3. **Choose technology stack** (if not already determined):
   - Language/framework selection based on requirements and team constraints — refer to [language-patterns.md](./references/language-patterns.md) for idiomatic patterns per stack
   - For mobile projects, choose native vs cross-platform — refer to [mobile-development.md](./references/mobile-development.md) for decision tree and platform patterns
   - Dependency choices (libraries, SDKs) — prefer well-maintained, minimal dependencies
   - For cloud infrastructure, refer to [cloud-providers.md](./references/cloud-providers.md) for service mapping across AWS/Azure/GCP

#### 2B: Infrastructure Architecture

1. **Compute & networking**
   - Hosting model: containers (Docker/K8s), VMs, serverless, PaaS
   - Load balancing, CDN, DNS strategy
   - Network topology: VPC, subnets, security groups, firewalls

2. **Storage & data**
   - Object storage, block storage, file systems
   - Caching layers (Redis, Memcached, application-level)
   - Backup and disaster recovery strategy

3. **Observability**
   - Logging (structured logs, aggregation)
   - Metrics and monitoring (APM, health checks, alerting)
   - Tracing (distributed tracing for multi-service systems)

4. **Security**
   - Authentication & authorization model
   - Secrets management (vault, env vars, managed secrets)
   - Network security (TLS, WAF, rate limiting)
   - OWASP Top 10 coverage

5. **CI/CD pipeline**
   - Build → test → lint → security scan → deploy stages
   - Environment strategy (dev, staging, production)
   - Rollback and blue-green/canary deployment approach

#### 2C: Database Design

1. **Choose engine(s)** based on data characteristics:

   | Data Shape | Consider |
   |---|---|
   | Relational, transactional, complex queries | PostgreSQL, MySQL, SQL Server, Oracle |
   | Document/flexible schema | MongoDB, CouchDB, DynamoDB |
   | Key-value / caching | Redis, Memcached, DynamoDB |
   | Time-series | TimescaleDB, InfluxDB, QuestDB |
   | Graph relationships | Neo4j, Neptune, ArangoDB |
   | Search / full-text | Elasticsearch, OpenSearch, Meilisearch |
   | Wide-column / massive scale | Cassandra, ScyllaDB, Bigtable |

2. **Design the schema**
   - Normalize to 3NF first, then denormalize intentionally for performance
   - Define primary keys, foreign keys, unique constraints
   - Plan indexes based on known query patterns (avoid premature indexing)
   - Consider partitioning strategy for large tables

3. **Plan data access patterns**
   - Map out the top 10-20 most frequent queries
   - ORM vs raw SQL decision per query type
   - Read/write ratio — consider read replicas if read-heavy
   - Connection pooling strategy

4. **Migration strategy**
   - Forward-only migrations with rollback scripts
   - Zero-downtime migration approach for production changes
   - Seed data for development/testing environments

#### 2D: Architecture Diagrams (Mermaid)

Generate Mermaid diagrams for every architecture design. These serve as living documentation.

1. **System context diagram** — show the system and its external actors/dependencies
   ```mermaid
   graph LR
       User([User]) --> App[Application]
       App --> DB[(Database)]
       App --> ExtAPI[External API]
       Admin([Admin]) --> App
   ```

2. **Component diagram** — show internal modules/services and their relationships
   ```mermaid
   graph TD
       subgraph API Layer
           Controller[Controllers]
       end
       subgraph Business Logic
           Service[Services]
           Events[Event Handlers]
       end
       subgraph Data Layer
           Repo[Repositories]
           Cache[Cache]
       end
       Controller --> Service
       Service --> Events
       Service --> Repo
       Repo --> Cache
   ```

3. **Data flow / sequence diagram** — for critical user flows
   ```mermaid
   sequenceDiagram
       participant U as User
       participant A as API
       participant S as Service
       participant D as Database
       U->>A: POST /resource
       A->>S: validate + process
       S->>D: INSERT
       D-->>S: OK
       S-->>A: Created
       A-->>U: 201 + resource
   ```

4. **Infrastructure diagram** — show cloud resources, networking, and data flow
   ```mermaid
   graph TD
       Internet([Internet]) --> LB[Load Balancer]
       LB --> App1[App Server 1]
       LB --> App2[App Server 2]
       App1 --> DB[(Primary DB)]
       App2 --> DB
       DB --> Replica[(Read Replica)]
       App1 --> Cache[(Redis Cache)]
       App2 --> Cache
   ```

5. **Entity-relationship diagram** — for database schema
   ```mermaid
   erDiagram
       USER ||--o{ ORDER : places
       ORDER ||--|{ LINE_ITEM : contains
       PRODUCT ||--o{ LINE_ITEM : "ordered in"
   ```

**Diagram rules:**
- Generate at least diagrams 1, 2, and 5 for every project
- Add sequence diagrams (3) for every flow involving 3+ components
- Add infrastructure diagrams (4) when cloud/infra work is in scope
- Keep diagrams focused — split complex systems into multiple diagrams
- Use consistent naming between diagrams and code

#### 2E: Security Architecture (Threat Model)

Perform threat modeling during architecture design, before implementation. Refer to [cybersecurity.md](./references/cybersecurity.md) for the full procedure.

1. **Identify trust boundaries** — mark every point where data crosses a trust level
2. **Run STRIDE analysis** — assess each component for Spoofing, Tampering, Repudiation, Information Disclosure, DoS, and Elevation of Privilege
3. **Map mitigations** — for each identified threat, define the mitigation that will be implemented
4. **Document security architecture decisions** — auth model, encryption strategy, secrets management, audit logging

**Quality Gate**: Architecture decisions are documented with rationale. Each decision answers "why this choice over alternatives." Threat model completed. Refer to [architecture-checklist.md](./references/architecture-checklist.md) for validation.

---

### Phase 3: Implementation

**Goal**: Build the solution incrementally with quality built in at every step.

1. **Plan implementation order**
   - Database schema and migrations first
   - Core domain models and business logic second
   - API layer / controllers third
   - Infrastructure configs fourth
   - Frontend / UI last (if applicable)

2. **For each component, follow this loop:**
   ```
   a. Write the migration / schema change
   b. Write the model / entity
   c. Write the business logic
   d. Write the API endpoint / controller
   e. Write tests (unit + integration)
   f. Validate against requirements
   ```

3. **Coding standards** (apply to any language):
   - Follow existing project conventions first; only introduce new patterns with justification
   - Single responsibility per function/class/module
   - Meaningful names — code should read like prose
   - Handle errors at system boundaries; don't over-defend internal code
   - No magic numbers; use named constants or configuration
   - Keep functions short (< 30 lines preferred)
   - Prefer composition over inheritance

4. **Security implementation** (non-negotiable) — see [cybersecurity.md](./references/cybersecurity.md) for full OWASP checklist:
   - Parameterized queries / prepared statements for all DB access
   - Input validation and sanitization at system boundaries
   - Output encoding (XSS prevention)
   - CSRF protection on state-changing endpoints
   - Authentication checks on every protected route
   - Least-privilege for service accounts and DB users
   - No secrets in code or version control
   - Security headers configured (CSP, HSTS, X-Frame-Options, X-Content-Type-Options)
   - Rate limiting on authentication and public endpoints
   - File upload validation (type, size, content) if applicable
   - SSRF protection — validate all outbound URLs against allow-lists
   - Dependency vulnerability scan passes before merge

5. **Infrastructure as Code** (when applicable) — refer to [devops-iac.md](./references/devops-iac.md) for templates:
   - Define all infrastructure in code (Terraform, Pulumi, CloudFormation, Docker Compose)
   - Environment-specific configuration via variables, not file duplication
   - Idempotent deployments
   - Use multi-stage Docker builds (see templates in reference)
   - CI/CD pipeline with build → test → lint → security scan → deploy stages

6. **Compliance implementation** (when applicable) — refer to [compliance.md](./references/compliance.md):
   - Implement data subject rights endpoints (GDPR: export, delete, rectify)
   - Audit logging for sensitive data access
   - Data retention automation (scheduled cleanup jobs)
   - Field-level encryption for regulated data (PHI, CHD, PII)
   - Consent management for data collection

7. **Pentest-ready hardening** (apply to every build) — refer to [penetration-testing.md](./references/penetration-testing.md):
   - Information disclosure eliminated (no version headers, stack traces, debug pages, source maps)
   - Authentication hardened (no user enumeration, account lockout, session regeneration)
   - Authorization verified at every endpoint (IDOR prevention, horizontal/vertical escalation blocked)
   - All 12 pre-pentest checklist categories passed (see reference)
   - Security headers set on all responses (HSTS, CSP, X-Frame-Options, etc.)
   - Business logic abuse cases tested (race conditions, negative values, workflow bypass)

**Quality Gate**: All code compiles/lints cleanly. Tests pass. No security warnings from static analysis.

---

### Phase 4: Validation

**Goal**: Verify the solution meets requirements, is performant, and is production-ready.

1. **Test coverage**
   - Unit tests for business logic (aim for critical paths, not 100% coverage theater)
   - Integration tests for API endpoints and database queries
   - Migration tests (up and down)
   - Edge cases: empty inputs, boundary values, concurrent access

2. **Performance validation**
   - Query execution plans for critical database queries (EXPLAIN ANALYZE)
   - Identify N+1 query problems
   - Load testing for endpoints with expected traffic patterns
   - Memory and resource usage profiling

3. **Security review** — run the full checklist from [cybersecurity.md](./references/cybersecurity.md):
   - OWASP Top 10 checklist pass (all 10 categories)
   - SAST scan (Semgrep, SonarQube, or CodeQL)
   - DAST scan for web applications (OWASP ZAP)
   - Dependency vulnerability scan (`npm audit`, `composer audit`, `pip audit`, `cargo audit`)
   - Container image scan (Trivy, Grype) if using containers
   - Authentication/authorization flow verification
   - Sensitive data exposure check (logs, error messages, API responses)
   - Security headers validation
   - CORS configuration review
   - API security checklist pass

   **Pentest readiness validation** — run the full pre-pentest checklist from [penetration-testing.md](./references/penetration-testing.md):
   - All 12 hardening categories verified (information disclosure, auth, authz, injection, XSS, CSRF, headers, file upload, crypto, business logic, API, infrastructure)
   - Automated scan suite executed (SAST + DAST + dependency + container + secrets)
   - Security regression test suite passes (auth, injection, headers, CSRF, info disclosure tests)
   - Common pentest findings cross-referenced — no critical/high patterns present
   - Secret scanning confirms no credentials in codebase

4. **Database validation**
   - Verify indexes support all critical query patterns
   - Check constraint integrity (FK, unique, check constraints)
   - Test migration rollback
   - Verify seed data works for development

5. **Infrastructure validation**
   - Health check endpoints respond correctly
   - Logging captures expected events
   - Monitoring alerts are configured
   - Backup and restore process works

6. **Legal & regulatory compliance validation** — refer to [legal-compliance.md](./references/legal-compliance.md):
   - Regulatory register is complete and reviewed by legal counsel
   - Data subject rights endpoints tested (export, delete, rectify, opt-out)
   - Consent flows verified per jurisdiction (pre-processing consent where required)
   - Data residency requirements satisfied (data stored in correct regions)
   - Accessibility compliance checked (WCAG 2.1 AA — axe-core/pa11y in CI)
   - Privacy policy, terms of service, and cookie consent cover all processing activities
   - DPAs/BAAs in place with all third-party processors
   - Compliance evidence collection is automated (audit logs, access reviews, consent records)
   - Breach notification process documented and tested

**Quality Gate**: All tests green. Performance meets targets. Security checklist passed. Refer to [validation-checklist.md](./references/validation-checklist.md).

---

### Phase 5: Delivery Summary

After implementation and validation:

1. **Document what was built**
   - List all files created or modified
   - Summarize architecture decisions made and why
   - Note any trade-offs or technical debt intentionally accepted

2. **Highlight follow-ups**
   - Known limitations or edge cases not yet covered
   - Future optimizations to consider under load
   - Monitoring thresholds to tune after production data is available

3. **Provide runbook** (for infrastructure work)
   - How to deploy
   - How to rollback
   - How to troubleshoot common failures

4. **Compliance maintenance plan**
   - Scheduled compliance review cadence (weekly regulatory monitoring, quarterly audits, annual full review)
   - Regulatory change monitoring sources and impact assessment process
   - Data subject rights request SLA and escalation path
   - Evidence collection and retention schedule for audits
   - Next compliance review date and responsible owner

5. **Pentest readiness status**
   - Pre-pentest hardening checklist status (all 12 sections)
   - Automated scan results summary (SAST, DAST, dependency, container, secrets)
   - Known accepted risks with documented rationale
   - Recommended pentest type and scope for external engagement
   - Security regression test suite location and coverage

---

## Decision Trees

### Choosing a Database Engine

```
Is the data highly relational with complex joins?
├── Yes → Is ACID compliance critical?
│   ├── Yes → PostgreSQL (default) or MySQL/SQL Server
│   └── No  → Consider denormalized relational or document store
└── No  → What's the primary access pattern?
    ├── Key-value lookups → Redis / DynamoDB
    ├── Full-text search → Elasticsearch / Meilisearch
    ├── Time-series data → TimescaleDB / InfluxDB
    ├── Graph traversals → Neo4j / Neptune
    └── Flexible schema, document-oriented → MongoDB / CouchDB
```

### Choosing a Compute Model

```
What's the traffic pattern?
├── Steady, predictable → Containers (ECS/K8s) or VMs
├── Spiky / event-driven → Serverless (Lambda/Functions/Cloud Run)
├── Long-running processes → Containers or VMs
└── Batch processing → Serverless or managed batch (AWS Batch, Cloud Dataflow)
```

### Monolith vs Microservices

```
Team size?
├── 1-5 developers → Monolith (modular)
├── 5-15 developers → Modular monolith or bounded microservices
└── 15+ developers → Microservices with clear domain boundaries

Override: If the project is a prototype/MVP → Always monolith first
```

## Language-Agnostic Patterns

When working in any language, apply these universal principles:

| Principle | Application |
|---|---|
| Separation of concerns | Keep data access, business logic, and presentation in distinct layers |
| Dependency injection | Accept dependencies rather than creating them internally |
| Configuration externalization | Env vars or config files, never hardcoded values |
| Idempotent operations | Safe to retry without side effects (especially for APIs and migrations) |
| Graceful degradation | System continues to function (possibly limited) when a dependency fails |
| Principle of least surprise | APIs and interfaces behave as callers expect |

## Reference Files

| Reference | Purpose |
|---|---|
| [architecture-checklist.md](./references/architecture-checklist.md) | Pre-implementation architecture validation |
| [validation-checklist.md](./references/validation-checklist.md) | Post-implementation quality validation |
| [cloud-providers.md](./references/cloud-providers.md) | AWS / Azure / GCP service mapping |
| [language-patterns.md](./references/language-patterns.md) | Idiomatic patterns for PHP, JS/TS, Python, Go, Java, C#, Rust |
| [cybersecurity.md](./references/cybersecurity.md) | STRIDE threat modeling, OWASP Top 10 checklist, secure coding patterns |
| [mobile-development.md](./references/mobile-development.md) | React Native, Flutter, Swift (iOS), Kotlin (Android) patterns and security |
| [devops-iac.md](./references/devops-iac.md) | Docker, Docker Compose, Kubernetes, Terraform, GitHub Actions, GitLab CI templates |
| [compliance.md](./references/compliance.md) | GDPR, HIPAA, PCI-DSS, SOC 2 checklists and automation patterns |
| [legal-compliance.md](./references/legal-compliance.md) | Regulatory discovery, jurisdiction mapping, continuous compliance monitoring, industry blueprints, accessibility, evidence collection |
| [penetration-testing.md](./references/penetration-testing.md) | Pre-pentest hardening (12 categories), common findings & fixes, automated scan commands, pentest-ready test suite, remediation SLAs |
