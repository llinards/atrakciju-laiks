<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gallery categories no longer carry a dedicated cover image; the public
     * card always shows the category's first photo.
     */
    public function up(): void
    {
        Schema::table('gallery_categories', function (Blueprint $table) {
            $table->dropColumn('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gallery_categories', function (Blueprint $table) {
            $table->string('path')->nullable()->after('slug');
        });
    }
};
