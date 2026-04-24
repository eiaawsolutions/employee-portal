<?php

namespace App\Http\Controllers;

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
