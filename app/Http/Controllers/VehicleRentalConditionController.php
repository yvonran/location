<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRentalConditionRequest;
use App\Models\Vehicle;
use App\Services\RentalConditionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VehicleRentalConditionController extends Controller
{
    public function __construct(private readonly RentalConditionService $rentalConditionService) {}

    public function edit(Vehicle $vehicle): Response
    {
        return Inertia::render('vehicles/RentalCondition', [
            'vehicle' => $vehicle->only(['id', 'name', 'brand', 'model', 'registration_number']),
            ...$this->rentalConditionService->editorProps($vehicle),
        ]);
    }

    public function update(UpdateRentalConditionRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->rentalConditionService->replaceZones($vehicle, $request->validated('zones', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conditions de location enregistrées.']);

        // Le formulaire est présent sur la page dédiée comme sur la fiche du
        // véhicule : on renvoie l'utilisateur là où il l'a soumis.
        return back();
    }
}
