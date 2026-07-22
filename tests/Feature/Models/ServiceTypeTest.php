<?php

namespace Tests\Feature\Models;

use App\Models\ServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_service_type_can_be_created_with_a_decimal_coefficient(): void
    {
        $serviceType = ServiceType::create([
            'name' => 'Transfert',
            'coefficient' => 2,
        ]);

        $this->assertSame('2.00', $serviceType->fresh()->coefficient);
        $this->assertTrue($serviceType->fresh()->active);
    }
}
