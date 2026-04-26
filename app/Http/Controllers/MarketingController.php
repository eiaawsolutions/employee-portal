<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * MarketingController — public marketing surfaces at the apex host
 * (ep.eiaawsolutions.com). The 'apex' middleware on the route group
 * 404s these on tenant subdomains.
 *
 * All views extend layouts/marketing.blade.php. Pricing data is read
 * from config('eiaaw.pricing') so a rate-card change is a single edit.
 */
class MarketingController extends Controller
{
    public function landing()
    {
        return view('marketing.landing', [
            'pricing' => config('eiaaw.pricing'),
        ]);
    }

    /**
     * XML sitemap for search engines and AI crawlers. Lists every public
     * marketing surface at the apex host. Tenant app surfaces are
     * intentionally excluded (they 404 on the apex anyway).
     */
    public function sitemap(): Response
    {
        $today = now()->toDateString();

        $urls = [
            ['loc' => route('marketing.landing'),       'changefreq' => 'weekly',  'priority' => '1.0', 'lastmod' => $today],
            ['loc' => route('marketing.features'),      'changefreq' => 'monthly', 'priority' => '0.9', 'lastmod' => $today],
            ['loc' => route('marketing.pricing'),       'changefreq' => 'monthly', 'priority' => '0.9', 'lastmod' => $today],
            ['loc' => route('marketing.security'),      'changefreq' => 'monthly', 'priority' => '0.7', 'lastmod' => $today],
            ['loc' => route('marketing.faq'),           'changefreq' => 'monthly', 'priority' => '0.7', 'lastmod' => $today],
            ['loc' => route('signup.form'),             'changefreq' => 'monthly', 'priority' => '0.8', 'lastmod' => $today],
            ['loc' => route('marketing.find-workspace'),'changefreq' => 'yearly',  'priority' => '0.4', 'lastmod' => $today],
            ['loc' => route('marketing.terms'),         'changefreq' => 'yearly',  'priority' => '0.3', 'lastmod' => $today],
            ['loc' => route('marketing.privacy'),       'changefreq' => 'yearly',  'priority' => '0.3', 'lastmod' => $today],
            ['loc' => route('marketing.dpa'),           'changefreq' => 'yearly',  'priority' => '0.3', 'lastmod' => $today],
        ];

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>'."\n";
            $xml .= '    <lastmod>'.$u['lastmod'].'</lastmod>'."\n";
            $xml .= '    <changefreq>'.$u['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$u['priority'].'</priority>'."\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>'."\n";

        return response($xml, 200, [
            'Content-Type'  => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function pricing()
    {
        return view('marketing.pricing', [
            'pricing' => config('eiaaw.pricing'),
        ]);
    }

    public function features()
    {
        return view('marketing.features');
    }

    public function security()
    {
        return view('marketing.security');
    }

    public function faq()
    {
        return view('marketing.faq');
    }

    public function terms()
    {
        return view('marketing.legal.terms');
    }

    public function privacy()
    {
        return view('marketing.legal.privacy');
    }

    public function dpa()
    {
        return view('marketing.legal.dpa');
    }
}
