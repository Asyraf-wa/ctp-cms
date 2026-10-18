<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD CMT-01..05. Replies are one level deep (parent_id points at a top-level comment).
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // set for replies written in the admin
            $table->string('name', 80);
            $table->string('email', 190);
            $table->text('body');                       // as typed (Markdown-lite)
            $table->text('body_html');                  // rendered and sanitised
            $table->string('status', 10)->default('pending'); // pending | approved | spam | trash
            $table->boolean('notify_replies')->default(false);
            $table->string('unsubscribe_token', 40)->nullable()->unique();
            $table->char('visitor_hash', 40)->nullable(); // HMAC of IP + UA, never the raw IP
            $table->string('spam_reason', 120)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['article_id', 'status', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
