# EIAAW Workforce — Observability Runbook

**Scope:** production monitoring, alerting, and on-call response. Covers what the
platform surfaces today, what's wired but needs Amos-side activation, and the
two minimum alerting rules required before launch.

**Last updated:** 2026-04-25

---

## 1. What's wired today (code-side, zero Amos-side work needed)

| Surface | Source | Retention |
|---|---|---|
| Structured app logs | `storage/logs/laravel.log` (rotated by Laravel) | 7 days on Railway default |
| Billing command logs | `storage/logs/billing.log` | Appended indefinitely — rotate via logrotate |
| Payroll statutory drift | `storage/logs/payroll-statutory.log` (weekly Monday 04:00) | Appended |
| Backup verify logs | `storage/logs/backup-verify.log` (weekly Monday 05:00) | Appended |
| Log integrity run | `storage/logs/integrity-check.log` (daily 03:00) | Appended |
| Backup run logs | `storage/logs/backup.log` | Appended |
| `security_audit_logs` table | Every auth, 403, admin action, SSO login | 2 years (by design) |
| `ai_conversations` table | Every AI prompt + answer + cost | 2 years |
| `ai_usage_daily` table | Daily AI cost aggregate per tenant | Indefinite |
| `subscription_events` table | Every Stripe webhook with idempotency key | Indefinite |
| CSP violations | `storage/logs/laravel.log` via `/csp-report` endpoint | 7 days |
| HTTP health check | `GET /up` — the standard Laravel health endpoint | live |

## 2. Sentry integration (Amos-side activation required)

Sentry is the recommended error-aggregation layer. Not wired yet; flagged as
Session 12 item I.

**Activation steps (~10 min):**

1. Create project at sentry.io (PHP / Laravel). Free tier supports ~5,000 events/mo — fine for soft launch.
2. `composer require sentry/sentry-laravel` on production.
3. Add to production env:
   ```
   SENTRY_LARAVEL_DSN=https://<key>@<org>.ingest.sentry.io/<project>
   SENTRY_TRACES_SAMPLE_RATE=0.1       # 10% APM sampling, covers latency
   SENTRY_PROFILES_SAMPLE_RATE=0.1     # optional profiling
   SENTRY_ENVIRONMENT=production
   SENTRY_RELEASE=${RAILWAY_GIT_COMMIT_SHA:-unknown}
   ```
4. In `bootstrap/app.php`'s `withExceptions()` block, add:
   ```php
   $exceptions->report(function (\Throwable $e) {
       if (app()->bound('sentry') && !$e instanceof \Illuminate\Validation\ValidationException) {
           app('sentry')->captureException($e);
       }
   });
   ```
5. Deploy. Verify by deliberately throwing in a non-production route and checking Sentry receives it.

**Do NOT send** to Sentry:
- `ValidationException` (user error, 400-class)
- `HttpException` with 401/403/404 (expected handling)
- Events from tenant-specific PII (the default Sentry PII scrubber is off; enable `send_default_pii: false` in `config/sentry.php`)

**Cost expectation:** free tier for first 5K events/mo. A well-behaved production app throws 50-500 events/mo; exceed that means there's a real bug.

## 3. Uptime monitoring

The `GET /up` endpoint is already live (Laravel default health check, returns 200 when the app boots). Point an external uptime monitor at it:

- **Option A (free):** UptimeRobot pinging `https://ep.eiaawsolutions.com/up` every 5 min. Free tier = 50 monitors.
- **Option B (paid):** Railway's built-in uptime check ($5/mo) — more granular, integrates with Railway alerting.

**Target SLO:** 99.5% monthly for Starter/Growth/Scale, 99.9% for Enterprise (contractual per Terms).

Uptime dashboard lives at wherever Amos chooses; link from the internal runbook.

## 4. Minimum alerting rules before launch

These are the **two** non-negotiable alerts. Everything else is a nice-to-have that can wait until there's a first real incident to tune against.

### Rule 1 — App 5xx rate spike

**Trigger:** 5xx responses exceed 1% of all responses over a 5-minute window.

**Implementation:**
- If Sentry is wired: configure a Metric Alert → "transaction.http.status_code >= 500, rate > 1% over 5m"
- If not: scrape Railway's request metrics via the Railway API, run a cron check every 5 min

**Response:**
1. Check Sentry / Railway logs for the top error signature
2. If it's a specific controller, roll back the latest deploy (`railway rollback`)
3. If it's a DB connection storm, check Postgres connection pool saturation
4. If it's the AI gateway (429 from Anthropic), flip the kill switch by unsetting `ANTHROPIC_API_KEY` — graceful degradation

### Rule 2 — Postgres connection pool saturation

**Trigger:** available connections drop below 20% of max over 2 minutes.

**Implementation:**
- Query the Postgres `pg_stat_database` view via a tiny cron (`php artisan pg:pool-check` — not yet built, see below)
- Alert if `numbackends / max_connections > 0.80`

**Response:**
1. Check for long-running queries in `pg_stat_activity`
2. Kill any query running > 30s that isn't an authorised report
3. If it's a load-test misfire, throttle the offending tenant

## 5. Incident classification

| Severity | Response time | Example |
|---|---|---|
| P0 — outage | 15 min | Tenant subdomains returning 502; payroll computing wrong deductions |
| P1 — feature broken | 2h | AI assistant returning 500 for every request; signup flow broken |
| P2 — degraded UX | next business day | Pricing page slow; trial banner wrong date |
| P3 — cosmetic | backlog | Typo in feature page; CSP report noise |

**First responder:** Amos (week 1-2 post-launch), then whoever is on-call rotation when hiring starts.

**Escalation path:**
1. Post in internal ops channel (Slack #ops)
2. If P0/P1, post status page update at ep.eiaawsolutions.com/status (not built — Session 13)
3. Email affected customers if incident exceeds 30 min

## 6. What's NOT yet wired (Session 13+)

- **Sentry package install** — documented here, needs Amos to `composer require` in production
- **Status page** (ep.eiaawsolutions.com/status) — Session 13
- **SLO dashboards** in a TSDB — TimescaleDB or Grafana, Session 14+
- **Distributed tracing** — OpenTelemetry across the app + AI gateway, Session 14+
- **On-call rotation + PagerDuty/Opsgenie** — not needed until there's >1 person on call
- **PgBouncer** in front of Postgres — Railway Postgres doesn't need it yet; consider at >50 concurrent tenants
- **Runbook per top-5 errors** — after the first incident, write the runbook for it. Don't pre-write runbooks for hypothetical errors.

## 7. Cadence

- Weekly: review the 5 scheduled-command log files for anomalies (billing, payroll, backup, integrity)
- Weekly: skim Sentry issues list, triage anything with >10 events
- Monthly: review `ai_usage_daily` for per-tenant cost trends; flag any tenant approaching its budget
- Quarterly: re-run the load test against staging; confirm thresholds still hold with current data volume
