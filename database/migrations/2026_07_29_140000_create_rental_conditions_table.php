<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->unique()->constrained('vehicles')->cascadeOnDelete();
            // Bornes hautes figées à l'origine ; rendues libres par une migration ultérieure.
            $table->unsignedInteger('city_max_km')->default(50);
            $table->unsignedInteger('suburb_max_km')->default(100);
            $table->unsignedInteger('long_distance_max_km')->default(700);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_conditions');
    }
};
