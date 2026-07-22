<?php

namespace Tests\Feature\Models;

use App\Enums\AmountMode;
use App\Models\OptionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_option_type_casts_its_mode_to_an_enum(): void
    {
        $optionType = OptionType::create([
            'name' => 'Assurance',
            'default_mode' => AmountMode::Percentage,
            'default_value' => 10,
        ]);

        $this->assertSame(AmountMode::Percentage, $optionType->fresh()->default_mode);
        $this->assertSame('10.00', $optionType->fresh()->default_value);
    }
}
