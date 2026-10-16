<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD ART-01..12, ARCH §4.
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->default('tutorial'); // tutorial | blog
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('excerpt', 500)->nullable();
            $table->json('tldr')->nullable(); // list of key takeaways (GEO-03)

            $table->json('body')->nullable(); // TipTap JSON, the source of truth
            $table->longText('body_rendered')->nullable(); // HTML from the render pipeline
            $table->longText('body_text')->nullable(); // plain text, for search and word count
            $table->json('toc')->nullable();
            $table->unsignedInteger('word_count')->default(0);

            $table->foreignId('featured_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('series_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('series_position')->nullable();

            $table->string('status', 12)->default('draft'); // draft | scheduled | published
            $table->timestamp('published_at')->nullable();
            $table->timestamp('content_updated_at')->nullable(); // "Last updated" (ART-07)

            $table->string('difficulty', 15)->nullable(); // beginner | intermediate | advanced
            $table->unsignedSmallInteger('reading_minutes')->default(1);
            $table->unsignedSmallInteger('reading_minutes_override')->nullable();
            $table->json('prerequisites')->nullable();
            $table->json('tested_with')->nullable(); // [{name, version}]
            $table->string('repo_url', 500)->nullable();
            $table->string('demo_url', 500)->nullable();
            $table->json('faq')->nullable(); // [{question, answer}] (GEO-06)

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('needs_review')->default(false);
            $table->boolean('comments_enabled')->default(true);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('kudos')->default(0);
            $table->unsignedInteger('legacy_id')->nullable()->unique();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['type', 'slug']);
            $table->index(['status', 'published_at']);
            $table->fullText(['title', 'excerpt', 'body_text']);
        });

        Schema::table('article_tag', function (Blueprint $table) {
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
        });

        // ART-03: manual saves plus one rolling autosave per article and user.
        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('excerpt', 500)->nullable();
            $table->json('tldr')->nullable();
            $table->json('body')->nullable();
            $table->boolean('is_autosave')->default(false);
            $table->timestamps();

            $table->index(['article_id', 'is_autosave', 'created_at']);
        });

        // SEO-01: per-entity overrides (articles now, pages/projects/categories later).
        Schema::create('seo_meta', function (Blueprint $table) {
            $table->id();
            $table->morphs('seoable');
            $table->string('title')->nullable();
            $table->string('description', 320)->nullable();
            $table->string('keywords')->nullable();
            $table->string('canonical', 500)->nullable();
            $table->string('robots', 30)->nullable();
            $table->foreignId('og_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_meta');
        Schema::dropIfExists('article_revisions');
        Schema::table('article_tag', fn (Blueprint $table) => $table->dropForeign(['article_id']));
        Schema::dropIfExists('articles');
    }
};
