<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colonnes ajoutées pour la cohérence de schéma avec rental_rates : Tariff
 * n'a pas d'interface d'administration, ces champs ne sont donc ni exposés
 * ni exploités dans le calcul du devis pour l'instant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->boolean('meal_included')->default(false)->after('daily_rate');
            $table->decimal('meal_price', 12, 2)->nullable()->after('meal_included');
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn(['meal_included', 'meal_price']);
        });
    }
};
