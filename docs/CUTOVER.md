# EIAAW Workforce — Production Cutover Runbook

**Audience:** Amos + ops/DevOps. Execute top-to-bottom; do not skip steps.
**Target window:** 90-minute maintenance window on a weekday morning (Asia/Kuala_Lumpur).
**Rollback window:** if any verification step fails, revert DNS and investigate before retrying.

---

## T-7 days — Pre-flight

### 1. Stripe dashboard

- [ ] Create Products and Prices (see `.env.example` lines 86–97 for the 12 slots)
  - Starter / Growth / Scale × MYR/USD × monthly/annual
  - Enterprise = no Price (manual invoicing)
- [ ] Paste all 12 `price_...` IDs into production environment variables
- [ ] Create the production webhook endpoint pointed at `https://ep.eiaawsolutions.com/stripe/webhook`
- [ ] Copy the webhook secret into `STRIPE_WEBHOOK_SECRET`
- [ ] Enable events: `invoice.payment_succeeded`, `invoice.payment_failed`, `customer.subscription.deleted`, `customer.subscription.updated`

### 2. DNS

- [ ] Pre-create the wildcard CNAME `*.ep.eiaawsolutions.com` pointing at Railway's edge
- [ ] Lower TTL on `ep.eiaawsolutions.com` A/CNAME to 300s at least 24h before cutover
- [ ] Verify TLS cert provisioning for the wildcard on Railway

### 3. Environment variables (Railway production)

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://ep.eiaawsolutions.com`
- [ ] `APP_MARKETING_HOST=ep.eiaawsolutions.com`
- [ ] `APP_TENANT_DOMAIN=ep.eiaawsolutions.com`
- [ ] `FORCE_HTTPS=true`
- [ ] `SESSION_DOMAIN=.ep.eiaawsolutions.com` (leading dot)
- [ ] `DB_*` — Postgres with **non-superuser** role (`tenancy:check-rls` will refuse to boot otherwise)
- [ ] `ANTHROPIC_API_KEY` set
- [ ] `LOG_INTEGRITY_KEY` + `BACKUP_ENCRYPTION_KEY` generated fresh (never reuse staging values)
- [ ] `AI_BUDGET_*` match the pricing-tier numbers
- [ ] Stripe price IDs populated (see step 1)

### 4. Legal

- [ ] Counsel-reviewed Terms, Privacy, DPA committed and replacing the stubs
  - `resources/views/marketing/legal/terms.blade.php`
  - `resources/views/marketing/legal/privacy.blade.php`
  - `resources/views/marketing/legal/dpa.blade.php`
- [ ] Delete `resources/views/marketing/legal/_stub-banner.blade.php` and the `@include` in `_layout.blade.php`

### 5. Load test on staging

- [ ] `php artisan tenant:seed-load-test --tenants=100 --users-per-tenant=10` against staging
- [ ] Run `k6 run docs/load-test/workforce-smoke.js` with `VUS=1000`
- [ ] Verify thresholds pass:
  - `http_req_failed < 1%`
  - `p(95) < 800ms` overall
  - `p(95) < 400ms` marketing landing
  - `p(95) < 3000ms` AI ask
- [ ] Check Railway metrics — Postgres connection pool saturation, CPU, memory headroom

---

## T-1 day — Final checks

- [ ] `php artisan tenancy:check-rls` on production env → "6 passed"
- [ ] `php artisan tenancy:test-leakage` on production env → "22 passed"
- [ ] `php artisan route:list` — verify:
  - `marketing.*` routes present on apex
  - `sso.*` routes present (Enterprise-gated)
  - `audit:export`, `billing:trial-end`, `billing:past-due-suspend`, `tenant:seed-load-test` commands registered
- [ ] Staging screenshot of every marketing page + login + dashboard + AI drawer
- [ ] Daily backup ran successfully in the last 24h
- [ ] `log:verify-integrity` passed in the last 24h

---

## T-0 — Cutover (maintenance window)

### Phase A — Freeze (10 min)

