<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['simulation_id', 'position', 'from_point', 'to_point', 'distance_km'])]
class SimulationLeg extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'distance_km' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Simulation, $this>
     */
    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }
}
