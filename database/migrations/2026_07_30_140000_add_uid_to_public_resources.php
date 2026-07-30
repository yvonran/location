<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Identifiant public utilisé dans les URL, à la place de l'identifiant
 * séquentiel : /simulations/1 devenait /simulations/2 en devinant, et laissait
 * deviner le volume de données.
 *
 * La clé primaire entière est conservée : elle porte toutes les clés
 * étrangères, seul l'affichage change.
 */
return new class extends Migration
{
    private const TABLES = ['vehicles', 'simulations', 'quotes'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->ulid('uid')->nullable()->after('id');
            });

            foreach (DB::table($table)->select('id')->get() as $row) {
                DB::table($table)->where('id', $row->id)->update(['uid' => (string) Str::ulid()]);
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unique('uid', "{$table}_uid_unique");
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropUnique("{$table}_uid_unique");
                $blueprint->dropColumn('uid');
            });
        }
    }
};
