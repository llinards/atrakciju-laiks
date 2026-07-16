<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Rejects values that would collide with an existing route's first URI
 * segment. Category slugs become root-level URLs, so a slug matching e.g.
 * "dashboard" would be shadowed by the explicit route and never resolve.
 */
class NotReservedPath implements ValidationRule
{
    /**
     * Paths not derivable from the route collection: Livewire's asset prefix
     * is version-suffixed at runtime, and Fortify features that are currently
     * disabled may register these routes later.
     *
     * @var list<string>
     */
    private const array STATIC_RESERVED = [
        'admin',
        'api',
        'confirm-password',
        'email',
        'livewire',
        'two-factor-challenge',
        'verify-email',
    ];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (static::isReserved($value)) {
            $fail(__('This web address is reserved and cannot be used.'));
        }
    }

    public static function isReserved(string $value): bool
    {
        /** @var list<string>|null $reserved */
        static $reserved = null;

        $reserved ??= collect(Route::getRoutes()->getRoutes())
            ->map(fn (RoutingRoute $route): string => Str::before($route->uri(), '/'))
            ->reject(fn (string $segment): bool => $segment === '' || $segment === '/' || str_contains($segment, '{'))
            ->merge(self::STATIC_RESERVED)
            ->unique()
            ->values()
            ->all();

        return in_array($value, $reserved, true);
    }
}
