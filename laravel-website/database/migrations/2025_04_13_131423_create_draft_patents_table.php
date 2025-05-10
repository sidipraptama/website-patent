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
        Schema::create('draft_patents', function (Blueprint $table) {
            $table->id('draft_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('check_id')->unique();
            $table->text('title')->nullable();
            $table->text('technical_field')->nullable();
            $table->text('background')->nullable();
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->text('claims')->nullable();
            $table->text('abstract')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('check_id')->references('check_id')->on('similarity_checks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_patents');
    }
};
