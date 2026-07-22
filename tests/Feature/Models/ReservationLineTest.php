<?php

namespace Tests\Feature\Models;

use App\Enums\QuoteStatus;
use App\Enums\ReservationLineStatus;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Reservation;
use App\Models\ReservationLine;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationLineTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reservation_line_resolves_all_its_relations_with_a_default_confirmed_status(): void
    {
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();
        $quote = Quote::create([
            'number' => 'QUO-2026-0001', 'customer_id' => $customer->id,
            'user_id' => $user->id, 'quote_date' => '2026-07-22',
            'status' => QuoteStatus::Accepted,
        ]);
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
        $serviceType = ServiceType::create(['name' => 'Location', 'coefficient' => 1]);
        $quoteLine = QuoteLine::create([
            'quote_id' => $quote->id, 'vehicle_id' => $vehicle->id,
            'service_type_id' => $serviceType->id, 'start_date' => '2026-08-01',
            'number_of_days' => 3, 'distance_km' => 450.5,
            'daily_rate' => 250000, 'service_coefficient' => 1,
        ]);
        $reservation = Reservation::create(['number' => 'RES-2026-0001', 'quote_id' => $quote->id]);

        $line = ReservationLine::create([
            'reservation_id' => $reservation->id,
            'quote_line_id' => $quoteLine->id,
            'vehicle_id' => $vehicle->id,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-04',
        ]);

        $this->assertTrue($line->reservation->is($reservation));
        $this->assertTrue($line->quoteLine->is($quoteLine));
        $this->assertTrue($line->vehicle->is($vehicle));
        $this->assertTrue($reservation->reservationLines->contains($line));
        $this->assertTrue($vehicle->reservationLines->contains($line));
        $this->assertSame(ReservationLineStatus::Confirmed, $line->fresh()->status);
        $this->assertInstanceOf(\Carbon\CarbonInterface::class, $line->fresh()->end_date);
    }
}
