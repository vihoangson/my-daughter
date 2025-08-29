<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up() {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('base_price'); // in Acoin units
            $table->unsignedInteger('current_price');
            $table->timestamps();
        });

        // Seed 5 fun stocks
        DB::table('stocks')->insert([
            ['code' => 'KDL', 'name' => 'Kẹo Dâu Lắc', 'base_price' => 50, 'current_price' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'BUB', 'name' => 'Bong Bóng Bay', 'base_price' => 80, 'current_price' => 80, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'GAC', 'name' => 'Gấu Áo Choàng', 'base_price' => 120, 'current_price' => 120, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'MNM', 'name' => 'Mỳ Ngon Mốc', 'base_price' => 60, 'current_price' => 60, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'SUS', 'name' => 'Siêu Úp Sờ', 'base_price' => 100, 'current_price' => 100, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
    public function down() {
        Schema::dropIfExists('stocks');
    }
};

