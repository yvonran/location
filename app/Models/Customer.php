<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'name', 'phone', 'email', 'address', 'tax_id'])]
class Customer extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
