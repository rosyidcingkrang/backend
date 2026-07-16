<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 20)->unique();
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->timestamps();
            $table->softDeletes(); // BR-10: user dihapus admin wajib soft delete
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
