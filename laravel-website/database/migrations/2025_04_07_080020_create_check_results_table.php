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
        Schema::create('check_results', function (Blueprint $table) {
            $table->id('result_id'); // Primary key
            $table->unsignedBigInteger('check_id');
            $table->foreign('check_id')->references('check_id')->on('similarity_checks')->onDelete('cascade');
            $table->string('patent_id');
            $table->float('similarity_score');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_results');
    }
};
