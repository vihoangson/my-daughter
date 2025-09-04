<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reward_item_id');
            $table->unsignedBigInteger('kid_id');
            $table->unsignedBigInteger('parent_id');
            $table->unsignedInteger('price_acoin');
            $table->timestamps();
            $table->foreign('reward_item_id')->references('id')->on('reward_items')->onDelete('cascade');
            $table->foreign('kid_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['kid_id','reward_item_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('reward_redemptions'); }
};

