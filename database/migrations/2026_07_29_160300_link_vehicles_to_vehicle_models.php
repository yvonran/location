<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les colonnes texte `brand` et `model` du véhicule laissent place à un lien
 * vers un modèle du référentiel, lui-même rattaché à une marque.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Nullable au niveau base : SQLite ne sait pas rendre une colonne non
            // nulle sans reconstruire la table, qui porte plusieurs clés entrantes.
            // La présence est imposée à la validation.
            $table->foreignId('vehicle_model_id')->nullable()->after('name')
                ->constrained('vehicle_models')->nullOnDelete();
        });

        $this->linkExistingVehicles();

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['brand', 'model']);
        });
    }

    private function linkExistingVehicles(): void
    {
        $vehicles = DB::table('vehicles')->select('id', 'brand', 'model')->get();

        foreach ($vehicles as $vehicle) {
            $brandName = trim((string) $vehicle->brand);
            $modelName = trim((string) $vehicle->model);

            if ($brandName === '' || $modelName === '') {
                continue;
            }

            $brandId = DB::table('brands')->where('name', $brandName)->value('id')
                ?? DB::table('brands')->insertGetId([
                    'name' => $brandName, 'created_at' => now(), 'updated_at' => now(),
                ]);

            $modelId = DB::table('vehicle_models')
                ->where('brand_id', $brandId)->where('name', $modelName)->value('id')
                ?? DB::table('vehicle_models')->insertGetId([
                    'brand_id' => $brandId, 'vehicle_type_id' => null, 'name' => $modelName,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

            DB::table('vehicles')->where('id', $vehicle->id)->update(['vehicle_model_id' => $modelId]);
        }
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('brand')->default('');
            $table->string('model')->default('');
        });

        $rows = DB::table('vehicles')
            ->join('vehicle_models', 'vehicles.vehicle_model_id', '=', 'vehicle_models.id')
            ->join('brands', 'vehicle_models.brand_id', '=', 'brands.id')
            ->select('vehicles.id', 'brands.name as brand_name', 'vehicle_models.name as model_name')
            ->get();

        foreach ($rows as $row) {
            DB::table('vehicles')->where('id', $row->id)->update([
                'brand' => $row->brand_name,
                'model' => $row->model_name,
            ]);
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('vehicle_model_id');
        });
    }
};
