<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD PUB-09, ARCH §4. Projects live at /projects/{slug}.
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tagline', 255)->nullable();
            $table->json('body')->nullable();
            $table->longText('body_rendered')->nullable();
            $table->json('toc')->nullable();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('gallery')->nullable();
            $table->json('stack')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('live_url')->nullable();
            $table->string('repo_url')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort']);
        });

        Schema::create('project_article', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->primary(['project_id', 'article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_article');
        Schema::dropIfExists('projects');
    }
};
