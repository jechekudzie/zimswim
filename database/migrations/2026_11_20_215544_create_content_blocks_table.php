<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Overrides for the wording and pictures on the public pages.
     *
     * Only overrides. Every block's label, type and original wording live in
     * the catalogue in code, and a row here replaces one. Nothing is seeded,
     * so an empty table renders exactly the site as written — and clearing a
     * field puts the original back rather than leaving a hole in a page.
     */
    public function up(): void
    {
        Schema::create('content_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique()->comment('Catalogue key, e.g. home.hero.heading');
            $table->text('value')->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
