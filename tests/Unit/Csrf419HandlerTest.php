<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Unit-level coverage for the 419 (CSRF / TokenMismatch) exception handler
 * configured in bootstrap/app.php.
 *
 * The handler is the user-facing fix for the "stuck on /login after entering
 * 2FA" loop: when a stale browser cookie causes a TokenMismatchException, we
 * (a) log a structured warning so we can see it firing, and (b) instruct the
 * browser to drop the stale session + XSRF cookies in the response so the
 * next page-load starts clean.
 */
class Csrf419HandlerTest extends TestCase
{
    public function test_419_redirects_to_login_with_warning_flash(): void
    {
        $request = $this->createRequest();
        $exception = new HttpException(419);

        // Trigger the registered render callback by letting the framework handle the exception.
        $response = app()->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->render($request, $exception);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('login'), $response->headers->get('Location'));
    }

    public function test_419_response_clears_session_and_xsrf_cookies(): void
    {
        $request = $this->createRequest();
        $exception = new HttpException(419);

        $response = app()->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->render($request, $exception);

        $cookies = collect($response->headers->getCookies());
        $sessionCookieName = (string) config('session.cookie');

        $sessionCookie = $cookies->first(fn ($c) => $c->getName() === $sessionCookieName);
        $xsrfCookie    = $cookies->first(fn ($c) => $c->getName() === 'XSRF-TOKEN');

        $this->assertNotNull($sessionCookie, 'Response should clear the session cookie');
        $this->assertNotNull($xsrfCookie,    'Response should clear the XSRF-TOKEN cookie');

        // A "forget" cookie has expiry in the past so the browser drops it.
        $this->assertLessThan(time(), $sessionCookie->getExpiresTime());
        $this->assertLessThan(time(), $xsrfCookie->getExpiresTime());
    }

    public function test_419_handler_logs_diagnostic_context(): void
    {
        Log::spy();

        $request = $this->createRequest();
        $exception = new HttpException(419);

        app()->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->render($request, $exception);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'csrf_419_redirect'
                    && array_key_exists('cookies_seen', $context)
                    && array_key_exists('session_cookie_present', $context)
                    && array_key_exists('config_session_domain', $context);
            });
    }

    public function test_non_419_http_exceptions_pass_through(): void
    {
        $request = $this->createRequest();
        $exception = new HttpException(404);

        $response = app()->make(\Illuminate\Contracts\Debug\ExceptionHandler::class)
            ->render($request, $exception);

        // Should NOT redirect to /login; should render the framework's default 404 page.
        $this->assertNotSame(302, $response->getStatusCode());
    }

    private function createRequest(): \Illuminate\Http\Request
    {
        $request = \Illuminate\Http\Request::create('/login', 'POST', [
            'work_email' => 'test@example.com',
            'password'   => 'wrongpass',
            '_token'     => 'stale-token',
        ]);

        // Bind the request into the container so any Auth/session facade calls
        // inside the handler resolve against this request.
        app()->instance('request', $request);

        return $request;
    }
}
