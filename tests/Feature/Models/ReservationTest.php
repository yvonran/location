<?php

namespace Tests\Feature\Models;

use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reservation_belongs_to_a_quote_and_the_quote_resolves_it(): void
    {
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();
        $quote = Quote::create([
            'number' => 'QUO-2026-0001', 'customer_id' => $customer->id,
            'user_id' => $user->id, 'quote_date' => '2026-07-22',
            'status' => QuoteStatus::Accepted,
        ]);

        $reservation = Reservation::create([
            'number' => 'RES-2026-0001',
            'quote_id' => $quote->id,
        ]);

        $this->assertTrue($reservation->quote->is($quote));
        $this->assertTrue($quote->reservation->is($reservation));
    }
}
