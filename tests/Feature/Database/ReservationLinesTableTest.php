<?php

namespace Tests\Feature\Database;

use App\Models\User;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationLinesTableTest extends TestCase
{
    use RefreshDatabase;

    private function makeReservationAndDependencies(): array
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();
        $quoteId = DB::table('quotes')->insertGetId([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'status' => 'accepted',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $vehicleId = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'vehicle_model_id' => VehicleModel::factory()->create()->id,
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $serviceTypeId = DB::table('service_types')->insertGetId([
            'name' => 'Location', 'coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $quoteLineId = DB::table('quote_lines')->insertGetId([
            'quote_id' => $quoteId, 'vehicle_id' => $vehicleId, 'service_type_id' => $serviceTypeId,
            'start_date' => now()->toDateString(), 'number_of_days' => 3, 'distance_km' => 450.50,
            'daily_rate' => 250000, 'service_coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $reservationId = DB::table('reservations')->insertGetId([
            'number' => 'RES-2026-0001', 'quote_id' => $quoteId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return compact('reservationId', 'quoteLineId', 'vehicleId');
    }

    public function test_reservation_lines_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('reservation_lines'));
        $this->assertTrue(Schema::hasColumns('reservation_lines', [
            'id', 'reservation_id', 'quote_line_id', 'vehicle_id',
            'start_date', 'end_date', 'status', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_reservation_line_defaults_to_confirmed_status(): void
    {
        $deps = $this->makeReservationAndDependencies();

        $id = DB::table('reservation_lines')->insertGetId([
            'reservation_id' => $deps['reservationId'],
            'quote_line_id' => $deps['quoteLineId'],
            'vehicle_id' => $deps['vehicleId'],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('reservation_lines', ['id' => $id, 'status' => 'confirmed']);
    }

    public function test_deleting_the_reservation_cascades_to_its_lines(): void
    {
        $deps = $this->makeReservationAndDependencies();

        $lineId = DB::table('reservation_lines')->insertGetId([
            'reservation_id' => $deps['reservationId'],
            'quote_line_id' => $deps['quoteLineId'],
            'vehicle_id' => $deps['vehicleId'],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reservations')->where('id', $deps['reservationId'])->delete();

        $this->assertDatabaseMissing('reservation_lines', ['id' => $lineId]);
    }
}
