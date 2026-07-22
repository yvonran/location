<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
