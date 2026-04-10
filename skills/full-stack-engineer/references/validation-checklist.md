# Validation Checklist

Run through this checklist after implementation, before marking the task complete.

## Code Quality

- [ ] Code compiles/interprets without errors
- [ ] Linter passes with zero warnings (or justified suppressions)
- [ ] No TODO/FIXME left without a tracking issue
- [ ] Functions and classes follow single responsibility principle
- [ ] No dead code or commented-out blocks
- [ ] Naming is consistent with project conventions

## Testing

- [ ] Unit tests cover critical business logic paths
- [ ] Integration tests verify API endpoints return correct responses
- [ ] Database migration tests: `up` and `down` both work cleanly
- [ ] Edge cases tested: empty input, null values, boundary values, max lengths
- [ ] Concurrent access scenarios tested where applicable
- [ ] All tests pass in CI-equivalent environment (clean state, no local dependencies)

## Database

- [ ] All migrations run cleanly on a fresh database
- [ ] Seed data works for development and test environments
- [ ] Critical queries have been EXPLAIN'd — no full table scans on large tables
- [ ] N+1 query problems checked and resolved (eager loading where needed)
- [ ] Indexes exist for all WHERE, JOIN, and ORDER BY columns in frequent queries
- [ ] Foreign key constraints enforced at database level
- [ ] Nullable columns are intentional and documented

## Security

- [ ] No SQL injection vectors (all queries parameterized)
- [ ] No XSS vectors (all user output encoded)
- [ ] CSRF tokens on all state-changing forms/endpoints
- [ ] Authentication required on all protected routes
- [ ] Authorization checked (role/permission/ownership) before data access
- [ ] No secrets in source code, logs, or error responses
- [ ] Dependency vulnerability scan passes (npm audit, composer audit, etc.)
- [ ] File uploads validated (type, size, content) if applicable
- [ ] Rate limiting configured on auth and public endpoints

## Performance

- [ ] Response times acceptable for the use case (< 200ms for API, < 1s for page loads)
- [ ] No unnecessary database queries (check for duplicates, unused eager loads)
- [ ] Large result sets are paginated
- [ ] Static assets are cacheable (appropriate cache headers)
- [ ] Background jobs used for operations > 500ms that don't need synchronous response

## Infrastructure

- [ ] Health check endpoint returns correct status
- [ ] Environment variables documented and have sensible defaults
- [ ] Docker/container builds are reproducible (pinned versions)
- [ ] Logging captures request IDs for traceability
- [ ] Error monitoring captures unhandled exceptions
- [ ] Graceful shutdown handled (drain connections, finish in-flight requests)

## Documentation

- [ ] API endpoints documented (request/response, auth, errors)
- [ ] Database schema changes documented in migration files
- [ ] Environment variables listed with descriptions
- [ ] Architecture decisions documented with rationale
- [ ] Known limitations or trade-offs noted
