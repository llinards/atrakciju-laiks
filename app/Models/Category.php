<?php

namespace App\Models;

use App\Enums\CategoryColor;
use App\Enums\ProductSize;
use App\Rules\NotReservedPath;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'color' => CategoryColor::class,
            'is_visible' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The DB cascade removes product rows without firing their model
        // events, so their image files are cleaned up here alongside our own.
        static::deleting(function (Category $category): void {
            $paths = $category->products()->whereNotNull('path')->pluck('path')->all();

            if ($category->path !== null) {
                $paths[] = $category->path;
            }

            Storage::disk('public')->delete($paths);
        });
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * The products that make this category offer a size filter.
     *
     * @return HasMany<Product, $this>
     */
    public function sizedProducts(): HasMany
    {
        return $this->products()->visible()->sized();
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
     * The visible categories driving the public navigation, with a
     * `has_size_filter` flag for categories whose products carry sizes.
     *
     * @return Collection<int, static>
     */
    public static function navigation(): Collection
    {
        return static::query()
            ->visible()
            ->ordered()
            ->withExists('sizedProducts as has_size_filter')
            ->get();
    }

    /**
     * Menu links to this category's size-filtered listing.
     *
     * @return list<array{label: string, href: string}>
     */
    public function sizeFilterLinks(): array
    {
        return array_map(fn (ProductSize $size): array => [
            'label' => $size->label().' '.Str::lower($this->title),
            'href' => route('category.show', ['category' => $this->slug, 'size' => $size->value]),
        ], ProductSize::cases());
    }

    /**
     * Derive a unique, non-reserved slug from a title, suffixing with a
     * counter when the natural slug is already taken. Slugs become
     * root-level URLs, so route paths are off limits.
     */
    public static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = 'kategorija';
        }

        $taken = static::query()
            ->where('slug', 'like', "{$base}%")
            ->pluck('slug')
            ->all();

        $slug = $base;
        $suffix = 2;

        while (NotReservedPath::isReserved($slug) || in_array($slug, $taken, true)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function url(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }
}
