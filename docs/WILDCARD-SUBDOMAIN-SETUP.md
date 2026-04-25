# Wildcard subdomain setup for tenant auto-provisioning

**One-time setup. ~5 minutes. Skip if already done.**

EIAAW Workforce provisions tenant workspaces at `{slug}.eiaawsolutions.com`
on every signup. With wildcard DNS, there is **no per-signup infrastructure
work** — a new tenant is just a database row, and the workspace URL works
instantly.

This document walks through the three things that need to exist at the
edge for that to work.

## What you're setting up

- **Cloudflare wildcard CNAME** so any `*.eiaawsolutions.com` hostname
  resolves to Railway's edge.
- **Railway wildcard custom domain** so Railway accepts traffic for any
  `*.eiaawsolutions.com` hostname.
- **TLS certificate coverage** — Cloudflare's free Universal SSL covers
  `*.eiaawsolutions.com` automatically (one level of wildcard included).

Specific records (`ep.`, `eiaaw-hq.`, `ads.`, `sa.`, `www.`) are unaffected:
explicit DNS records always win over the wildcard, so existing subdomains
keep their current targets.

## Prerequisites

- You can edit DNS in the `eiaawsolutions.com` Cloudflare zone.
- You can add custom domains to the EIAAW-Workforce Railway service.
- Code at `21b3f17` or later is deployed (split tenant_domain handling
  + AuthController bounce + slug validation).

---

## Step 1 — Cloudflare DNS (wildcard CNAME)

1. Open the Cloudflare dashboard → `eiaawsolutions.com` zone → **DNS → Records**.
2. Click **Add record**.
3. Configure:

   | Field            | Value                                              |
   |------------------|----------------------------------------------------|
   | Type             | `CNAME`                                            |
   | Name             | `*` (literal asterisk)                             |
   | Target           | `eiaaw-workforce-production.up.railway.app`        |
   | Proxy status     | **Proxied** (orange cloud)                         |
   | TTL              | `Auto`                                             |

4. Save.

**What this does:** Any subdomain that does NOT have an explicit DNS record
in the zone resolves to Cloudflare → Railway. With ~10 explicit records
already in the zone (`ep`, `eiaaw-hq`, `ads`, `sa`, `www`, MX, TXT, etc.),
the wildcard catches everything else — i.e., every future tenant slug.

**Why proxied (orange cloud):** Free Universal SSL only covers proxied
hostnames, and Cloudflare's WAF + DDoS protection only applies to
proxied traffic. DNS-only (grey cloud) would expose Railway's IPs and
skip TLS termination.

---

## Step 2 — Railway custom domain (wildcard)

1. Open Railway → EIAAW-Workforce service → **Settings → Networking → Public Networking**.
2. Click **+ Custom Domain**.
3. Enter `*.eiaawsolutions.com` exactly (with the leading asterisk and dot).
4. Save.

Railway will display "Waiting for DNS update" briefly while it provisions
an SSL cert via the DNS-01 ACME challenge (~30-60 seconds, sometimes longer).

**What this does:** Tells Railway's edge to accept incoming connections for
any `*.eiaawsolutions.com` hostname and present a valid SSL cert. Without
this, Railway returns a 404 at the load balancer regardless of what your
app would do.

**Note:** The existing `eiaaw-hq.eiaawsolutions.com` and `ep.eiaawsolutions.com`
custom domains can stay — Railway resolves the most specific match first
when multiple custom domains could match a hostname. The wildcard is the
catch-all for tenant slugs that don't have their own dedicated entry.

---

## Step 3 — Verify end-to-end

