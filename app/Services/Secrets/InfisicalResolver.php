<?php

namespace App\Services\Secrets;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * InfisicalResolver — resolves `secret://project/env/path/NAME` handles to
 * real secret values via Infisical's universal-auth REST API.
 *
 * Pattern shared across EIAAW projects (All-in-one-business-suite being the
 * reference implementation, docs at c:/laragon/www/eiaaw-secrets-mcp/docs/
 * laravel-integration.md). v2 will extract this into a composer package so
 * it stops being copy-pasted.
 *
 * Fail-open: on dev machines without credentials, this returns the raw
 * handle unchanged rather than crashing the boot. Any caller that actually
 * needs the secret will surface its own error — we don't nuke the whole
 * app for one unresolvable handle.
 *
 * This runs on EVERY request boot (the PHP server boots Laravel per
 * request), so it must never touch the network per request. Incident
 * 2026-09-25: failures weren't cached and the token lived only in process
 * memory, so one unresolvable handle meant a login + fetch on every
 * request; Infisical blocked the egress IP and each request then sat
 * through two 5 s timeouts. Now:
 *   - the access token is cached across requests (Laravel cache)
 *   - a failure is remembered for FAILURE_TTL seconds (no retry storm)
 *   - the last good value is kept and served while Infisical is unreachable
 *   - connects give up after CONNECT_TIMEOUT seconds
 */
class InfisicalResolver
{
    private const FAILURE_TTL = 120;
    private const CONNECT_TIMEOUT = 2;
    private const TOKEN_CACHE_KEY = 'infisical:access-token';

    private ?string $accessToken = null;
    private int $accessTokenExpiresAt = 0;

    /**
     * @param  array<string, mixed>  $config  Values from config('secrets.infisical')
     */
    public function __construct(
        private readonly array $config,
        private readonly ?Client $http = null,
    ) {}

    /**
     * Resolve a `secret://project/env/path/NAME` handle to its real value.
     * Returns the raw handle on failure so the caller can decide how to react.
     */
    public function resolve(string $handle): string
    {
        if (! str_starts_with($handle, 'secret://')) {
            return $handle;
        }

        $parsed = self::parseHandle($handle);
        if ($parsed === null) {
            Log::warning('InfisicalResolver: malformed handle', ['handle' => $handle]);
            return $handle;
        }

        $ttl = (int) ($this->config['cache_ttl'] ?? 300);
        $key = md5($handle);
        $freshKey = 'infisical:'.$key;
        $lastGoodKey = 'infisical:last-good:'.$key;
        $failedKey = 'infisical:failed:'.$key;

        if (is_string($fresh = $this->cacheGet($freshKey))) {
            return $fresh;
        }

        // A recent failure: don't hit the network again yet.
        if (Cache::has($failedKey)) {
            return $this->cacheGet($lastGoodKey) ?? $handle;
        }

        try {
            $value = $this->fetch($parsed['environment'], $parsed['path'], $parsed['name']);
        } catch (\Throwable $e) {
            Cache::put($failedKey, true, self::FAILURE_TTL);
            $lastGood = $this->cacheGet($lastGoodKey);
            Log::error('InfisicalResolver: fetch failed', [
                'handle' => $handle,
                'error' => $e->getMessage(),
                'serving' => is_string($lastGood) ? 'last good value' : 'raw handle',
            ]);
            return is_string($lastGood) ? $lastGood : $handle;
        }

        Cache::put($freshKey, Crypt::encryptString($value), $ttl);
        Cache::forever($lastGoodKey, Crypt::encryptString($value));

        return $value;
    }

    /** Secret values are stored encrypted (APP_KEY) — the cache is a DB table. */
    private function cacheGet(string $key): ?string
    {
        $stored = Cache::get($key);
        if (! is_string($stored)) {
            return null;
        }
        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            Cache::forget($key); // written under an old APP_KEY or format
            return null;
        }
    }

    /**
     * @return array{project: string, environment: string, path: string, name: string}|null
     */
    public static function parseHandle(string $handle): ?array
    {
        if (! preg_match('#^secret://([^/]+)/([^/]+)(/.*)?/([A-Z0-9_]+)$#', $handle, $m)) {
            return null;
        }
        return [
            'project' => $m[1],
            'environment' => $m[2],
            'path' => ($m[3] ?? '') === '' ? '/' : $m[3],
            'name' => $m[4],
        ];
    }

    private function ensureToken(): void
    {
        if ($this->accessToken !== null && time() < $this->accessTokenExpiresAt - 30) {
            return;
        }

        // Shared across requests so each PHP request doesn't log in again.
        $cached = json_decode((string) $this->cacheGet(self::TOKEN_CACHE_KEY), true);
        if (is_array($cached) && time() < ($cached['expires_at'] ?? 0) - 30) {
            $this->accessToken = $cached['token'];
            $this->accessTokenExpiresAt = $cached['expires_at'];
            return;
        }

        $clientId = $this->config['client_id'] ?? null;
        $clientSecret = $this->config['client_secret'] ?? null;
        if (empty($clientId) || empty($clientSecret)) {
            throw new RuntimeException('Infisical credentials are not configured.');
        }

        $response = $this->client()->post('/api/v1/auth/universal-auth/login', [
            'json' => [
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
            ],
            'timeout' => (int) ($this->config['request_timeout'] ?? 5),
            'connect_timeout' => self::CONNECT_TIMEOUT,
        ]);

        $body = json_decode((string) $response->getBody(), true);
        if (! is_array($body) || ! isset($body['accessToken'])) {
            throw new RuntimeException('Infisical universal-auth did not return an access token.');
        }

        $this->accessToken = (string) $body['accessToken'];
        $expiresIn = (int) ($body['expiresIn'] ?? 3600);
        $this->accessTokenExpiresAt = time() + $expiresIn;
        Cache::put(self::TOKEN_CACHE_KEY, Crypt::encryptString(json_encode([
            'token' => $this->accessToken,
            'expires_at' => $this->accessTokenExpiresAt,
        ])), max(60, $expiresIn - 60));
    }

    private function fetch(string $environment, string $path, string $name): string
    {
        $this->ensureToken();

        $projectId = $this->config['project_id'] ?? null;
        if (empty($projectId)) {
            throw new RuntimeException('INFISICAL_PROJECT_ID is not configured.');
        }

        $response = $this->client()->get('/api/v3/secrets/raw/'.rawurlencode($name), [
            'query' => [
                'workspaceId' => $projectId,
                'environment' => $environment,
                'secretPath' => $path,
            ],
            'headers' => [
                'Authorization' => 'Bearer '.$this->accessToken,
            ],
            'timeout' => (int) ($this->config['request_timeout'] ?? 5),
            'connect_timeout' => self::CONNECT_TIMEOUT,
        ]);

        $body = json_decode((string) $response->getBody(), true);
        $value = $body['secret']['secretValue'] ?? null;
        if (! is_string($value)) {
            throw new RuntimeException("Infisical returned no value for {$name} in {$environment}.");
        }
        return $value;
    }

    private function client(): Client
    {
        if ($this->http !== null) return $this->http;
        return new Client([
            'base_uri' => rtrim((string) ($this->config['site_url'] ?? 'https://app.infisical.com'), '/'),
            'http_errors' => true,
        ]);
    }

    /** @throws GuzzleException */
    public function healthCheck(): bool
    {
        $this->ensureToken();
        return $this->accessToken !== null;
    }
}
