<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Per-request SEO context, registered as a singleton. Public pages fill it
 * in their rendering() hook and the public head partial renders the meta
 * tags, canonical link, Open Graph tags, and JSON-LD graphs from it.
 */
class Seo
{
    protected ?string $description = null;

    protected ?string $canonical = null;

    protected ?string $image = null;

    protected string $type = 'website';

    /** @var list<array<string, mixed>> */
    protected array $jsonLd = [];

    /**
     * Set the meta description from plain or rich text — tags are stripped
     * and the text is shortened to snippet length on word boundaries.
     */
    public function describe(?string $text): static
    {
        $text = trim(strip_tags($text ?? ''));

        if ($text !== '') {
            $this->description = Str::limit(preg_replace('/\s+/u', ' ', $text) ?? $text, 160, '…', preserveWords: true);
        }

        return $this;
    }

    public function canonical(string $url): static
    {
        $this->canonical = $url;

        return $this;
    }

    public function image(?string $url): static
    {
        $this->image = $url ?? $this->image;

        return $this;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Queue a schema.org graph for output as a JSON-LD script tag.
     *
     * @param  array<string, mixed>  $graph
     */
    public function jsonLd(array $graph): static
    {
        $this->jsonLd[] = $graph;

        return $this;
    }

    public function description(): string
    {
        return $this->description ?? config('site.description');
    }

    public function canonicalUrl(): string
    {
        return $this->canonical ?? url()->current();
    }

    public function imageUrl(): string
    {
        return $this->image ?? asset('images/og-image.png');
    }

    public function ogType(): string
    {
        return $this->type;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function jsonLdGraphs(): array
    {
        return $this->jsonLd;
    }
}
