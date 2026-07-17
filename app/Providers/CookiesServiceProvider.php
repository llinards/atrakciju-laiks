<?php

namespace App\Providers;

use Whitecube\LaravelCookieConsent\CookiesServiceProvider as ServiceProvider;
use Whitecube\LaravelCookieConsent\EssentialCookiesCategory;
use Whitecube\LaravelCookieConsent\Facades\Cookies;

class CookiesServiceProvider extends ServiceProvider
{
    /**
     * Define the cookies users should be aware of. The site only uses
     * cookies required for it to function — no analytics or marketing.
     */
    protected function registerCookies(): void
    {
        $essentials = Cookies::essentials();

        if ($essentials instanceof EssentialCookiesCategory) {
            $essentials->consent()->session()->csrf();
        }
    }
}
