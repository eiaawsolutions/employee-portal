<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTwoFactor
{
    /** Routes that enforced users may still access (to set up 2FA or log out). */
    private const ALLOWED_ROUTES = [
        'two-factor.setup',
        'two-factor.confirm',
        'two-factor.disable',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mustSetupTwoFactor() && !in_array($request->route()?->getName(), self::ALLOWED_ROUTES)) {
            return redirect()->route('two-factor.setup')
                ->with('warning', 'Your role requires two-factor authentication. Please set it up to continue.');
        }

        return $next($request);
    }
}
