<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // D9: tutorials and pages share the domain/{slug} namespace; the unique index makes collisions impossible.
        Schema::create('root_slugs', function (Blueprint $table) {
            $table->string('slug')->primary();
            $table->morphs('sluggable');
            $table->unique(['sluggable_type', 'sluggable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('root_slugs');
    }
};
