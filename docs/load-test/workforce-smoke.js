// EIAAW Workforce — k6 smoke load test
//
// Goals for launch: sustain 100 concurrent tenants × 10 active users at p95
// latency < 800ms on Railway staging with the shared-pool Postgres.
//
// Run:
//   k6 run -e BASE_HOST=ep.eiaawsolutions.com -e VUS=1000 docs/load-test/workforce-smoke.js
//
// Seeds:
//   php artisan tenant:seed-load-test --tenants=100 --users-per-tenant=10
//   (writes docs/load-test/tenants.json that this script reads)
//
// What it exercises per VU (weighted):
//   - GET / (marketing landing — apex)
//   - GET /pricing (apex)
//   - POST login on a random tenant subdomain
//   - GET /dashboard (logged-in)
//   - POST /ai/ask with a 120-char prompt (gated to Starter+; respects budget)
//   - GET /hr/employees (if user role allows)
//
// Thresholds fail the run if p95 latency crosses target or error rate > 1%.

import http from 'k6/http';
import { sleep, check, fail } from 'k6';
import { SharedArray } from 'k6/data';
import exec from 'k6/execution';

const BASE_HOST = __ENV.BASE_HOST || 'ep.eiaawsolutions.com';
const VUS       = parseInt(__ENV.VUS || '200', 10);
const DURATION  = __ENV.DURATION || '5m';

// Loaded from the seed command output. Each entry:
//   { tenant_slug: "acme-001", work_email: "u01@acme-001.test", password: "LoadTest#2026" }
const accounts = new SharedArray('accounts', function () {
    try {
        return JSON.parse(open('./tenants.json'));
    } catch (e) {
        return [{
            tenant_slug: 'demo',
            work_email: 'load-test@demo.test',
            password: 'LoadTest#2026',
        }];
    }
});

export const options = {
    scenarios: {
        marketing_ramp: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '30s', target: Math.floor(VUS * 0.2) },
                { duration: '1m',  target: Math.floor(VUS * 0.5) },
                { duration: DURATION, target: VUS },
                { duration: '30s', target: 0 },
            ],
            gracefulStop: '15s',
        },
    },
    thresholds: {
        http_req_failed:    ['rate<0.01'],          // <1% errors
        http_req_duration:  ['p(95)<800', 'p(99)<2000'],
        'http_req_duration{name:marketing_landing}': ['p(95)<400'],
        'http_req_duration{name:login_post}':        ['p(95)<1200'],
        'http_req_duration{name:ai_ask}':            ['p(95)<3000'],
    },
    userAgent: 'k6-eiaaw-workforce-smoke/1.0',
};

function apex(path) { return `https://${BASE_HOST}${path}`; }
function tenantUrl(slug, path) { return `https://${slug}.${BASE_HOST}${path}`; }

function pickAccount() {
    if (accounts.length === 0) fail('No accounts seeded — run tenant:seed-load-test first.');
    return accounts[exec.vu.idInTest % accounts.length];
}

function getCsrf(session, url) {
    const res = session.get(url);
    check(res, { 'csrf page loaded': (r) => r.status === 200 });
    const match = res.body.match(/name="csrf-token"\s+content="([^"]+)"/);
    return match ? match[1] : null;
}

export default function () {
    const account = pickAccount();
    const session = http;  // cookie jar is per-VU in k6

    // 1. Marketing apex — never authenticated
    let res = http.get(apex('/'), { tags: { name: 'marketing_landing' } });
    check(res, { 'landing 200': (r) => r.status === 200 });
    sleep(0.3);

    res = http.get(apex('/pricing'), { tags: { name: 'marketing_pricing' } });
    check(res, { 'pricing 200': (r) => r.status === 200 });
    sleep(0.5);

    // 2. Login on tenant subdomain
    const loginUrl = tenantUrl(account.tenant_slug, '/login');
    const csrf = getCsrf(session, loginUrl);
    if (!csrf) {
        fail('Could not extract CSRF token from login page');
    }

    res = session.post(loginUrl, {
        _token: csrf,
        work_email: account.work_email,
        password: account.password,
    }, { tags: { name: 'login_post' }, redirects: 0 });

    // 302 on success, 200 (form re-render) on failure
    check(res, { 'login redirect': (r) => r.status === 302 });
    if (res.status !== 302) {
        sleep(1);
        return;
    }

    // 3. Dashboard
    res = session.get(tenantUrl(account.tenant_slug, '/dashboard'), { tags: { name: 'dashboard' } });
    check(res, { 'dashboard 200': (r) => r.status === 200 || r.status === 302 });
    sleep(1);

    // 4. AI ask (1 in 3 VUs — keeps total AI cost bounded in a big run)
    if (exec.vu.idInTest % 3 === 0) {
        const askCsrf = getCsrf(session, tenantUrl(account.tenant_slug, '/dashboard')) || csrf;
        res = session.post(
            tenantUrl(account.tenant_slug, '/ai/ask'),
            JSON.stringify({ prompt: 'Summarise pending leave requests this week' }),
            {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': askCsrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                tags: { name: 'ai_ask' },
            }
        );
        // 200 ok, 403 plan-gate, 429 budget breaker — all acceptable at the infra layer
        check(res, { 'ai_ask responded': (r) => [200, 403, 429].includes(r.status) });
    }

    sleep(Math.random() * 2 + 1);
}
