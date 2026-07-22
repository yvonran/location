<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuoteLineOptionsTableTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuoteLineId(): int
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();
        $quoteId = DB::table('quotes')->insertGetId([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $vehicleId = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $serviceTypeId = DB::table('service_types')->insertGetId([
            'name' => 'Location', 'coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('quote_lines')->insertGetId([
            'quote_id' => $quoteId, 'vehicle_id' => $vehicleId, 'service_type_id' => $serviceTypeId,
            'start_date' => now()->toDateString(), 'number_of_days' => 3, 'distance_km' => 450.50,
            'daily_rate' => 250000, 'service_coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_quote_line_options_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('quote_line_options'));
        $this->assertTrue(Schema::hasColumns('quote_line_options', [
            'id', 'quote_line_id', 'option_type_id', 'mode', 'value', 'amount',
            'created_at', 'updated_at',
        ]));
    }

    public function test_an_option_can_be_attached_to_a_quote_line(): void
    {
        $optionTypeId = DB::table('option_types')->insertGetId([
            'name' => 'Assurance', 'default_mode' => 'percentage', 'default_value' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $id = DB::table('quote_line_options')->insertGetId([
            'quote_line_id' => $this->makeQuoteLineId(),
            'option_type_id' => $optionTypeId,
            'mode' => 'percentage',
            'value' => 10,
            'amount' => 75000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('quote_line_options', ['id' => $id, 'mode' => 'percentage']);
    }

    public function test_deleting_the_quote_line_cascades_to_its_options(): void
    {
        $quoteLineId = $this->makeQuoteLineId();
        $optionTypeId = DB::table('option_types')->insertGetId([
            'name' => 'Assurance', 'default_mode' => 'percentage', 'default_value' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $optionId = DB::table('quote_line_options')->insertGetId([
            'quote_line_id' => $quoteLineId, 'option_type_id' => $optionTypeId,
            'mode' => 'percentage', 'value' => 10, 'amount' => 75000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('quote_lines')->where('id', $quoteLineId)->delete();

        $this->assertDatabaseMissing('quote_line_options', ['id' => $optionId]);
    }
}
