<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('loans', function(Blueprint $table){
            $table->id();
            $table->unsignedBigInteger('kid_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('principal'); // original principal
            $table->unsignedBigInteger('remaining_principal');
            $table->unsignedInteger('rate_per_day_bp'); // basis points per day (e.g. 150 = 1.50%)
            $table->unsignedInteger('term_days');
            $table->date('start_date');
            $table->date('due_date');
            $table->enum('status',['active','repaid','overdue','defaulted'])->default('active');
            $table->unsignedBigInteger('accrued_interest')->default(0); // total accrued (not yet capitalized)
            $table->timestamp('last_accrual_at')->nullable();
            $table->timestamps();

            $table->foreign('kid_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('users')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('loans');
    }
};
