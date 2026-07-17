<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * Serve robots.txt dynamically so the sitemap URL always matches the
     * environment's APP_URL. Admin paths are kept out of the index.
     */
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /login',
            'Disallow: /dashboard',
            'Disallow: /settings',
            'Disallow: /site-settings',
            'Disallow: /hero-slides',
            'Disallow: /faqs',
            'Disallow: /categories',
            'Disallow: /products',
            'Disallow: /gallery',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines))
            ->header('Content-Type', 'text/plain');
    }
}
