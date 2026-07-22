<?php

namespace Tests\Feature\Models;

use App\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_route_can_be_created_with_a_decimal_distance(): void
    {
        $route = Route::create([
            'name' => 'RN2',
            'departure_city' => 'Antananarivo',
            'arrival_city' => 'Toamasina',
            'distance_km' => 367,
        ]);

        $this->assertSame('367.00', $route->fresh()->distance_km);
    }
}
