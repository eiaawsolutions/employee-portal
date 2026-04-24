<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsurePlan;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * EnsurePlan middleware — gates routes by tenant plan.
 *
 * We fake a tenant by binding a stdClass-like object with a hasFeature()
 * method into app('current_tenant'). The real Tenant model's hasFeature()
 * reads config/plans.php, which is what we want to verify.
 */
class EnsurePlanMiddlewareTest extends TestCase
{
    public function test_passes_when_tenant_has_feature(): void
    {
        $tenant = new class {
            public string $plan = 'scale';
            public function hasFeature(string $feature): bool {
                return in_array($feature, config("plans.scale.features", []), true);
            }
        };

        $this->app->instance('current_tenant', $tenant);

        $middleware = new EnsurePlan();
        $response = $middleware->handle(
            Request::create('/assets', 'GET'),
            fn () => response('ok', 200),
            'it.assets'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_redirects_when_tenant_lacks_feature(): void
    {
        $tenant = new class {
            public string $plan = 'starter';
            public function hasFeature(string $feature): bool {
                return in_array($feature, config("plans.starter.features", []), true);
            }
        };

        $this->app->instance('current_tenant', $tenant);

        $middleware = new EnsurePlan();
        $response = $middleware->handle(
            Request::create('/accounting', 'GET'),
            fn () => response('should not reach', 200),
            'finance.accounting'
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('upgrade-required', $response->headers->get('Location'));
    }

    public function test_returns_json_for_xhr_requests(): void
    {
        $tenant = new class {
            public string $plan = 'starter';
            public function hasFeature(string $feature): bool { return false; }
        };

        $this->app->instance('current_tenant', $tenant);

        $middleware = new EnsurePlan();
        $request = Request::create('/ai/ask', 'POST');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->headers->set('Accept', 'application/json');

        $response = $middleware->handle(
            $request,
            fn () => response('should not reach'),
            'finance.accounting'
        );

        $this->assertSame(403, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('upgrade_required', $body['error']);
        $this->assertSame('finance.accounting', $body['feature']);
    }

    public function test_aborts_403_when_no_tenant_context(): void
    {
        // Deliberately DO NOT bind current_tenant
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware = new EnsurePlan();
        $middleware->handle(
            Request::create('/accounting', 'GET'),
            fn () => response('should not reach', 200),
            'finance.accounting'
        );
    }
}
