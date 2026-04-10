# Architecture Checklist

Validate all architecture decisions before moving to implementation.

## Application Architecture

- [ ] Component boundaries are defined with clear responsibilities
- [ ] Communication patterns chosen (sync/async) with justification
- [ ] API contracts documented (request/response shapes, status codes, error formats)
- [ ] Authentication and authorization model defined
- [ ] Error handling strategy is consistent across components
- [ ] Logging strategy defined (what to log, structured format, PII handling)
- [ ] Tech stack choices justified against requirements and constraints

## Infrastructure Architecture

- [ ] Compute model matches traffic pattern (steady → containers, spiky → serverless)
- [ ] Network topology defined (VPC, subnets, public/private separation)
- [ ] TLS configured for all external-facing endpoints
- [ ] Secrets management approach defined (no plaintext secrets in code/configs)
- [ ] Backup strategy defined with RPO and RTO targets
- [ ] Health check and monitoring endpoints planned
- [ ] CI/CD pipeline stages defined (build, test, lint, security scan, deploy)
- [ ] Environment parity strategy (dev ≈ staging ≈ production)
- [ ] Rollback mechanism defined (blue-green, canary, or automated rollback)

## Database Architecture

- [ ] Engine choice justified by data characteristics and access patterns
- [ ] Schema normalized to 3NF with intentional denormalization documented
- [ ] Primary keys use appropriate types (UUID vs auto-increment vs natural)
- [ ] Foreign keys and referential integrity constraints defined
- [ ] Indexes planned for known query patterns (not speculative)
- [ ] Connection pooling configured appropriately
- [ ] Read/write splitting considered if read-heavy
- [ ] Partitioning strategy defined for tables expected to exceed 10M+ rows
- [ ] Migration strategy supports zero-downtime deployments
- [ ] Soft delete vs hard delete decision made per entity

## Security Architecture

- [ ] OWASP Top 10 mitigations mapped to implementation
- [ ] Input validation at all system boundaries
- [ ] SQL injection prevention (parameterized queries everywhere)
- [ ] XSS prevention (output encoding)
- [ ] CSRF protection on state-changing endpoints
- [ ] Rate limiting on authentication and public endpoints
- [ ] Least-privilege principle applied to all service accounts
- [ ] Sensitive data classified and encryption strategy defined (at rest + in transit)
- [ ] Audit logging for security-relevant events

## Scalability Considerations

- [ ] Identified bottleneck: the first thing that will break under 10x load
- [ ] Horizontal scaling path defined (stateless services, shared-nothing)
- [ ] Caching strategy defined (what to cache, TTL, invalidation)
- [ ] Async processing planned for expensive operations (queues, background jobs)
- [ ] Database connection limits accounted for under peak load
