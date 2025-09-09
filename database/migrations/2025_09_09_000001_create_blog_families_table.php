<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('blog_families', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->enum('visibility', ['public','members','private'])->default('members');
            $table->enum('status', ['draft','published','archived'])->default('draft');
            $table->json('tags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->string('cover_image')->nullable();
            $table->json('meta')->nullable(); // any extra metadata (reading time, reactions counts, etc.)
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['family_id','status']);
            $table->index(['user_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_families');
    }
};
