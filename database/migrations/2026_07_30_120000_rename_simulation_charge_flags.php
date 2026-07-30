<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les deux drapeaux de la simulation changent de point de vue : ils disaient
 * « l'agence absorbe le coût », ils disent maintenant « le client le paie ».
 * Le sens s'inverse, donc les valeurs déjà enregistrées sont retournées pour
 * que les simulations passées gardent exactement le même coût.
 *
 * À ne pas confondre avec `rental_rates.meal_included`, qui reste inchangé et
 * signifie « le tarif journalier comprend déjà le repas du chauffeur ».
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('simulations', function (Blueprint $table) {
            $table->renameColumn('meal_included', 'meal_charged_to_client');
            $table->renameColumn('fuel_included', 'fuel_charged_to_client');
        });

        DB::table('simulations')->update([
            'meal_charged_to_client' => DB::raw('NOT meal_charged_to_client'),
            'fuel_charged_to_client' => DB::raw('NOT fuel_charged_to_client'),
        ]);

        // Le repas facturé est celui du chauffeur, pas celui du client.
        Schema::table('simulation_settings', function (Blueprint $table) {
            $table->renameColumn('client_meal_price', 'driver_meal_price');
        });
    }

    public function down(): void
    {
        Schema::table('simulation_settings', function (Blueprint $table) {
            $table->renameColumn('driver_meal_price', 'client_meal_price');
        });

        DB::table('simulations')->update([
            'meal_charged_to_client' => DB::raw('NOT meal_charged_to_client'),
            'fuel_charged_to_client' => DB::raw('NOT fuel_charged_to_client'),
        ]);

        Schema::table('simulations', function (Blueprint $table) {
            $table->renameColumn('meal_charged_to_client', 'meal_included');
            $table->renameColumn('fuel_charged_to_client', 'fuel_included');
        });
    }
};
