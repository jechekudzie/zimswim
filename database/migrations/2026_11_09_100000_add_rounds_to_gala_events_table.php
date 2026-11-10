<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gala_events', function (Blueprint $table) {
            $table->foreignId('parent_event_id')
                ->nullable()
                ->after('gala_session_id')
                ->comment('The heats this final is swum from')
                ->constrained('gala_events')
                ->nullOnDelete();
            $table->unsignedTinyInteger('qualifiers')->nullable()->after('round')
                ->comment('How many swimmers go through from heats to this final');
            $table->timestamp('seeded_at')->nullable()->after('qualifiers');
            $table->timestamp('results_published_at')->nullable()->after('seeded_at');
        });
    }

    public function down(): void
    {
        Schema::table('gala_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_event_id');
            $table->dropColumn(['qualifiers', 'seeded_at', 'results_published_at']);
        });
    }
};
