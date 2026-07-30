<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cloisonnement par compte. Seules les trois racines portent le propriétaire :
 * tout le reste en hérite par sa relation (les tarifs et conditions suivent le
 * véhicule, les lignes suivent le devis, les étapes suivent la simulation).
 *
 * Le référentiel partagé (marques, types, modèles) reste volontairement commun :
 * il est géré par le superadmin et ne contient aucune donnée client.
 */
return new class extends Migration
{
    private const TENANT_TABLES = ['customers', 'vehicles', 'simulations'];

    public function up(): void
    {
        foreach (self::TENANT_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                // Nullable : SQLite ne peut pas rendre la colonne obligatoire sans
                // reconstruire des tables porteuses de clés entrantes. Une ligne
                // sans propriétaire reste invisible, ce qui est le défaut sûr.
                $blueprint->foreignId('user_id')->nullable()->after('id')
                    ->constrained('users')->nullOnDelete();
            });
        }

        $firstUserId = DB::table('users')->orderBy('id')->value('id');

        if ($firstUserId === null) {
            return;
        }

        foreach (self::TENANT_TABLES as $table) {
            DB::table($table)->whereNull('user_id')->update(['user_id' => $firstUserId]);
        }
    }

    public function down(): void
    {
        foreach (self::TENANT_TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['user_id']);
                $blueprint->dropColumn('user_id');
            });
        }
    }
};
