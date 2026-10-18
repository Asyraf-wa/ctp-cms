<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ART-10: one row per visitor per article per day (deduplication). Pruned by ctp:rollup-views.
        // visitor_hash is an HMAC of IP + user agent: no raw IPs are stored (PDPA).
        Schema::create('article_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->date('viewed_on');
            $table->char('visitor_hash', 40);
            $table->unique(['article_id', 'viewed_on', 'visitor_hash']);
            $table->index('viewed_on');
        });

        // Daily totals for trends and the admin widget.
        Schema::create('article_view_stats', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->primary(['article_id', 'date']);
            $table->index('date');
        });

        // ART-12: one kudos per visitor per article.
        Schema::create('article_kudos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->char('visitor_hash', 40);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['article_id', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_kudos');
        Schema::dropIfExists('article_view_stats');
        Schema::dropIfExists('article_views');
    }
};
