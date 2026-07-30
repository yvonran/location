<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Rules\OwnedRecord;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', new OwnedRecord(Customer::class)],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.vehicle_id' => ['required', 'integer', new OwnedRecord(Vehicle::class)],
            'lines.*.route_id' => ['nullable', 'integer', 'exists:routes,id'],
            'lines.*.distance_km' => ['required_without:lines.*.route_id', 'nullable', 'numeric', 'min:0'],
            'lines.*.service_type_id' => ['required', 'integer', 'exists:service_types,id'],
            'lines.*.start_date' => ['required', 'date'],
            'lines.*.departure_time' => ['nullable', 'date_format:H:i'],
            'lines.*.number_of_days' => ['required', 'integer', 'min:1'],
            'lines.*.discount_type' => ['nullable', 'in:fixed,percentage'],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines.*.options' => ['array'],
            'lines.*.options.*.option_type_id' => ['required', 'integer', 'exists:option_types,id'],
            'lines.*.options.*.mode' => ['nullable', 'in:fixed,percentage'],
            'lines.*.options.*.value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
