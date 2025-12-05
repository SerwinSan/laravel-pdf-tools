<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('file_histories', function (Blueprint $table) {
        $table->id();
        $table->string('original_name'); // Nama file asli (atau ringkasannya)
        $table->string('action_type');   // MERGE, SPLIT, atau IMAGE
        $table->string('result_path');   // Lokasi file hasil disimpan
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_histories');
    }
};
