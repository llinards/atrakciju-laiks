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

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'size' => ProductSize::class,
            'is_visible' => 'boolean',
        ];
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

    public function url(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    public function formattedPrice(): string
    {
        return Number::format((float) $this->price, maxPrecision: 2).'€';
    }

    public function formattedOriginalPrice(): ?string
    {
        if ($this->original_price === null || (float) $this->original_price <= (float) $this->price) {
            return null;
        }

        return Number::format((float) $this->original_price, maxPrecision: 2).'€';
    }

    /**
     * Discount percent derived from the original price, e.g. 160€ → 130€ = 19.
     */
    public function discountPercent(): ?int
    {
        if ($this->formattedOriginalPrice() === null) {
            return null;
        }

        return (int) round((1 - (float) $this->price / (float) $this->original_price) * 100);
    }
}