Once both steps are done, test with a non-existent slug. The DNS resolves,
Railway accepts the request, your app's `ResolveTenant` middleware looks
up the slug in `tenants`, finds nothing, and returns null tenant context.
The marketing-style "no such workspace" path takes over (or `/up` returns
200 because the health endpoint doesn't need a tenant).

```bash
# Should return HTTP 200 — Railway accepted the request, app served /up
curl -sI https://probe-test-12345.eiaawsolutions.com/up

# Should return HTTP 404 with the no-such-tenant message — wildcard
# routing works, app correctly refused an unknown slug
curl -sI https://probe-test-12345.eiaawsolutions.com/login
```

If either curl fails with a TLS handshake error, the Railway custom
domain wildcard hasn't finished provisioning the cert yet. Wait 2 minutes
and retry.

---

## What's reserved and can't be a tenant slug

The reserved-slug list lives in `config/eiaaw.php` → `reserved_slugs`. It
includes:

- **Infra**: `app`, `admin`, `api`, `www`, `mail`, `static`, `assets`, `cdn`
- **Existing CNAMEs**: `ep`, `ads`, `sa`
- **EIAAW internals**: `eiaaw`, `eiaaw-hq`, `eiaaw-admin`, `workforce`, `system`, `hq`
- **Auth & security** (anti-phishing): `auth`, `oauth`, `sso`, `saml`, `oidc`, `login`, `signin`, `signup`, `signout`, `logout`, `register`, `verify`, `webhook`, `webhooks`, `security`, `admin-portal`
- **Platform pages**: `help`, `support`, `status`, `docs`, `documentation`, `blog`, `about`, `pricing`, `features`, `legal`, `terms`, `privacy`, `contact`, `sales`, `partners`, `jobs`, `careers`, `dashboard`, `billing`, `account`, `profile`, `settings`, `console`, `changelog`
- **Ops**: `staging`, `dev`, `test`, `qa`, `beta`, `alpha`, `preview`, `sandbox`, `demo`, `monitor`, `monitoring`, `metrics`, `logs`, `health`, `ping`, `up`

To add or remove: edit `config/eiaaw.php` → `reserved_slugs` and deploy.
No DB migration needed — the list is read at request time.

---

## What happens on signup (zero infra calls)

1. Visitor fills the signup form at `https://ep.eiaawsolutions.com/signup`
2. `SignupController::start()` validates the desired slug via
   `Tenant::isSlugAvailable()` (checks reserved list + format + collision)
3. `SignupInvite` row is created, confirmation email sent
4. Visitor clicks the link, sets a password
5. `TenantProvisioner::provisionFromInvite()` re-checks slug (race-safe),
   creates the `Tenant` row + first owner `User` + pivot
6. Controller redirects to `https://{slug}.eiaawsolutions.com/login`
7. **DNS already resolves**, **Railway already accepts**, **TLS already works**.
   The new workspace is live with zero infrastructure-level operations.

Total infrastructure work per signup: **zero**. All provisioning is
database operations inside one transaction.

---

## What if a customer wants their own custom domain (e.g., `hr.acme.com`)

That's a separate workflow (vanity custom domains for Enterprise tier),
not covered here. It requires:
- A DNS record on the customer's side (CNAME `hr.acme.com` → Railway)
- A custom domain entry in Railway
- A `tenants.custom_domain` column + ResolveTenant lookup by custom_domain
  before slug-based subdomain lookup

Defer until Enterprise tier sells with the SLA addendum that covers it.
The wildcard subdomain pattern works fine for the first ~50 tenants
without it.

---

## Failure recovery

**Cloudflare wildcard CNAME deleted by accident** → every tenant goes down
in under 2 minutes (Cloudflare's TTL). Re-add the record; recovery is
~30 seconds after creation.

**Railway wildcard custom domain deleted** → traffic still reaches Railway
(DNS still works) but Railway returns 404. Re-add; cert reprovisioning is
30-60 seconds.

**TLS cert expiry** → Railway auto-renews via ACME. If renewal fails (rare),
Railway shows a yellow warning in the Networking panel and you have ~7 days
to investigate before the cert actually expires.

**Tenant slug typo / abuse** → a customer manages to register `bigbank`
when they don't actually represent BigBank. Not a DNS/infra issue — handle
via the suspend/reactivate flow in the HQ Tenants directory + the
trademark dispute process you'll need anyway. The reserved-slug list is
the prevention; suspension is the cure.