1. Post banner on status page: "Scheduled maintenance, ETA 90 min"
2. Disable all signup routes via env flag or temporary middleware — no new tenants during cutover
3. Final pre-cutover Postgres backup: `php artisan backup:run --type=database --encrypt`
4. Export baseline metrics for comparison: request rate, error rate, p95 latency

### Phase B — Deploy (20 min)

5. Tag the release: `git tag -a v1.0.0 -m "Production launch"` — do NOT push yet
6. Deploy to Railway production (Railway CLI or dashboard)
7. Watch deploy logs for migration failures; abort and roll back if any migration errors
8. Confirm `php artisan migrate --pretend` shows no pending migrations after deploy

### Phase C — Verify apex + tenant (20 min)

9. `curl -I https://ep.eiaawsolutions.com/` → 200, HSTS header present, `Content-Security-Policy` present
10. `curl -I https://ep.eiaawsolutions.com/pricing` → 200
11. Open `https://ep.eiaawsolutions.com/` in incognito — landing renders, footer links work, legal pages render WITHOUT the "Pre-launch placeholder" banner
12. Sign up a real test tenant with a disposable email — provision completes in < 10 seconds
13. Log into the tenant subdomain — dashboard renders, AI drawer appears, trial banner shows "14 days"
14. Submit a test prompt in the AI drawer — answer returns, budget meter updates
15. Run `php artisan billing:trial-end --dry-run` and `php artisan billing:past-due-suspend --dry-run` — both exit clean with empty result sets

### Phase D — Stripe smoke (10 min)

16. In the Stripe dashboard, send a test `invoice.payment_failed` event to the webhook
17. Verify the tenant's `subscription_status` flips to `past_due` and `past_due_at` is set
18. Send a test `invoice.payment_succeeded` event — verify both flags clear
19. Check `subscription_events` table for the two idempotency records

### Phase E — Monitor (30 min)

20. Remove the status-page banner
21. Re-enable signup
22. Push the git tag: `git push origin v1.0.0`
23. Watch Railway metrics for 30 min:
    - Error rate stays < 0.5%
    - p95 latency < 800ms
    - No OOM on the app or Postgres
    - No unhandled exceptions in logs
24. If anything regresses, execute rollback (below)

---

## Rollback

If Phase C, D, or E reveals a blocking issue:

1. **DNS rollback (fastest)** — flip `ep.eiaawsolutions.com` back to the pre-cutover A/CNAME. TTL 300s means ~5 min propagation.
2. **Railway rollback** — use the Railway dashboard to roll back the deploy to the previous build.
3. **Stripe rollback** — disable the production webhook endpoint; events queue at Stripe and will replay after fix.
4. Leave the new Postgres tables in place — they're tenancy-aware and don't affect the old single-tenant path since RLS defaults to reject on unset `app.tenant_id`.

---

## Post-launch (T+1 day onward)

- [ ] Monitor CSP violation reports at `/csp-report` — use the data to drive the Session 10 inline-handler migration
- [ ] Confirm the two scheduled billing commands ran overnight (`storage/logs/billing.log`)
- [ ] Confirm the nightly backup ran and integrity-verified
- [ ] Review `security_audit_logs` for anomalies from the first-day traffic
- [ ] First Enterprise SSO config test with a real customer (Microsoft Entra or Okta)
- [ ] Schedule first external pentest (Q4 2026 target per security page roadmap)

## Known non-launchers (defer to Session 10+)

- Inline-handler migration across 52 legacy views — CSP runs in enforce+report-only mode; `unsafe-hashes` still present
- Real SSO interop test with Okta/Azure AD/Google Workspace — requires a live IdP
- SOC 2 Type I third-party audit — scheduled Q3 2026
- SCIM 2.0 user provisioning — planned alongside SSO Type I
- Payroll module Malaysian statutory tables — present but not audited against the current LHDN/EPF schedule

## Amos-side launch checklist (not code)

- [ ] Announcement scheduled on eiaawsolutions.com and LinkedIn
- [ ] First 3 design-partner tenants briefed on the cutover window
- [ ] Sales deck updated with ep.eiaawsolutions.com URLs
- [ ] Support email auto-responder enabled on hello@eiaawsolutions.com
- [ ] Standing calendar slot for week-1 triage (daily 30-min with ops)
