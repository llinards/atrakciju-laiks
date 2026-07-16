<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usage constraints shown as a checklist under the description
     * (age, weight, height, simultaneous-user limits).
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('suitability_items')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('suitability_items');
        });
    }
};
