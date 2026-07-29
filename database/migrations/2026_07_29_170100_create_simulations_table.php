<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->unsignedSmallInteger('number_of_days');
            $table->time('departure_time')->nullable();
            $table->decimal('distance_km', 8, 2);
            $table->decimal('daily_rate', 12, 2);
            $table->boolean('meal_included')->default(false);
            $table->boolean('fuel_included')->default(false);
            $table->decimal('meal_cost', 12, 2)->default(0);
            $table->decimal('fuel_cost', 12, 2)->default(0);
            $table->decimal('vehicle_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulations');
    }
};
