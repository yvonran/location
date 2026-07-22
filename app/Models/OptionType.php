<?php

namespace App\Models;

use App\Enums\AmountMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'default_mode', 'default_value', 'active'])]
class OptionType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_mode' => AmountMode::class,
            'default_value' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function quoteLineOptions(): HasMany
    {
        return $this->hasMany(QuoteLineOption::class);
    }
}
