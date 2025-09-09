<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('invite_code')->unique();
            $table->string('motto')->nullable();
            $table->string('timezone',64)->nullable()->default('Asia/Ho_Chi_Minh');
            $table->string('country', 3)->nullable();
            $table->foreignId('primary_parent_id')->nullable()->constrained('users')->nullOnDelete();
            // Use longText instead of JSON for broader DB compatibility; model will still cast to array
            $table->longText('settings')->nullable();
            $table->timestamps();
            $table->index(['primary_parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
