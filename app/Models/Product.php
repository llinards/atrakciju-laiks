<?php

namespace App\Models;

use App\Enums\ProductSize;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Stored product images are cropped to these dimensions — the same 5:4
     * ratio the public product card renders.
     */
    public const IMAGE_WIDTH = 1200;

    public const IMAGE_HEIGHT = 960;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'size' => ProductSize::class,
            'is_visible' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product): void {
            if ($product->path !== null) {
                Storage::disk('public')->delete($product->path);
            }
        });
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sized(Builder $query): void
    {
        $query->whereNotNull('size');
    }

    public function url(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    /**
     * The effective price shown to visitors: the discount price when a
     * valid one is set, otherwise the standard price.
     */
    public function formattedPrice(): string
    {
        $price = $this->hasDiscount() ? $this->discount_price : $this->price;

        return Number::format((float) $price, maxPrecision: 2).'€';
    }

    /**
     * The standard price, shown struck through while a discount is active.
     */
    public function formattedOriginalPrice(): ?string
    {
        if (! $this->hasDiscount()) {
            return null;
        }

        return Number::format((float) $this->price, maxPrecision: 2).'€';
    }

    /**
     * Discount percent derived from the two prices, e.g. 160€ → 130€ = 19.
     */
    public function discountPercent(): ?int
    {
        if (! $this->hasDiscount()) {
            return null;
        }

        return (int) round((1 - (float) $this->discount_price / (float) $this->price) * 100);
    }

    public function hasDiscount(): bool
    {
        return $this->discount_price !== null && (float) $this->discount_price < (float) $this->price;
    }
}
