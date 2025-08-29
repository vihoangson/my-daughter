<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('stock_prices', function(Blueprint $table){
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->unsignedInteger('price');
            $table->timestamp('captured_at')->useCurrent();
            $table->timestamps();
            $table->index(['stock_id','captured_at']);
        });
    }
    public function down() { Schema::dropIfExists('stock_prices'); }
};

