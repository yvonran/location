<?php

namespace App\Http\Controllers;

use App\Enums\RentalZone;
use App\Http\Requests\UpdateRentalConditionRequest;
use App\Models\RentalCondition;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VehicleRentalConditionController extends Controller
{
    public function edit(Vehicle $vehicle): Response
    {
        $condition = $vehicle->rentalCondition ?? new RentalCondition;

        return Inertia::render('vehicles/RentalCondition', [
            'vehicle' => $vehicle->only(['id', 'name', 'brand', 'model', 'registration_number']),
            'condition' => [
                'city_max_km' => $condition->city_max_km,
                'suburb_max_km' => $condition->suburb_max_km,
                'long_distance_max_km' => $condition->long_distance_max_km,
            ],
            'rates' => $condition->exists
                ? $condition->rentalRates()->orderBy('zone')->orderBy('min_days')->get()
                : [],
            'zones' => array_map(
                fn (RentalZone $zone) => ['value' => $zone->value, 'label' => $zone->label()],
                RentalZone::cases(),
            ),
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

        return to_route('vehicles.index');
    }
}
