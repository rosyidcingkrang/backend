<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code', 20)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('performance_slot_id')->constrained('performance_slots')->cascadeOnDelete();
            $table->unsignedTinyInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['paid'])->default('paid');
            $table->timestamp('purchased_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('performance_slot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
