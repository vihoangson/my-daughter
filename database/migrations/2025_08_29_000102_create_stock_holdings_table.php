<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('stock_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('avg_price'); // weighted average purchase price
            $table->timestamps();
            $table->unique(['user_id','stock_id']);
        });
    }
    public function down() {
        Schema::dropIfExists('stock_holdings');
    }
};

