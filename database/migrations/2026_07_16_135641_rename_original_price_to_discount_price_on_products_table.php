<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invert the discount semantics: `price` becomes the standard price and
     * the renamed `discount_price` holds the optional lower promo price
     * (previously `price` held the promo price and `original_price` the
     * standard one), so existing discounted rows swap their two values.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('original_price', 'discount_price');
        });

        $this->swapDiscountedPrices();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->swapDiscountedPrices();

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('discount_price', 'original_price');
        });
    }

    /**
     * Swap price and discount_price on discounted rows. Done row by row in
     * PHP because MySQL evaluates `SET a = b, b = a` sequentially.
     */
    private function swapDiscountedPrices(): void
    {
        DB::table('products')->whereNotNull('discount_price')->get()->each(function (object $product): void {
            DB::table('products')->where('id', $product->id)->update([
                'price' => $product->discount_price,
                'discount_price' => $product->price,
            ]);
        });
    }
};
