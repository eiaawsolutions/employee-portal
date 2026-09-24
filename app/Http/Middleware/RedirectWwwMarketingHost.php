<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * www.ep.eiaawsolutions.com is served straight by Railway (it bypasses
 * Cloudflare), so without this it answers 200 with duplicate content and
 * builds og:image / JSON-LD URLs on the www host. Send it to the one
 * marketing host with a permanent redirect, keeping path and query.
 */
class RedirectWwwMarketingHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $marketingHost = strtolower(config('eiaaw.marketing_host', 'ep.eiaawsolutions.com'));

        if (strtolower($request->getHost()) === 'www.'.$marketingHost) {
            return redirect()->away('https://'.$marketingHost.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
