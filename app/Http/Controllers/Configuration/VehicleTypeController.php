<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuration\StoreVehicleTypeRequest;
use App\Http\Requests\Configuration\UpdateVehicleTypeRequest;
use App\Models\VehicleType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VehicleTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('configuration/VehicleTypes', [
            'vehicleTypes' => VehicleType::withCount('vehicleModels')
                ->orderBy('position')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreVehicleTypeRequest $request): RedirectResponse
    {
        VehicleType::create([
            ...$request->validated(),
            'position' => (int) VehicleType::max('position') + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Type ajouté.']);

        return back();
    }

    public function update(UpdateVehicleTypeRequest $request, VehicleType $vehicleType): RedirectResponse
    {
        $vehicleType->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Type mis à jour.']);

        return back();
    }

    public function destroy(VehicleType $vehicleType): RedirectResponse
    {
        // Les modèles rattachés perdent leur type sans être supprimés (nullOnDelete).
        $vehicleType->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Type supprimé.']);

        return back();
    }
}
