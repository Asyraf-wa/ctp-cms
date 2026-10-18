<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SEO-11: missing URLs people (and crawlers) actually request, so they can be redirected.
        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->char('path_hash', 40)->unique();
            $table->string('path', 500);
            $table->unsignedInteger('hits')->default(1);
            $table->string('referrer', 500)->nullable(); // the latest external referrer
            $table->boolean('is_bot')->default(false);
            $table->boolean('ignored')->default(false);
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->index(['ignored', 'hits']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
    }
};
