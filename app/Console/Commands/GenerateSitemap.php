<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

/**
 * GenerateSitemap — writes public/sitemap.xml at deploy-time.
 *
 * Why a build-time artifact instead of a dynamic route: Railway's edge
 * intercepts requests for *.xml as static-file lookups before they reach
 * Laravel. A Route::get('/sitemap.xml', ...) handler is never invoked.
 * Generating the file into public/ during the nixpacks build phase makes
 * it a real static file the edge can serve.
 *
 * Run by nixpacks.toml in [phases.build] after route:cache.
 */
class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--path= : Override output path (defaults to public/sitemap.xml)}';
    protected $description = 'Generate public/sitemap.xml from the marketing route names';

    public function handle(): int
    {
        $path = $this->option('path') ?: public_path('sitemap.xml');
        $today = now()->toDateString();
        $marketingHost = config('eiaaw.marketing_host', 'ep.eiaawsolutions.com');

        // Force the URL generator to produce apex HTTPS URLs even though
        // this is running in a CLI context with no host. Without this,
        // route() returns http://localhost/... during the build container.
        URL::forceRootUrl('https://'.$marketingHost);
        URL::forceScheme('https');

        $urls = [
            ['loc' => route('marketing.landing'),        'changefreq' => 'weekly',  'priority' => '1.0'],
            ['loc' => route('marketing.features'),       'changefreq' => 'monthly', 'priority' => '0.9'],
            ['loc' => route('marketing.pricing'),        'changefreq' => 'monthly', 'priority' => '0.9'],
            ['loc' => route('marketing.security'),       'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('marketing.faq'),            'changefreq' => 'monthly', 'priority' => '0.7'],
            // /signup intentionally excluded — it 302-redirects to /pricing when no
            // ?plan= is supplied (post 2026-04 flow change), and crawlers shouldn't
            // index a redirect target that already lives in this sitemap.
            ['loc' => route('marketing.find-workspace'), 'changefreq' => 'yearly',  'priority' => '0.4'],
            ['loc' => route('marketing.terms'),          'changefreq' => 'yearly',  'priority' => '0.3'],
            ['loc' => route('marketing.privacy'),        'changefreq' => 'yearly',  'priority' => '0.3'],
            ['loc' => route('marketing.dpa'),            'changefreq' => 'yearly',  'priority' => '0.3'],
        ];

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>'."\n";
            $xml .= '    <lastmod>'.$today.'</lastmod>'."\n";
            $xml .= '    <changefreq>'.$u['changefreq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$u['priority'].'</priority>'."\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>'."\n";

        file_put_contents($path, $xml);

        $this->info('Sitemap written: '.$path.' ('.count($urls).' URLs)');

        return self::SUCCESS;
    }
}
