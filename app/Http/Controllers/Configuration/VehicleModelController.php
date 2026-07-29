<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuration\StoreVehicleModelRequest;
use App\Http\Requests\Configuration\UpdateVehicleModelRequest;
use App\Models\Brand;
use App\Models\VehicleModel;
use App\Models\VehicleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleModelController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'brand_id' => $request->integer('brand_id') ?: null,
            'vehicle_type_id' => $request->input('vehicle_type_id'),
            'search' => $request->string('search')->toString() ?: null,
        ];

        $models = VehicleModel::query()
            ->with(['brand', 'vehicleType'])
            ->when($filters['brand_id'], fn ($query, $brandId) => $query->where('brand_id', $brandId))
            ->when(
                $filters['vehicle_type_id'] === 'none',
                fn ($query) => $query->whereNull('vehicle_type_id'),
                fn ($query) => $query->when(
                    $filters['vehicle_type_id'],
                    fn ($q, $typeId) => $q->where('vehicle_type_id', $typeId),
                ),
            )
            ->when($filters['search'], fn ($query, $search) => $query->where('vehicle_models.name', 'like', "%{$search}%"))
            ->join('brands', 'vehicle_models.brand_id', '=', 'brands.id')
            ->orderBy('brands.name')
            ->orderBy('vehicle_models.name')
            ->select('vehicle_models.*')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('configuration/VehicleModels', [
            'models' => $models,
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'vehicleTypes' => VehicleType::orderBy('position')->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function store(StoreVehicleModelRequest $request): RedirectResponse
    {
        VehicleModel::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modèle ajouté.']);

        return back();
    }

    public function update(UpdateVehicleModelRequest $request, VehicleModel $vehicleModel): RedirectResponse
    {
        $vehicleModel->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modèle mis à jour.']);

        return back();
    }

    public function destroy(VehicleModel $vehicleModel): RedirectResponse
    {
        if ($vehicleModel->vehicles()->withTrashed()->exists()) {
            return back()->withErrors([
                'model' => 'Ce modèle est utilisé par un véhicule : il ne peut pas être supprimé.',
            ]);
        }

        $vehicleModel->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modèle supprimé.']);

        return back();
    }
}
