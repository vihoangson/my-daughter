<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('game_id'); // can map to internal game key like 'math_adventure'
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();
            $table->index(['user_id','game_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('game_scores');
    }
};

