<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('draft_patent_images', function (Blueprint $table) {
            $table->id('image_id'); // primary key
            $table->unsignedBigInteger('draft_id'); // foreign key
            $table->integer('idx')->default(0); // urutan
            $table->text('file'); // path/url gambar
            $table->timestamps();

            $table->foreign('draft_id')->references('draft_id')->on('draft_patents')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_patent_images');
    }
};
