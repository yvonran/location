<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRentalConditionRequest;
use App\Models\Vehicle;
use App\Services\RentalConditionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
        DB::transaction(function () use ($request, $vehicle) {
            $condition = $vehicle->rentalCondition()->updateOrCreate([], $request->safe()->only([
                'city_max_km', 'suburb_max_km', 'long_distance_max_km',
            ]));

            // Le formulaire renvoie la grille complète : on la remplace d'un bloc.
            $condition->rentalRates()->delete();

            foreach ($request->validated('rates', []) as $rate) {
                $condition->rentalRates()->create([
                    'zone' => $rate['zone'],
                    'min_days' => $rate['min_days'],
                    'max_days' => $rate['max_days'] ?? null,
                    'daily_rate' => $rate['daily_rate'],
                ]);
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conditions de location enregistrées.']);

        // Le formulaire est présent sur la page dédiée comme sur la fiche du
        // véhicule : on renvoie l'utilisateur là où il l'a soumis.
        return back();
    }
}
