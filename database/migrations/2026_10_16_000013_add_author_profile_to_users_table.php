<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD AUTHOR-02: the public author profile.
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('slug')->nullable()->unique()->after('display_name');
            $table->string('avatar_path')->nullable()->after('google_avatar');
            $table->string('job_title')->nullable();
            $table->string('bio_short', 300)->nullable();
            $table->text('bio_long')->nullable();
            $table->string('website_url')->nullable();
            $table->json('socials')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'slug', 'avatar_path', 'job_title', 'bio_short', 'bio_long', 'website_url', 'socials']);
        });
    }
};
