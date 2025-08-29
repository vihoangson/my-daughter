<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('stock_trades', function(Blueprint $table){
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->enum('type',['buy','sell']);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('price'); // executed price per share
            $table->integer('profit')->default(0); // realized profit for sell (could be negative)
            $table->unsignedInteger('total'); // quantity * price
            $table->unsignedBigInteger('balance_after'); // user Acoin balance after trade
            $table->timestamps();
            $table->index(['user_id','created_at']);
        });
    }
    public function down() { Schema::dropIfExists('stock_trades'); }
};

