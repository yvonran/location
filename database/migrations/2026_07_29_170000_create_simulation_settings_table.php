<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table à une seule ligne : réglages globaux utilisés par le calcul
 * automatique des simulations (carburant et repas client).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('simulation_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('fuel_price_per_liter', 12, 2);
            $table->decimal('client_meal_price', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_settings');
    }
};
