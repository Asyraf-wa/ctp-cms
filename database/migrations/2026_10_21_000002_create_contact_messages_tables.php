<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD CONTACT-01..07. Purged after 12 months (PDPA); the IP is kept only as an HMAC.
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 20);
            $table->string('name', 80);
            $table->string('email', 190);
            $table->string('company', 120)->nullable();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->string('status', 10)->default('new'); // new | read | replied | archived | spam
            $table->string('spam_reason', 120)->nullable();
            $table->char('visitor_hash', 40)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('contact_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_replies');
        Schema::dropIfExists('contact_messages');
    }
};
