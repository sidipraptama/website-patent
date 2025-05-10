<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('update_history', function (Blueprint $table) {
            $table->id('update_history_id');
            $table->integer('status')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('update_logs', function (Blueprint $table) {
            $table->id('update_log_id');
            $table->unsignedBigInteger('update_history_id');
            $table->text('message');
            $table->timestamps();

            $table->foreign('update_history_id')->references('update_history_id')->on('update_history')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('update_logs');
        Schema::dropIfExists('update_history');
    }
};
