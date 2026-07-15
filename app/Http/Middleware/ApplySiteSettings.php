<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySiteSettings
{
    /**
     * Overlay stored settings onto the site config so every consumer of
     * config('site.*') sees the values managed from the admin panel.
     * The config file itself acts as the default for unsaved keys.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = rescue(fn (): array => Setting::map(), [], report: false);

        foreach ($settings as $key => $value) {
            if (filled($value) && config()->has("site.{$key}")) {
                config()->set("site.{$key}", $value);
            }
        }

        return $next($request);
    }
}
