<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotes_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('quotes'));
        $this->assertTrue(Schema::hasColumns('quotes', [
            'id', 'number', 'customer_id', 'user_id', 'quote_date', 'status',
            'subtotal', 'total', 'notes', 'created_at', 'updated_at', 'deleted_at',
        ]));
    }

    public function test_a_quote_defaults_to_draft_status_and_zero_totals(): void
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();

        $id = DB::table('quotes')->insertGetId([
            'number' => 'QUO-2026-0001',
            'customer_id' => $customerId,
            'user_id' => $user->id,
            'quote_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('quotes', [
            'id' => $id,
            'status' => 'draft',
            'subtotal' => 0,
            'total' => 0,
        ]);
    }

    public function test_quote_number_must_be_unique(): void
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();

        DB::table('quotes')->insert([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('quotes')->insert([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
