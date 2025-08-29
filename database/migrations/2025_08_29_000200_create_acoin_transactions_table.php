<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if(!Schema::hasTable('acoin_transactions')) {
            Schema::create('acoin_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('kid_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->bigInteger('amount'); // positive or negative
                $table->string('type', 50)->default('fund'); // fund, spend, adjust, trade_buy, trade_sell
                $table->string('description')->nullable();
                $table->bigInteger('balance_after');
                $table->timestamps();

                $table->index('kid_id');
                $table->index('parent_id');
                $table->foreign('kid_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('parent_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('acoin_transactions');
    }
};
