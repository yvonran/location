<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'phone', 'email', 'address', 'tax_id'])]
class Customer extends Model
{
    use HasFactory, SoftDeletes;

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
