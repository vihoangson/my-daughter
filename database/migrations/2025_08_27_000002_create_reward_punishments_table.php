<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('reward_punishments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->integer('points');
            $table->enum('type', ['reward', 'punishment']);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->foreign('child_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    public function down() {
        Schema::dropIfExists('reward_punishments');
    }
};

