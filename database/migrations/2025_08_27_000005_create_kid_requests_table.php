<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\KidRequest;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kid_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', [
                KidRequest::TYPE_TOY,
                KidRequest::TYPE_FOOD,
                KidRequest::TYPE_PLAYGROUND,
                KidRequest::TYPE_ACTIVITY
            ]);
            $table->enum('status', [
                KidRequest::STATUS_PENDING,
                KidRequest::STATUS_APPROVED,
                KidRequest::STATUS_REJECTED,
                KidRequest::STATUS_COMPLETED
            ])->default(KidRequest::STATUS_PENDING);
            $table->timestamp('scheduled_time')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('parent_note')->nullable();
            $table->timestamps();

            $table->foreign('child_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kid_requests');
    }
};
