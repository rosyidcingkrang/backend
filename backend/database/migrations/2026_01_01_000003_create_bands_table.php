<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 15)->unique();
            $table->string('genre', 30)->nullable();
            $table->string('description', 500)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->timestamps();
            $table->softDeletes(); // BR-3: histori slot lampau harus tetap bisa join nama band
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bands');
    }
};
