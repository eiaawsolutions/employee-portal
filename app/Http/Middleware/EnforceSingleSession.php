<?php

namespace App\Http\Middleware;

use App\Models\SecurityAuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnforceSingleSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Fetch a fresh copy from DB so we always compare against the latest token
            $user = \App\Models\User::find(Auth::id());

            $dbToken      = $user?->session_token;
            $sessionToken = session('_single_session_token');

            // If DB has a token and this session's token doesn't match → newer login elsewhere
            if ($dbToken && $sessionToken !== $dbToken) {
                SecurityAuditLog::record('session_hijack', [
                    'user_id'    => $user?->id,
                    'work_email' => $user?->work_email,
                    'role'       => $user?->role,
                    'ip_address' => $request->ip(),
                    'url'        => $request->fullUrl(),
                    'details'    => 'Session invalidated: account signed in from another device or browser.',
                ]);

                // Surface the same event in the application log so Sentry / log
                // tail can spot when this is firing right after a fresh login
                // (= bug, not legit "logged in elsewhere"). Tokens are masked.
                Log::warning('single_session_kick', [
                    'user_id'         => $user?->id,
                    'host'            => $request->getHost(),
                    'path'            => $request->path(),
                    'db_token_prefix' => $dbToken ? substr($dbToken, 0, 6) : null,
                    'sess_token_prefix' => $sessionToken ? substr($sessionToken, 0, 6) : null,
                    'sess_token_set'  => $sessionToken !== null,
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['work_email' => 'You have been logged out because your account was signed in from another device or browser.']);
            }
        }

        return $next($request);
    }
}
