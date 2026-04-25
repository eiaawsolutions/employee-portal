<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        // SecretsServiceProvider MUST be first — it resolves `secret://` handles
        // in config paths BEFORE CashierServiceProvider, mail, storage, or any
        // other consumer reads them. See config/secrets.php and docs/laravel-
        // infisical-integration in the eiaaw-secrets-mcp repo.
        App\Providers\SecretsServiceProvider::class,
        // PlatformSettingsServiceProvider runs after Secrets so DB-stored values
        // override env defaults at boot. Used for the SuperAdmin Integrations
        // page (Resend, Stripe, Anthropic, OpenAI, R2, Sentry).
        App\Providers\PlatformSettingsServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\CashierServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust Railway's edge proxy so X-Forwarded-Proto is honored. Without
        // this, $request->secure() returns false even when the edge already
        // terminated TLS, and our ForceHttps middleware 301-loops + the
        // /up health check fails with 301 instead of 200.
        $middleware->trustProxies(at: '*', headers:
            \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // Force HTTPS in production
        $middleware->prepend(\App\Http\Middleware\ForceHttps::class);

        // Global security headers on every response
        $middleware->prepend(\App\Http\Middleware\SecurityHeaders::class);

        // EIAAW Workforce — resolve current tenant from subdomain BEFORE any
        // tenant-scoped query runs. Binds app('current_tenant') and sets the
        // Postgres SET LOCAL app.tenant_id session variable for RLS.
        // Runs on every web request; safe no-op on the marketing apex.
        $middleware->web(append: [
            \App\Http\Middleware\ResolveTenant::class,
        ]);

        // Named aliases so route definitions can opt-in/out granularly if needed.
        $middleware->alias([
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
            'apex'   => \App\Http\Middleware\EnsureApex::class,
            'plan'   => \App\Http\Middleware\EnsurePlan::class,
        ]);

        // Exempt the public AARF acknowledgement POST from CSRF verification.
        // This route is accessed via a token link (e.g. from email), often in a fresh
        // browser session where no CSRF token has been set yet.
        $middleware->validateCsrfTokens(except: [
            'aarf/*/acknowledge',
            'stripe/webhook',
            'sso/saml/acs',  // IdP posts here without CSRF — response signature is the integrity check
            'csp-report',    // Browser-emitted CSP violation reports
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Redirect to login with a fresh CSRF token when session has expired (419).
        // Laravel's prepareException() converts TokenMismatchException to HttpException(419)
        // before render callbacks run, so we must catch HttpException and check the status code.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                return redirect()->route('login')->with('warning', 'Your session has expired. Please log in again.');
            }
        });
    })->create();