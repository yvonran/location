<?php

namespace Tests\Feature\Models;

use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_quote_belongs_to_a_customer_and_a_user_who_both_list_it(): void
    {
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();

        $quote = Quote::create([
            'number' => 'QUO-2026-0001',
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'quote_date' => '2026-07-22',
        ]);

        $this->assertTrue($quote->customer->is($customer));
        $this->assertTrue($quote->user->is($user));
        $this->assertTrue($customer->quotes->contains($quote));
        $this->assertTrue($user->quotes->contains($quote));
        $this->assertSame(QuoteStatus::Draft, $quote->fresh()->status);
        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $quote->fresh()->quote_date);
    }

    public function test_deleting_a_quote_soft_deletes_it(): void
    {
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();
        $quote = Quote::create([
            'number' => 'QUO-2026-0001', 'customer_id' => $customer->id,
            'user_id' => $user->id, 'quote_date' => '2026-07-22',
        ]);

        $quote->delete();

        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);
    }
}
