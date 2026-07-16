<?php

namespace App\Models;

use Database\Factories\GalleryCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GalleryCategory extends Model
{
    /** @use HasFactory<GalleryCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The DB cascade removes image rows without firing their model
        // events, so their files are cleaned up here.
        static::deleting(function (GalleryCategory $category): void {
            Storage::disk('public')->delete($category->images()->pluck('path')->all());
        });
    }

    /**
     * @return HasMany<GalleryImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class)->ordered();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /**
     * Derive a unique slug from a title, suffixing with a counter when the
     * natural slug is already taken. Gallery slugs live under the /galerija
     * prefix, so they only need to be unique within this table.
     */
    public static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'galerija';
        }

        $taken = static::query()
            ->where('slug', 'like', "{$base}%")
            ->pluck('slug')
            ->all();

        $slug = $base;
        $suffix = 2;

        while (in_array($slug, $taken, true)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * The image shown on the gallery index card: always the category's
     * first photo.
     */
    public function coverUrl(): ?string
    {
        return $this->images->first()?->url();
    }
}
