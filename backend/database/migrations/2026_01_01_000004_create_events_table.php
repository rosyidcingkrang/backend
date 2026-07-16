<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 50);
            $table->date('event_date');
            $table->string('venue_note', 100)->nullable();
            $table->string('poster_path', 255)->nullable();
            $table->timestamps();

            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
