<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->unsignedInteger('min_distance_km');
            $table->unsignedInteger('max_distance_km')->nullable();
            $table->unsignedSmallInteger('min_days');
            $table->unsignedSmallInteger('max_days')->nullable();
            $table->decimal('daily_rate', 12, 2);
            $table->timestamps();

            $table->index(['vehicle_id', 'min_distance_km', 'max_distance_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
