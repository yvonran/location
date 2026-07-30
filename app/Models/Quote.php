<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['number', 'customer_id', 'user_id', 'quote_date', 'status', 'subtotal', 'total', 'notes'])]
class Quote extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'status' => QuoteStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class);
    }
}
