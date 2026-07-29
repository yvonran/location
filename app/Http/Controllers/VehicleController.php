<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('vehicles/Index', [
            'vehicles' => Vehicle::orderBy('name')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('vehicles/Create', [
            'statuses' => $this->statuses(),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $attributes = $request->safe()->except('image');

        if ($request->hasFile('image')) {
            $attributes['image_path'] = $request->file('image')->store('vehicles', 'public');
        }

        Vehicle::create($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Véhicule ajouté.']);

        return to_route('vehicles.index');
    }

    public function edit(Vehicle $vehicle): Response
    {
        return Inertia::render('vehicles/Edit', [
            'vehicle' => $vehicle,
            'statuses' => $this->statuses(),
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
