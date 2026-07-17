# Atrakciju Laiks

A product catalog and rental showcase website for [atrakcijulaiks.lv](https://www.atrakcijulaiks.lv) — a Latvian
company renting inflatable attractions, tents, and canopies for children's parties, events, and celebrations. Built
with Laravel and Livewire, it pairs a fast, SEO-tuned public site with a complete admin panel for managing all
content.

## Features

### Public

- **Home Page** — Admin-managed hero slider, category cards, and FAQ accordion
- **Category Pages** — Clean root-level slug URLs (`/piepusamas-atrakcijas`) with product size filtering and
  pagination
- **Product Pages** — Photo gallery with lightbox, technical specs table, rental price tiers, included items, and
  rich-text description/rental terms tabs
- **Sale Section** — Products flagged for sale listed under `/pardosana` with their own pricing
- **Photo Gallery** — Category-grouped masonry gallery with lightbox
- **Reservation Modal** — Global call/email contact modal reachable from every product
- **Contact Page** — Settings-driven contact details with an embedded map
- **SEO** — Per-page meta descriptions, canonical URLs, Open Graph/Twitter cards, JSON-LD structured data
  (LocalBusiness, Product + Offer, BreadcrumbList, FAQPage), XML sitemap, and dynamic robots.txt
- **Cookie Consent** — GDPR-compliant banner (essential cookies only) with a privacy policy page and live-generated
  cookies table
- **Error Pages** — Branded 404/419/429/500/503 pages that render without the application stack (maintenance-safe)
- **Mobile UX** — Floating contact rail collapses into an expandable FAB on small screens

### Admin

- **Dashboard** — Content statistics and quick actions
- **Products** — Full CRUD with image cropping (5:4), per-product galleries, drag-and-drop ordering, sale/new/visibility
  flags, and per-category slugs
- **Categories** — CRUD with images, brand colors, ordering, and route-safe slug validation
- **Hero Slides** — Home page slider management with upload limits
- **Gallery** — Photo categories and bulk photo uploads
- **FAQs** — Question/answer management with ordering and visibility
- **Site Settings** — Phone, e-mail, address, and social links applied site-wide via middleware
- **Authentication** — Laravel Fortify with password reset; public registration is disabled — users are created via
  `php artisan user:create`

## Tech Stack

| Layer      | Technology                                    |
|------------|-----------------------------------------------|
| Framework  | Laravel 13                                    |
| Frontend   | Livewire 4 (inline `⚡` components), Alpine.js |
| Admin UI   | Flux UI Pro v2                                |
| Styling    | Tailwind CSS v4                               |
| Auth       | Laravel Fortify                               |
| Testing    | Pest 4                                        |
| Analysis   | Larastan (PHPStan)                            |
| Build      | Vite                                          |
| Database   | SQLite (default), MySQL/PostgreSQL compatible |
| Cookies    | whitecube/laravel-cookie-consent              |
| Code Style | Laravel Pint                                  |

## Architecture

```
app/
├── Actions/Fortify/     # User creation & password reset actions
├── Concerns/            # Shared validation rule traits
├── Console/Commands/    # user:create CLI command
├── Enums/               # ProductSize, CategoryColor
├── Http/Controllers/    # Sitemap & robots.txt controllers
├── Http/Middleware/     # ApplySiteSettings (DB settings → config)
├── Models/              # Eloquent models
├── Providers/           # App, Fortify, Cookies providers
└── Support/             # Seo — per-request SEO context singleton

resources/views/
├── components/public/   # Public-site Blade components (cards, header, footer, icons…)
├── errors/              # Branded error pages (standalone layout)
├── layouts/             # public / admin / auth layouts
├── pages/
│   ├── public/          # Livewire pages (⚡ inline components)
│   └── admin/           # Admin panel pages
└── vendor/              # Customized cookie-consent banner
```

Key conventions:

- **Two frontends, one app** — the public site is hand-built with Tailwind components (no Flux), while the admin
  panel uses stock Flux UI. Each has its own CSS entry with an explicit Tailwind `@source` allowlist, so the public
  bundle never absorbs admin classes.
- **Slug routing** — categories live at root level (`/{category}`) with wildcard routes registered last; explicitly
  defined paths (e.g. `/galerija`, `/pardosana`) are automatically reserved and validated against category slugs.
- **SEO context** — pages fill a `Seo` singleton in their `rendering()` hook; the shared head partial renders meta
  tags, canonicals, and JSON-LD from it. Sale product URLs canonicalize to their category URL to avoid duplicate
  content.
- **Settings** — a key-value `settings` table is loaded into `config('site.*')` by middleware, editable from the
  admin panel.

## Prerequisites

- PHP 8.3+
- Composer
- Node.js & npm
- [Laravel Herd](https://herd.laravel.com/) (recommended) or any PHP server
- A [Flux UI Pro](https://fluxui.dev) license (required to install `livewire/flux-pro`)

## Setup

1. **Clone the repository**

   ```bash
   git clone https://github.com/llinards/atrakciju-laiks.git
   cd atrakciju-laiks
   ```

2. **Add Flux UI Pro credentials**

   ```bash
   composer config http-basic.composer.fluxui.dev your-email your-license-key
   ```

3. **Run the setup script**

   ```bash
   composer setup
   ```

   This installs dependencies, copies `.env.example` to `.env`, generates the app key, runs migrations, and builds
   frontend assets.

4. **Seed initial content** (optional)

   ```bash
   php artisan db:seed
   ```

5. **Create an admin user**

   ```bash
   php artisan user:create
   ```

6. **Start the development server**

   ```bash
   composer run dev
   ```

## Development

```bash
# Run tests
php artisan test --compact

# Run a specific test
php artisan test --compact --filter=ProductPageTest

# Static analysis
composer types:check

# Format PHP code
composer lint
```

Tests and static analysis run automatically on GitHub Actions for every push.
