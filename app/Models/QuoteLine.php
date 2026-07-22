<?php

namespace App\Models;

use App\Enums\AmountMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'quote_id', 'vehicle_id', 'route_id', 'service_type_id', 'start_date',
    'number_of_days', 'distance_km', 'daily_rate', 'service_coefficient',
    'discount_type', 'discount_value', 'discount_amount', 'options_amount',
    'line_total', 'position',
])]
class QuoteLine extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'number_of_days' => 'integer',
            'distance_km' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'service_coefficient' => 'decimal:2',
            'discount_type' => AmountMode::class,
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'options_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function quoteLineOptions(): HasMany
    {
        return $this->hasMany(QuoteLineOption::class);
    }
}
