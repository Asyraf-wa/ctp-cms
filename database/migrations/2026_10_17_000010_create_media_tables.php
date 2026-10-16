<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD MED-01.
        Schema::create('media_folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('disk', 30);
            $table->string('path', 500)->unique();
            $table->string('filename'); // original upload name
            $table->string('name')->nullable(); // title
            $table->string('mime', 100);
            $table->string('extension', 10);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt', 300)->nullable();
            $table->string('caption', 500)->nullable();
            $table->string('credit')->nullable();
            $table->json('variants')->nullable(); // {"320": "path", "768": …, "og": …}
            $table->char('hash', 40)->index(); // sha1 of the stored file, for dedupe
            $table->timestamps();
        });

        // PRD MED-05: where each file is used, so in-use files can't be deleted.
        Schema::create('media_usages', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->morphs('usable');
            $table->string('field', 30);
            $table->primary(['media_id', 'usable_type', 'usable_id', 'field']);
        });

        Schema::table('series', function (Blueprint $table) {
            $table->foreign('cover_media_id')->references('id')->on('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('series', fn (Blueprint $table) => $table->dropForeign(['cover_media_id']));
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media');
        Schema::dropIfExists('media_folders');
    }
};
