<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Products get slug-based public URLs (/{category}/{product}). Slugs only
     * need to be unique within their category since they always sit behind
     * the category segment.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        $taken = [];

        DB::table('products')->orderBy('id')->get()->each(function (object $product) use (&$taken): void {
            $base = Str::slug($product->name) ?: 'produkts';
            $slug = $base;

            for ($suffix = 2; in_array("{$product->category_id}:{$slug}", $taken, true); $suffix++) {
                $slug = "{$base}-{$suffix}";
            }

            $taken[] = "{$product->category_id}:{$slug}";

            DB::table('products')->where('id', $product->id)->update(['slug' => $slug]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique(['category_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['category_id', 'slug']);
            $table->dropColumn('slug');
        });
    }
};
