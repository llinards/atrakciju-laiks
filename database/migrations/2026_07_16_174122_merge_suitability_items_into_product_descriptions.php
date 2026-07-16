<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The suitability checklist becomes a bullet list inside the rich text
     * description: plain-text descriptions are converted to HTML paragraphs
     * and the list items are appended as a <ul>, then the column is dropped.
     */
    public function up(): void
    {
        DB::table('products')->whereNotNull('suitability_items')->get()->each(function (object $product): void {
            /** @var list<string> $items */
            $items = json_decode($product->suitability_items, true) ?: [];

            if ($items === []) {
                return;
            }

            $paragraphs = $product->description === null || str_contains($product->description, '<')
                ? (string) $product->description
                : collect(preg_split('/\R{2,}/', trim($product->description)) ?: [])
                    ->map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>')
                    ->implode('');

            $list = '<ul>'.collect($items)->map(fn (string $item): string => '<li>'.e($item).'</li>')->implode('').'</ul>';

            DB::table('products')->where('id', $product->id)->update([
                'description' => $paragraphs.$list,
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('suitability_items');
        });
    }

    /**
     * Reverse the migrations. The merged list stays inside the description —
     * only the column itself is restored.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('suitability_items')->nullable()->after('description');
        });
    }
};
