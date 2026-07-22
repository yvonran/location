<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomersTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('customers'));
        $this->assertTrue(Schema::hasColumns('customers', [
            'id', 'name', 'phone', 'email', 'address', 'tax_id',
            'created_at', 'updated_at', 'deleted_at',
        ]));
    }

    public function test_a_customer_can_be_created_with_only_required_fields(): void
    {
        $id = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto',
            'phone' => '0341234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $id,
            'name' => 'Jean Rakoto',
            'email' => null,
            'address' => null,
            'tax_id' => null,
        ]);
    }
}
