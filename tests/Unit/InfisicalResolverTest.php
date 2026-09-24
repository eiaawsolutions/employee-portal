<?php

namespace Tests\Unit;

use App\Services\Secrets\InfisicalResolver;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Regression for the 2026-09-25 Workforce incident: an unresolvable handle
 * made EVERY request log in to Infisical again (failures weren't cached, the
 * token lived only in process memory), Infisical blocked the egress IP, and
 * each request then waited out two 5 s timeouts.
 */
class InfisicalResolverTest extends TestCase
{
    private const HANDLE = 'secret://eiaaw-all-projects/prod/STRIPE_SECRET';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function resolver(MockHandler $mock): InfisicalResolver
    {
        return new InfisicalResolver(
            ['client_id' => 'id', 'client_secret' => 'secret', 'project_id' => 'p', 'cache_ttl' => 300],
            new Client(['handler' => HandlerStack::create($mock), 'base_uri' => 'https://infisical.test']),
        );
    }

    private function login(): Response
    {
        return new Response(200, [], json_encode(['accessToken' => 'tok', 'expiresIn' => 3600]));
    }

    private function secret(string $value): Response
    {
        return new Response(200, [], json_encode(['secret' => ['secretValue' => $value]]));
    }

    private function down(): ConnectException
    {
        return new ConnectException('Connection timed out', new Request('GET', '/'));
    }

    public function test_a_resolved_value_is_cached(): void
    {
        $mock = new MockHandler([$this->login(), $this->secret('sk_live_x')]);
        $r = $this->resolver($mock);

        $this->assertSame('sk_live_x', $r->resolve(self::HANDLE));
        $this->assertSame('sk_live_x', $r->resolve(self::HANDLE));
        $this->assertSame(0, $mock->count(), 'both requests consumed, no extra calls');
    }

    public function test_the_login_token_is_shared_across_requests(): void
    {
        // Two separate resolver instances = two PHP requests.
        $this->resolver(new MockHandler([$this->login(), $this->secret('a')]))
            ->resolve('secret://eiaaw-all-projects/prod/ONE');

        $second = new MockHandler([$this->secret('b')]); // no login response queued
        $this->assertSame('b', $this->resolver($second)->resolve('secret://eiaaw-all-projects/prod/TWO'));
    }

    public function test_a_failure_is_cached_so_it_is_not_retried_on_every_request(): void
    {
        $this->assertSame(self::HANDLE, $this->resolver(new MockHandler([$this->down()]))->resolve(self::HANDLE));

        $untouched = new MockHandler([$this->login(), $this->secret('should-not-be-fetched')]);
        $this->assertSame(self::HANDLE, $this->resolver($untouched)->resolve(self::HANDLE));
        $this->assertSame(2, $untouched->count(), 'no network call while the failure is cached');
    }

    public function test_the_last_good_value_is_served_when_infisical_is_unreachable(): void
    {
        $this->resolver(new MockHandler([$this->login(), $this->secret('sk_live_last_good')]))->resolve(self::HANDLE);
        Cache::forget('infisical:'.md5(self::HANDLE)); // fresh-value TTL expired

        $r = $this->resolver(new MockHandler([$this->down()]));
        $this->assertSame('sk_live_last_good', $r->resolve(self::HANDLE));
    }

    public function test_secret_values_are_encrypted_in_the_cache(): void
    {
        $this->resolver(new MockHandler([$this->login(), $this->secret('sk_live_plaintext_check')]))->resolve(self::HANDLE);

        foreach (['infisical:', 'infisical:last-good:'] as $prefix) {
            $stored = Cache::get($prefix.md5(self::HANDLE));
            $this->assertIsString($stored);
            $this->assertStringNotContainsString('sk_live_plaintext_check', $stored);
        }
        $this->assertStringNotContainsString('tok', (string) Cache::get('infisical:access-token'));
    }

    public function test_requests_fail_fast_on_connect(): void
    {
        $seen = [];
        $mock = new MockHandler([function ($request, $options) use (&$seen) {
            $seen = $options;
            return $this->login();
        }, $this->secret('v')]);

        $this->resolver($mock)->resolve(self::HANDLE);

        $this->assertLessThanOrEqual(2, $seen['connect_timeout'] ?? 99);
    }
}
