<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Exceptions\NoTariffFoundException;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Customer;
use App\Models\OptionType;
use App\Models\Quote;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\Vehicle;
use App\Services\QuoteCalculationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    public function __construct(private readonly QuoteCalculationService $quoteCalculationService) {}

    public function index(): Response
    {
        return Inertia::render('quotes/Index', [
            'quotes' => Quote::with('customer')->latest('quote_date')->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('quotes/Create', [
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'vehicles' => Vehicle::withIdentity()->where('status', VehicleStatus::Available)
                ->orderBy('name')->get(['id', 'name', 'vehicle_model_id']),
            'routes' => Route::orderBy('name')->get(['id', 'name', 'departure_city', 'arrival_city', 'distance_km']),
            'serviceTypes' => ServiceType::where('active', true)->orderBy('name')->get(['id', 'name', 'coefficient']),
            'optionTypes' => OptionType::where('active', true)->orderBy('name')->get(['id', 'name', 'default_mode', 'default_value']),
        ]);
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        try {
            $quote = $this->quoteCalculationService->createQuote(
                $request->integer('customer_id'),
                $request->user()->id,
                $request->input('lines'),
                $request->input('notes'),
            );
        } catch (NoTariffFoundException $exception) {
            return back()->withErrors(['lines' => $exception->getMessage()])->withInput();
        }

        return to_route('quotes.show', $quote);
    }

    public function show(Quote $quote): Response
    {
        $quote->load([
            'customer', 'user',
            'quoteLines.vehicle.vehicleModel.brand', 'quoteLines.route', 'quoteLines.serviceType',
            'quoteLines.quoteLineOptions.optionType',
        ]);

        return Inertia::render('quotes/Show', [
            'quote' => $quote,
        ]);
    }
}
