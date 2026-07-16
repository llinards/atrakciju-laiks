<?php

namespace App\Models;

use Database\Factories\GalleryImageFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GalleryImage extends Model
{
    /** @use HasFactory<GalleryImageFactory> */
    use HasFactory;

    /**
     * Photos are scaled down to this width proportionally, preserving the
     * aspect ratio the public masonry grid renders.
     */
    public const int IMAGE_WIDTH = 1600;

    protected static function booted(): void
    {
        static::deleting(function (GalleryImage $image): void {
            Storage::disk('public')->delete($image->path);
        });
    }

    /**
     * @return BelongsTo<GalleryCategory, $this>
     */
    public function galleryCategory(): BelongsTo
    {
        return $this->belongsTo(GalleryCategory::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
