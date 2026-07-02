<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->index();
            $table->string('external_id')->nullable();
            $table->text('url');
            $table->char('url_hash', 64)->unique();
            $table->string('title')->index();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->text('image_url')->nullable();
            $table->timestamp('published_at')->index();
            $table->string('language')->nullable();
            $table->timestamps();

            $table->index(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
