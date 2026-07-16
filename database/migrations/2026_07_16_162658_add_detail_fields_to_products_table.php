<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Content for the public product detail page, mirroring the legacy site's
     * sections: intro paragraphs, TEHNISKĀ INFORMĀCIJA label/value specs,
     * NOMAS CENA per-day price rows, and the NOMAS NOTEIKUMI block split into
     * the included-items checklist and the free-text terms.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->json('specs')->nullable()->after('description');
            $table->json('rental_prices')->nullable()->after('specs');
            $table->json('included_items')->nullable()->after('rental_prices');
            $table->text('rental_terms')->nullable()->after('included_items');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['description', 'specs', 'rental_prices', 'included_items', 'rental_terms']);
        });
    }
};
