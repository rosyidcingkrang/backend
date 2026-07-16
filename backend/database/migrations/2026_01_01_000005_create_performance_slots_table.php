<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('band_id')->constrained('bands');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('stock_total');
            $table->unsignedSmallInteger('stock_sold')->default(0);
            $table->timestamps();

            // BR-9: band tidak dobel di jam sama pada event sama
            $table->unique(['event_id', 'band_id', 'start_time']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_slots');
    }
};
