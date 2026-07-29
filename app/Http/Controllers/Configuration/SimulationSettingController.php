<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuration\UpdateSimulationSettingRequest;
use App\Models\SimulationSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SimulationSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('configuration/SimulationSettings', [
            'setting' => SimulationSetting::current(),
        ]);
    }

    public function update(UpdateSimulationSettingRequest $request): RedirectResponse
    {
        SimulationSetting::current()->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Réglages enregistrés.']);

        return back();
    }
}
