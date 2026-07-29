<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les zones passent de 4 valeurs figées (enum + 3 seuils sur la condition) à une
 * liste libre définie par l'utilisateur : chaque zone porte son nom et sa borne
 * haute, la dernière étant ouverte.
 */
return new class extends Migration
{
    /**
     * Correspondance ancienne valeur d'enum => [libellé, colonne de seuil].
     * L'ordre fixe la position des zones reconstruites.
     */
    private const LEGACY_ZONES = [
        'city' => ['Ville', 'city_max_km'],
        'suburb' => ['Périphérie', 'suburb_max_km'],
        'long_distance' => ['Longue distance', 'long_distance_max_km'],
        'very_long_distance' => ['Très longue distance', null],
    ];

    public function up(): void
    {
        // Reliquats d'une exécution interrompue : cette migration crée ces deux tables.
        Schema::dropIfExists('rental_rates_new');
        Schema::dropIfExists('rental_zones');

        Schema::create('rental_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_condition_id')->constrained('rental_conditions')->cascadeOnDelete();
            $table->string('name');
            // null = zone ouverte : tout ce qui dépasse la zone précédente.
            $table->unsignedInteger('max_km')->nullable();
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->index(['rental_condition_id', 'position']);
        });

        $zoneIdByConditionAndKey = $this->createZonesFromThresholds();

        // SQLite ne sait pas modifier une colonne en clé étrangère non nulle :
        // on reconstruit la table plutôt que de l'altérer.
        Schema::create('rental_rates_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_zone_id')->constrained('rental_zones')->cascadeOnDelete();
            $table->unsignedSmallInteger('min_days');
            $table->unsignedSmallInteger('max_days')->nullable();
            $table->decimal('daily_rate', 12, 2);
            $table->timestamps();
            // L'index est ajouté après coup : en SQLite son nom est global à la
            // base et l'ancienne table le porte encore à cet instant.
        });

        foreach (DB::table('rental_rates')->orderBy('id')->get() as $rate) {
            $zoneId = $zoneIdByConditionAndKey[$rate->rental_condition_id][$rate->zone] ?? null;

            if ($zoneId === null) {
                continue;
            }

            DB::table('rental_rates_new')->insert([
                'rental_zone_id' => $zoneId,
                'min_days' => $rate->min_days,
                'max_days' => $rate->max_days,
                'daily_rate' => $rate->daily_rate,
                'created_at' => $rate->created_at,
                'updated_at' => $rate->updated_at,
            ]);
        }

        Schema::drop('rental_rates');
        Schema::rename('rental_rates_new', 'rental_rates');

        Schema::table('rental_rates', function (Blueprint $table) {
            $table->index(['rental_zone_id', 'min_days', 'max_days'], 'rental_rates_lookup_index');
        });

        Schema::table('rental_conditions', function (Blueprint $table) {
            $table->dropColumn(['city_max_km', 'suburb_max_km', 'long_distance_max_km']);
        });
    }

    /**
     * @return array<int, array<string, int>> zone id indexée par condition puis par ancienne clé
     */
    private function createZonesFromThresholds(): array
    {
        $zoneIds = [];

        foreach (DB::table('rental_conditions')->orderBy('id')->get() as $condition) {
            $position = 0;

            foreach (self::LEGACY_ZONES as $key => [$name, $thresholdColumn]) {
                $zoneIds[$condition->id][$key] = DB::table('rental_zones')->insertGetId([
                    'rental_condition_id' => $condition->id,
                    'name' => $name,
                    'max_km' => $thresholdColumn ? $condition->{$thresholdColumn} : null,
                    'position' => $position++,
                    'created_at' => $condition->created_at,
                    'updated_at' => $condition->updated_at,
                ]);
            }
        }

        return $zoneIds;
    }

    /**
     * Retour arrière au mieux : seules les quatre premières zones de chaque
     * condition retrouvent une place, les suivantes sont perdues.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_rates_old');

        Schema::table('rental_conditions', function (Blueprint $table) {
            foreach (['city_max_km' => 50, 'suburb_max_km' => 100, 'long_distance_max_km' => 700] as $column => $default) {
                if (! Schema::hasColumn('rental_conditions', $column)) {
                    $table->unsignedInteger($column)->default($default);
                }
            }
        });

        Schema::create('rental_rates_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_condition_id')->constrained('rental_conditions')->cascadeOnDelete();
            $table->enum('zone', array_keys(self::LEGACY_ZONES));
            $table->unsignedSmallInteger('min_days');
            $table->unsignedSmallInteger('max_days')->nullable();
            $table->decimal('daily_rate', 12, 2);
            $table->timestamps();
            // Index ajouté après la bascule : son nom est encore pris par l'autre table.
        });

        $legacyKeys = array_keys(self::LEGACY_ZONES);
        $thresholdColumns = array_column(self::LEGACY_ZONES, 1);

        foreach (DB::table('rental_conditions')->orderBy('id')->get() as $condition) {
            $zones = DB::table('rental_zones')
                ->where('rental_condition_id', $condition->id)
                ->orderBy('position')
                ->get();

            $thresholds = [];

            foreach ($zones as $index => $zone) {
                if (! isset($legacyKeys[$index])) {
                    break;
                }

                if ($thresholdColumns[$index] !== null) {
                    $thresholds[$thresholdColumns[$index]] = $zone->max_km ?? 0;
                }

                foreach (DB::table('rental_rates')->where('rental_zone_id', $zone->id)->get() as $rate) {
                    DB::table('rental_rates_old')->insert([
                        'rental_condition_id' => $condition->id,
                        'zone' => $legacyKeys[$index],
                        'min_days' => $rate->min_days,
                        'max_days' => $rate->max_days,
                        'daily_rate' => $rate->daily_rate,
                        'created_at' => $rate->created_at,
                        'updated_at' => $rate->updated_at,
                    ]);
                }
            }

            if ($thresholds !== []) {
                DB::table('rental_conditions')->where('id', $condition->id)->update($thresholds);
            }
        }

        Schema::drop('rental_rates');
        Schema::rename('rental_rates_old', 'rental_rates');

        Schema::table('rental_rates', function (Blueprint $table) {
            $table->index(['rental_condition_id', 'zone', 'min_days', 'max_days'], 'rental_rates_lookup_index');
        });

        Schema::dropIfExists('rental_zones');
    }
};
