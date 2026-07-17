{{--
    Brand-styled replacement for the package's default notice. Only essential
    cookies are registered, so a single accept action is enough — the API
    endpoint is called via window.LaravelCookieConsent (loaded through the
    @cookieconsentscripts directive in the public head partial).
--}}
<aside
    id="cookies-banner"
    x-data="{ open: true }"
    x-show="open"
    x-transition.opacity.duration.300ms
    class="fixed inset-x-4 bottom-4 z-50 lg:left-auto lg:right-4 lg:w-96"
    role="dialog"
    aria-label="{{ __('cookieConsent::cookies.title') }}"
>
    <div class="flex flex-col gap-4 rounded-[22px] border border-gray-200 bg-white p-4 shadow-lg">
        <div class="flex flex-col gap-1">
            <h2 class="font-heading text-lg font-bold leading-tight text-gray-900">
                @lang('cookieConsent::cookies.title')
            </h2>
            <p class="text-sm leading-5 text-gray-700">
                @lang('cookieConsent::cookies.intro')
                @if ($policy)
                    <a href="{{ $policy }}" class="font-semibold text-brand underline underline-offset-2 transition-colors hover:text-brand-dark">
                        Uzzināt vairāk
                    </a>
                @endif
            </p>
        </div>

        <button
            type="button"
            @click="window.LaravelCookieConsent.acceptAll().then(() => open = false)"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-sun bg-sun px-5 py-3 text-base font-semibold text-white shadow-xs transition-colors hover:border-amber-500 hover:bg-amber-500"
        >
            @lang('cookieConsent::cookies.all')
        </button>
    </div>
</aside>
