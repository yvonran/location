<?php

namespace App\Models;

use App\Enums\AmountMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quote_line_id', 'option_type_id', 'mode', 'value', 'amount'])]
class QuoteLineOption extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'mode' => AmountMode::class,
            'value' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function quoteLine(): BelongsTo
    {
        return $this->belongsTo(QuoteLine::class);
    }

    public function optionType(): BelongsTo
    {
        return $this->belongsTo(OptionType::class);
    }
}
