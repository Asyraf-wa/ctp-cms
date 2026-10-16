<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD SEO-11, SLUG-03, MIG-05. Paths are stored lowercase with a leading slash.
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 500)->unique();
            $table->string('to_path', 500)->nullable(); // null with 410 = gone
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->string('source', 20)->default('manual'); // manual | legacy | slug
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
