<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationsTableTest extends TestCase
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
            'quote_date' => now()->toDateString(), 'status' => 'accepted',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_reservations_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('reservations'));
        $this->assertTrue(Schema::hasColumns('reservations', [
            'id', 'number', 'quote_id', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_reservation_can_be_created_from_an_accepted_quote(): void
    {
        $id = DB::table('reservations')->insertGetId([
            'number' => 'RES-2026-0001',
            'quote_id' => $this->makeQuoteId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('reservations', ['id' => $id, 'number' => 'RES-2026-0001']);
    }

    public function test_reservation_number_must_be_unique(): void
    {
        $quoteId = $this->makeQuoteId();

        DB::table('reservations')->insert([
            'number' => 'RES-2026-0001', 'quote_id' => $quoteId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('reservations')->insert([
            'number' => 'RES-2026-0001', 'quote_id' => $quoteId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
