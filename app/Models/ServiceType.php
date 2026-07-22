<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'coefficient', 'description', 'active'])]
class ServiceType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'coefficient' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }
}
