<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained('simulations')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('from_point');
            $table->string('to_point');
            $table->decimal('distance_km', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_legs');
    }
};
