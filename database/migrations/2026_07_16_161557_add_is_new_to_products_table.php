<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Products marked as new get an automatic "JAUNUMS!" badge on the public
     * site. Existing rows that carried the badge inside their name (e.g.
     * "JAUNUMS! Labubu") are flagged and their name prefix stripped.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_new')->default(false)->after('size');
        });

        DB::table('products')->where('name', 'like', 'JAUNUMS!%')->get()->each(function (object $product): void {
            DB::table('products')->where('id', $product->id)->update([
                'is_new' => true,
                'name' => ltrim(substr($product->name, strlen('JAUNUMS!'))),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')->where('is_new', true)->get()->each(function (object $product): void {
            DB::table('products')->where('id', $product->id)->update([
                'name' => 'JAUNUMS! '.$product->name,
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_new');
        });
    }
};
