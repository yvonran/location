<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Avant ce champ, la présence d'un vehicle_type_id valait "disponible" : le
 * backfill préserve ce comportement pour les modèles déjà classés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->boolean('is_supported')->default(false)->after('vehicle_type_id');
        });

        DB::table('vehicle_models')->whereNotNull('vehicle_type_id')->update(['is_supported' => true]);
    }

    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropColumn('is_supported');
        });
    }
};
