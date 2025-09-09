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
            // Use longText instead of JSON for MariaDB compatibility; model still casts to array
            $table->longText('tags')->nullable();
            $table->boolean('pinned')->default(false);
            $table->string('cover_image')->nullable();
            // Use longText instead of JSON for meta as well
            $table->longText('meta')->nullable();
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
