<?php

namespace Tests\Unit\Enums;

use App\Enums\AmountMode;
use App\Enums\QuoteStatus;
use App\Enums\ReservationLineStatus;
use App\Enums\VehicleStatus;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_vehicle_status_values_match_the_database_enum(): void
    {
        $this->assertSame('available', VehicleStatus::Available->value);
        $this->assertSame('maintenance', VehicleStatus::Maintenance->value);
        $this->assertSame('out_of_service', VehicleStatus::OutOfService->value);
    }

    public function test_quote_status_values_match_the_database_enum(): void
    {
        $this->assertSame('draft', QuoteStatus::Draft->value);
        $this->assertSame('sent', QuoteStatus::Sent->value);
        $this->assertSame('accepted', QuoteStatus::Accepted->value);
        $this->assertSame('rejected', QuoteStatus::Rejected->value);
    }

    public function test_amount_mode_values_match_the_database_enum(): void
    {
        $this->assertSame('fixed', AmountMode::Fixed->value);
        $this->assertSame('percentage', AmountMode::Percentage->value);
    }

    public function test_reservation_line_status_values_match_the_database_enum(): void
    {
        $this->assertSame('confirmed', ReservationLineStatus::Confirmed->value);
        $this->assertSame('in_progress', ReservationLineStatus::InProgress->value);
        $this->assertSame('completed', ReservationLineStatus::Completed->value);
        $this->assertSame('cancelled', ReservationLineStatus::Cancelled->value);
    }
}
