<?php

namespace Tests\Feature\Models;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_be_created_and_retrieved(): void
    {
        $customer = Customer::create([
            'name' => 'Jean Rakoto',
            'phone' => '0341234567',
            'email' => 'jean@example.com',
            'address' => 'Antananarivo',
            'tax_id' => 'NIF123',
        ]);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Jean Rakoto']);
    }

    public function test_deleting_a_customer_soft_deletes_it(): void
    {
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);

        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
