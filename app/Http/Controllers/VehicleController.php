<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use App\Services\RentalConditionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('vehicles/Index', [
            'vehicles' => Vehicle::withIdentity()->orderBy('name')->paginate(15),
        ]);
    }

    public function create(RentalConditionService $rentalConditionService): Response
    {
        return Inertia::render('vehicles/Create', [
            'statuses' => $this->statuses(),
            ...$this->referenceData(),
            ...$rentalConditionService->editorProps(),
        ]);
    }

    public function store(StoreVehicleRequest $request, RentalConditionService $rentalConditionService): RedirectResponse
    {
        $attributes = $request->safe()->except(['image', 'zones']);

        if ($request->hasFile('image')) {
            $attributes['image_path'] = $request->file('image')->store('vehicles', 'public');
        }

        // La fiche et ses zones forment un tout : ni l'une ni les autres seules.
        DB::transaction(function () use ($attributes, $request, $rentalConditionService) {
            $vehicle = Vehicle::create($attributes);

            $rentalConditionService->replaceZones($vehicle, $request->validated('zones', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Véhicule ajouté.']);

        return to_route('vehicles.index');
    }

    public function edit(Vehicle $vehicle, RentalConditionService $rentalConditionService): Response
    {
        return Inertia::render('vehicles/Edit', [
            'vehicle' => $vehicle->loadMissing('vehicleModel.brand', 'vehicleModel.vehicleType'),
            'statuses' => $this->statuses(),
            ...$this->referenceData(),
            ...$rentalConditionService->editorProps($vehicle),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $attributes = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $this->deleteImage($vehicle);
            $attributes['image_path'] = $request->file('image')->store('vehicles', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($vehicle);
            $attributes['image_path'] = null;
        }

        $vehicle->update($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Véhicule mis à jour.']);

        return to_route('vehicles.index');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Véhicule supprimé.']);

        return to_route('vehicles.index');
    }

    /**
     * Référentiel servant les listes déroulantes : le type filtre les modèles,
     * et le modèle porte sa marque.
     *
     * @return array{vehicleModels: mixed, vehicleTypes: mixed}
     */
    private function referenceData(): array
    {
        return [
            'vehicleModels' => VehicleModel::query()
                ->with('brand:id,name')
                ->orderBy('name')
                ->get(['id', 'brand_id', 'vehicle_type_id', 'name']),
            'vehicleTypes' => VehicleType::orderBy('position')->orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statuses(): array
    {
        $labels = [
            VehicleStatus::Available->value => 'Disponible',
            VehicleStatus::Maintenance->value => 'En maintenance',
            VehicleStatus::OutOfService->value => 'Hors service',
        ];

        return array_map(
            fn (VehicleStatus $status) => ['value' => $status->value, 'label' => $labels[$status->value]],
            VehicleStatus::cases(),
        );
    }

    private function deleteImage(Vehicle $vehicle): void
    {
        if ($vehicle->image_path) {
            Storage::disk('public')->delete($vehicle->image_path);
        }
    }
}
