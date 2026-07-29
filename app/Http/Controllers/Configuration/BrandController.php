<?php

namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuration\StoreBrandRequest;
use App\Http\Requests\Configuration\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('configuration/Brands', [
            'brands' => Brand::withCount('vehicleModels')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        Brand::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marque ajoutée.']);

        return back();
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marque renommée.']);

        return back();
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->vehicleModels()->exists()) {
            return back()->withErrors([
                'brand' => 'Cette marque porte encore des modèles : supprimez-les d’abord.',
            ]);
        }

        $brand->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marque supprimée.']);

        return back();
    }
}
