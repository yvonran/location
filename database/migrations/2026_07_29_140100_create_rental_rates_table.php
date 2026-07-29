<?php

use App\Enums\RentalZone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_condition_id')->constrained('rental_conditions')->cascadeOnDelete();
            $table->enum('zone', array_column(RentalZone::cases(), 'value'));
            $table->unsignedSmallInteger('min_days');
            $table->unsignedSmallInteger('max_days')->nullable();
            $table->decimal('daily_rate', 12, 2);
            $table->timestamps();

            $table->index(['rental_condition_id', 'zone', 'min_days', 'max_days'], 'rental_rates_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_rates');
    }
};
