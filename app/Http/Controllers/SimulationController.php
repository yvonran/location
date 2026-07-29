<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Exceptions\NoTariffFoundException;
use App\Http\Requests\StoreSimulationRequest;
use App\Models\Simulation;
use App\Models\Vehicle;
use App\Services\SimulationCalculationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SimulationController extends Controller
{
    public function __construct(private readonly SimulationCalculationService $simulationCalculationService) {}

    public function index(): Response
    {
        return Inertia::render('simulations/Index', [
            'simulations' => Simulation::with('vehicle')->latest()->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('simulations/Create', [
            'vehicles' => Vehicle::withIdentity()->where('status', VehicleStatus::Available)
                ->orderBy('name')->get(['id', 'name', 'vehicle_model_id']),
        ]);
    }

    public function store(StoreSimulationRequest $request): RedirectResponse
    {
        try {
            $simulation = $this->simulationCalculationService->createSimulation($request->validated());
        } catch (NoTariffFoundException $exception) {
            return back()->withErrors(['legs' => $exception->getMessage()])->withInput();
        }

        return to_route('simulations.show', $simulation);
    }

    public function show(Simulation $simulation): Response
    {
        $simulation->load(['vehicle.vehicleModel.brand', 'legs' => fn ($query) => $query->orderBy('position')]);

        return Inertia::render('simulations/Show', [
            'simulation' => $simulation,
        ]);
    }
}
