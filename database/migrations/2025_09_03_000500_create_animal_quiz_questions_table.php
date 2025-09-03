<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('animal_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->string('difficulty', 20)->index();
            $table->string('topic', 100)->index();
            $table->text('text');
            $table->json('options');
            $table->unsignedTinyInteger('correct');
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('animal_quiz_questions'); }
};

