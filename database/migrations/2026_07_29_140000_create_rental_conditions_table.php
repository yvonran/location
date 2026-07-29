<?php

use App\Models\RentalCondition;
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
            // Bornes hautes du trajet aller, en km. Au-delà de la dernière, la zone est « très longue distance ».
            $table->unsignedInteger('city_max_km')->default(RentalCondition::DEFAULT_CITY_MAX_KM);
            $table->unsignedInteger('suburb_max_km')->default(RentalCondition::DEFAULT_SUBURB_MAX_KM);
            $table->unsignedInteger('long_distance_max_km')->default(RentalCondition::DEFAULT_LONG_DISTANCE_MAX_KM);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_conditions');
    }
};
