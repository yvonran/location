<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuoteLinesTableTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuoteId(): int
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();

        return DB::table('quotes')->insertGetId([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeVehicleId(): int
    {
        return DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeServiceTypeId(): int
    {
        return DB::table('service_types')->insertGetId([
            'name' => 'Location', 'coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_quote_lines_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('quote_lines'));
        $this->assertTrue(Schema::hasColumns('quote_lines', [
            'id', 'quote_id', 'vehicle_id', 'route_id', 'service_type_id',
            'start_date', 'number_of_days', 'distance_km', 'daily_rate',
            'service_coefficient', 'discount_type', 'discount_value',
            'discount_amount', 'options_amount', 'line_total', 'position',
            'created_at', 'updated_at',
        ]));
    }

    public function test_a_quote_line_can_be_created_without_a_route(): void
    {
        $id = DB::table('quote_lines')->insertGetId([
            'quote_id' => $this->makeQuoteId(),
            'vehicle_id' => $this->makeVehicleId(),
            'route_id' => null,
            'service_type_id' => $this->makeServiceTypeId(),
            'start_date' => now()->toDateString(),
            'number_of_days' => 3,
            'distance_km' => 450.50,
            'daily_rate' => 250000,
            'service_coefficient' => 1.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('quote_lines', [
            'id' => $id,
            'route_id' => null,
            'discount_amount' => 0,
            'options_amount' => 0,
            'line_total' => 0,
            'position' => 0,
        ]);
    }

    public function test_deleting_the_quote_cascades_to_its_lines(): void
    {
        $quoteId = $this->makeQuoteId();
        $lineId = DB::table('quote_lines')->insertGetId([
            'quote_id' => $quoteId,
            'vehicle_id' => $this->makeVehicleId(),
            'service_type_id' => $this->makeServiceTypeId(),
            'start_date' => now()->toDateString(),
            'number_of_days' => 3,
            'distance_km' => 450.50,
            'daily_rate' => 250000,
            'service_coefficient' => 1.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('quotes')->where('id', $quoteId)->delete();

        $this->assertDatabaseMissing('quote_lines', ['id' => $lineId]);
    }
}
