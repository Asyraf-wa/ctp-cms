<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD PUB-10: pages share the article render pipeline (blocks, TOC, media usage).
        Schema::table('pages', function (Blueprint $table) {
            $table->string('excerpt', 500)->nullable()->after('slug');
            $table->longText('body_rendered')->nullable()->after('body');
            $table->json('toc')->nullable()->after('body_rendered');
            $table->unsignedSmallInteger('sort')->default(0)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['excerpt', 'body_rendered', 'toc', 'sort']);
        });
    }
};
