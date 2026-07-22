# Eloquent Models (Step 2) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the 4 PHP enums and 11 Eloquent models (10 new + `User` modification) described in `docs/superpowers/specs/2026-07-22-eloquent-models-design.md`, with relations, casts, and soft deletes, on top of the tables already created in Step 1.

**Architecture:** One enum per concept in `app/Enums/`, one model per table in `app/Models/`. Because several relations are bidirectional and Eloquent relation methods require the related class to exist at call time, each "parent" model is created bare first (columns/casts only), then a later task modifies it to add the `hasMany`/`hasOne` side once the "child" model exists — both directions of a relation are always implemented and tested together, in the same task.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit-style test classes with `RefreshDatabase` (see `tests/Feature/ExampleTest.php`), SQLite in-memory for tests.

## Global Constraints

- All tables already exist (Step 1 migrations, already run). This plan creates **no migrations**.
- Models use the `#[Fillable([...])]` PHP attribute (from `Illuminate\Database\Eloquent\Attributes\Fillable`), matching the existing `app/Models/User.php` convention — not the classic `protected $fillable` property.
- Casts are declared via a `protected function casts(): array` method, matching `User.php` — not the classic `protected $casts` property.
- Enums are string-backed, in `App\Enums`, with values that match the migration's enum column values **exactly** (verified in Task 1).
- `AmountMode` (`Fixed = 'fixed'`, `Percentage = 'percentage'`) is shared by `option_types.default_mode`, `quote_lines.discount_type`, and `quote_line_options.mode` — do not create three separate enums.
- Laravel's `decimal:2` cast returns a **string** formatted to 2 decimal places (e.g. `"367.00"`), not a float. Tests must assert against strings, not numeric literals.
- `SoftDeletes` trait only on `Customer`, `Vehicle`, `Quote`, `Reservation` (matches Step 1 schema — these are the only tables with a `deleted_at` column).
- `HasFactory` trait is included on every new model even though no factory exists yet (Step 3 will add factories) — this matches Laravel's own `make:model` scaffolding and is a no-op until `::factory()` is actually called.
- No business-logic methods on models (no `calculateTotal()`, no tariff lookup, etc.) — that is Step 4's `QuoteCalculationService`.
- Tests live in `tests/Feature/Models/` (one file per model) and `tests/Unit/Enums/` (one file for all 4 enums).

---

### Task 1: PHP Enums

**Files:**
- Create: `app/Enums/VehicleStatus.php`
- Create: `app/Enums/QuoteStatus.php`
- Create: `app/Enums/AmountMode.php`
- Create: `app/Enums/ReservationLineStatus.php`
- Test: `tests/Unit/Enums/EnumsTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Enums\VehicleStatus` (cases: `Available`, `Maintenance`, `OutOfService`), `App\Enums\QuoteStatus` (cases: `Draft`, `Sent`, `Accepted`, `Rejected`), `App\Enums\AmountMode` (cases: `Fixed`, `Percentage`), `App\Enums\ReservationLineStatus` (cases: `Confirmed`, `InProgress`, `Completed`, `Cancelled`). All string-backed, consumed by every later task's model casts.

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/EnumsTest.php`
Expected: FAIL — `Class "App\Enums\VehicleStatus" not found`.

- [ ] **Step 3: Write the enums**

```php
<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case Available = 'available';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';
}
```

```php
<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
```

```php
<?php

namespace App\Enums;

enum AmountMode: string
{
    case Fixed = 'fixed';
    case Percentage = 'percentage';
}
```

```php
<?php

namespace App\Enums;

enum ReservationLineStatus: string
{
    case Confirmed = 'confirmed';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/EnumsTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Enums tests/Unit/Enums/EnumsTest.php
git commit -m "feat: add domain enums"
```

---

### Task 2: `Customer` model (base)

**Files:**
- Create: `app/Models/Customer.php`
- Test: `tests/Feature/Models/CustomerTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Models\Customer` with fillable `name`, `phone`, `email`, `address`, `tax_id`; `SoftDeletes`. No relations yet — `quotes(): HasMany` is added in Task 8 once `Quote` exists.

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/CustomerTest.php`
Expected: FAIL — `Class "App\Models\Customer" not found`.

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'phone', 'email', 'address', 'tax_id'])]
class Customer extends Model
{
    use HasFactory, SoftDeletes;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/CustomerTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/Customer.php tests/Feature/Models/CustomerTest.php
git commit -m "feat: add Customer model"
```

---

### Task 3: `Vehicle` model (base)

**Files:**
- Create: `app/Models/Vehicle.php`
- Test: `tests/Feature/Models/VehicleTest.php`

**Interfaces:**
- Consumes: `App\Enums\VehicleStatus` (Task 1).
- Produces: `App\Models\Vehicle` with fillable `name`, `brand`, `model`, `seats`, `registration_number`, `year`, `has_air_conditioning`, `status`; casts `seats`/`year` to `integer`, `has_air_conditioning` to `boolean`, `status` to `VehicleStatus`; `SoftDeletes`. No relations yet — `tariffs()` (Task 7), `quoteLines()` (Task 9), `reservationLines()` (Task 12) are added later.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Models;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_vehicle_can_be_created_with_a_default_available_status(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1',
            'brand' => 'Hyundai',
            'model' => 'Starex',
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2020,
            'has_air_conditioning' => true,
        ]);

        $this->assertSame(VehicleStatus::Available, $vehicle->fresh()->status);
        $this->assertIsInt($vehicle->fresh()->seats);
        $this->assertTrue($vehicle->fresh()->has_air_conditioning);
    }

    public function test_deleting_a_vehicle_soft_deletes_it(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);

        $vehicle->delete();

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/VehicleTest.php`
Expected: FAIL — `Class "App\Models\Vehicle" not found`.

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'brand', 'model', 'seats', 'registration_number', 'year', 'has_air_conditioning', 'status'])]
class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'year' => 'integer',
            'has_air_conditioning' => 'boolean',
            'status' => VehicleStatus::class,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/VehicleTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/Vehicle.php tests/Feature/Models/VehicleTest.php
git commit -m "feat: add Vehicle model"
```

---

### Task 4: `Route` model (base)

**Files:**
- Create: `app/Models/Route.php`
- Test: `tests/Feature/Models/RouteTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Models\Route` with fillable `name`, `departure_city`, `arrival_city`, `distance_km`, `estimated_duration_minutes`, `description`; casts `distance_km` to `decimal:2`, `estimated_duration_minutes` to `integer`. No relations yet — `quoteLines()` is added in Task 9.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Models;

use App\Models\Route;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_route_can_be_created_with_a_decimal_distance(): void
    {
        $route = Route::create([
            'name' => 'RN2',
            'departure_city' => 'Antananarivo',
            'arrival_city' => 'Toamasina',
            'distance_km' => 367,
        ]);

        $this->assertSame('367.00', $route->fresh()->distance_km);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/RouteTest.php`
Expected: FAIL — `Class "App\Models\Route" not found`.

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'departure_city', 'arrival_city', 'distance_km', 'estimated_duration_minutes', 'description'])]
class Route extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/RouteTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/Route.php tests/Feature/Models/RouteTest.php
git commit -m "feat: add Route model"
```

---

### Task 5: `ServiceType` model (base)

**Files:**
- Create: `app/Models/ServiceType.php`
- Test: `tests/Feature/Models/ServiceTypeTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Models\ServiceType` with fillable `name`, `coefficient`, `description`, `active`; casts `coefficient` to `decimal:2`, `active` to `boolean`. No relations yet — `quoteLines()` is added in Task 9.

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/ServiceTypeTest.php`
Expected: FAIL — `Class "App\Models\ServiceType" not found`.

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'coefficient', 'description', 'active'])]
class ServiceType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'coefficient' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/ServiceTypeTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/ServiceType.php tests/Feature/Models/ServiceTypeTest.php
git commit -m "feat: add ServiceType model"
```

---

### Task 6: `OptionType` model (base)

**Files:**
- Create: `app/Models/OptionType.php`
- Test: `tests/Feature/Models/OptionTypeTest.php`

**Interfaces:**
- Consumes: `App\Enums\AmountMode` (Task 1).
- Produces: `App\Models\OptionType` with fillable `name`, `default_mode`, `default_value`, `active`; casts `default_mode` to `AmountMode`, `default_value` to `decimal:2`, `active` to `boolean`. No relations yet — `quoteLineOptions()` is added in Task 10.

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/OptionTypeTest.php`
Expected: FAIL — `Class "App\Models\OptionType" not found`.

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use App\Enums\AmountMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'default_mode', 'default_value', 'active'])]
class OptionType extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'default_mode' => AmountMode::class,
            'default_value' => 'decimal:2',
            'active' => 'boolean',
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/OptionTypeTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/OptionType.php tests/Feature/Models/OptionTypeTest.php
git commit -m "feat: add OptionType model"
```

---

### Task 7: `Tariff` model + `Vehicle::tariffs()`

**Files:**
- Create: `app/Models/Tariff.php`
- Modify: `app/Models/Vehicle.php`
- Test: `tests/Feature/Models/TariffTest.php`

**Interfaces:**
- Consumes: `App\Models\Vehicle` (Task 3).
- Produces: `App\Models\Tariff` with fillable `vehicle_id`, `min_distance_km`, `max_distance_km`, `min_days`, `max_days`, `daily_rate`; casts all `*_km`/`*_days` to `integer`, `daily_rate` to `decimal:2`; `vehicle(): BelongsTo`. `Vehicle::tariffs(): HasMany` added.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Models;

use App\Models\Tariff;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TariffTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tariff_belongs_to_a_vehicle_and_the_vehicle_lists_its_tariffs(): void
    {
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);

        $tariff = Tariff::create([
            'vehicle_id' => $vehicle->id,
            'min_distance_km' => 0,
            'max_distance_km' => 799,
            'min_days' => 1,
            'max_days' => 5,
            'daily_rate' => 250000,
        ]);

        $this->assertTrue($tariff->vehicle->is($vehicle));
        $this->assertTrue($vehicle->tariffs->contains($tariff));
        $this->assertSame('250000.00', $tariff->fresh()->daily_rate);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/TariffTest.php`
Expected: FAIL — `Class "App\Models\Tariff" not found`.

- [ ] **Step 3: Write the model and update Vehicle**

Create `app/Models/Tariff.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_id', 'min_distance_km', 'max_distance_km', 'min_days', 'max_days', 'daily_rate'])]
class Tariff extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'min_distance_km' => 'integer',
            'max_distance_km' => 'integer',
            'min_days' => 'integer',
            'max_days' => 'integer',
            'daily_rate' => 'decimal:2',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
```

In `app/Models/Vehicle.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and add this method inside the class body:

```php
    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/TariffTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/Tariff.php app/Models/Vehicle.php tests/Feature/Models/TariffTest.php
git commit -m "feat: add Tariff model and Vehicle::tariffs relation"
```

---

### Task 8: `Quote` model + `Customer::quotes()` + `User::quotes()`

**Files:**
- Create: `app/Models/Quote.php`
- Modify: `app/Models/Customer.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Models/QuoteTest.php`

**Interfaces:**
- Consumes: `App\Models\Customer` (Task 2), `App\Models\User` (existing), `App\Enums\QuoteStatus` (Task 1).
- Produces: `App\Models\Quote` with fillable `number`, `customer_id`, `user_id`, `quote_date`, `status`, `subtotal`, `total`, `notes`; casts `quote_date` to `date`, `status` to `QuoteStatus`, `subtotal`/`total` to `decimal:2`; `SoftDeletes`; `customer(): BelongsTo`, `user(): BelongsTo`. `Customer::quotes(): HasMany` and `User::quotes(): HasMany` added. `Quote::reservation()` is **not** added here — it is added in Task 11 once `Reservation` exists.

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/QuoteTest.php`
Expected: FAIL — `Class "App\Models\Quote" not found`.

- [ ] **Step 3: Write the model and update Customer and User**

Create `app/Models/Quote.php`:

```php
<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['number', 'customer_id', 'user_id', 'quote_date', 'status', 'subtotal', 'total', 'notes'])]
class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'status' => QuoteStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

In `app/Models/Customer.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and add this method inside the class body:

```php
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
```

In `app/Models/User.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and add this method inside the class body:

```php
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/QuoteTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/Quote.php app/Models/Customer.php app/Models/User.php tests/Feature/Models/QuoteTest.php
git commit -m "feat: add Quote model and Customer/User quotes relations"
```

---

### Task 9: `QuoteLine` model + `Quote::quoteLines()` + `Vehicle::quoteLines()` + `Route::quoteLines()` + `ServiceType::quoteLines()`

**Files:**
- Create: `app/Models/QuoteLine.php`
- Modify: `app/Models/Quote.php`
- Modify: `app/Models/Vehicle.php`
- Modify: `app/Models/Route.php`
- Modify: `app/Models/ServiceType.php`
- Test: `tests/Feature/Models/QuoteLineTest.php`

**Interfaces:**
- Consumes: `App\Models\Quote` (Task 8), `App\Models\Vehicle` (Task 3), `App\Models\Route` (Task 4), `App\Models\ServiceType` (Task 5), `App\Enums\AmountMode` (Task 1).
- Produces: `App\Models\QuoteLine` with fillable `quote_id`, `vehicle_id`, `route_id`, `service_type_id`, `start_date`, `number_of_days`, `distance_km`, `daily_rate`, `service_coefficient`, `discount_type`, `discount_value`, `discount_amount`, `options_amount`, `line_total`, `position`; casts `start_date` to `date`, `number_of_days`/`position` to `integer`, `distance_km`/`daily_rate`/`service_coefficient`/`discount_value`/`discount_amount`/`options_amount`/`line_total` to `decimal:2`, `discount_type` to nullable `AmountMode`; `quote(): BelongsTo`, `vehicle(): BelongsTo`, `route(): BelongsTo` (nullable), `serviceType(): BelongsTo`. `Quote::quoteLines()`, `Vehicle::quoteLines()`, `Route::quoteLines()`, `ServiceType::quoteLines()` (`HasMany`) added.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Models;

use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\Customer;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteLineTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuote(): Quote
    {
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();

        return Quote::create([
            'number' => 'QUO-2026-0001', 'customer_id' => $customer->id,
            'user_id' => $user->id, 'quote_date' => '2026-07-22',
        ]);
    }

    private function makeVehicle(): Vehicle
    {
        return Vehicle::create([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
    }

    private function makeServiceType(): ServiceType
    {
        return ServiceType::create(['name' => 'Location', 'coefficient' => 1]);
    }

    public function test_a_quote_line_can_be_created_without_a_route_and_all_sides_resolve(): void
    {
        $quote = $this->makeQuote();
        $vehicle = $this->makeVehicle();
        $serviceType = $this->makeServiceType();

        $line = QuoteLine::create([
            'quote_id' => $quote->id,
            'vehicle_id' => $vehicle->id,
            'route_id' => null,
            'service_type_id' => $serviceType->id,
            'start_date' => '2026-08-01',
            'number_of_days' => 3,
            'distance_km' => 450.5,
            'daily_rate' => 250000,
            'service_coefficient' => 1,
        ]);

        $this->assertTrue($line->quote->is($quote));
        $this->assertTrue($line->vehicle->is($vehicle));
        $this->assertTrue($line->serviceType->is($serviceType));
        $this->assertNull($line->route);
        $this->assertTrue($quote->quoteLines->contains($line));
        $this->assertTrue($vehicle->quoteLines->contains($line));
        $this->assertTrue($serviceType->quoteLines->contains($line));
        $this->assertNull($line->fresh()->discount_type);
        $this->assertSame('450.50', $line->fresh()->distance_km);
    }

    public function test_a_quote_line_with_a_route_and_a_discount_type_resolves_both(): void
    {
        $route = Route::create([
            'name' => 'RN2', 'departure_city' => 'Antananarivo',
            'arrival_city' => 'Toamasina', 'distance_km' => 367,
        ]);

        $line = QuoteLine::create([
            'quote_id' => $this->makeQuote()->id,
            'vehicle_id' => $this->makeVehicle()->id,
            'route_id' => $route->id,
            'service_type_id' => $this->makeServiceType()->id,
            'start_date' => '2026-08-01',
            'number_of_days' => 3,
            'distance_km' => $route->distance_km,
            'daily_rate' => 250000,
            'service_coefficient' => 1,
            'discount_type' => \App\Enums\AmountMode::Percentage,
            'discount_value' => 10,
        ]);

        $this->assertTrue($line->route->is($route));
        $this->assertTrue($route->quoteLines->contains($line));
        $this->assertSame(\App\Enums\AmountMode::Percentage, $line->fresh()->discount_type);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/QuoteLineTest.php`
Expected: FAIL — `Class "App\Models\QuoteLine" not found`.

- [ ] **Step 3: Write the model and update Quote, Vehicle, Route, ServiceType**

Create `app/Models/QuoteLine.php`:

```php
<?php

namespace App\Models;

use App\Enums\AmountMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'quote_id', 'vehicle_id', 'route_id', 'service_type_id', 'start_date',
    'number_of_days', 'distance_km', 'daily_rate', 'service_coefficient',
    'discount_type', 'discount_value', 'discount_amount', 'options_amount',
    'line_total', 'position',
])]
class QuoteLine extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'number_of_days' => 'integer',
            'distance_km' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'service_coefficient' => 'decimal:2',
            'discount_type' => AmountMode::class,
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'options_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }
}
```

In `app/Models/Quote.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and add:

```php
    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }
```

In `app/Models/Vehicle.php`, add:

```php
    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }
```

(The `HasMany` import already exists in `Vehicle.php` from Task 7.)

In `app/Models/Route.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and add:

```php
    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }
```

In `app/Models/ServiceType.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and add:

```php
    public function quoteLines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/QuoteLineTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/QuoteLine.php app/Models/Quote.php app/Models/Vehicle.php app/Models/Route.php app/Models/ServiceType.php tests/Feature/Models/QuoteLineTest.php
git commit -m "feat: add QuoteLine model and its relations"
```

---

### Task 10: `QuoteLineOption` model + `QuoteLine::quoteLineOptions()` + `OptionType::quoteLineOptions()`

**Files:**
- Create: `app/Models/QuoteLineOption.php`
- Modify: `app/Models/QuoteLine.php`
- Modify: `app/Models/OptionType.php`
- Test: `tests/Feature/Models/QuoteLineOptionTest.php`

**Interfaces:**
- Consumes: `App\Models\QuoteLine` (Task 9), `App\Models\OptionType` (Task 6), `App\Enums\AmountMode` (Task 1).
- Produces: `App\Models\QuoteLineOption` with fillable `quote_line_id`, `option_type_id`, `mode`, `value`, `amount`; casts `mode` to `AmountMode`, `value`/`amount` to `decimal:2`; `quoteLine(): BelongsTo`, `optionType(): BelongsTo`. `QuoteLine::quoteLineOptions()` and `OptionType::quoteLineOptions()` (`HasMany`) added.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Models;

use App\Enums\AmountMode;
use App\Models\Customer;
use App\Models\OptionType;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\QuoteLineOption;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteLineOptionTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuoteLine(): QuoteLine
    {
        $customer = Customer::create(['name' => 'Jean Rakoto', 'phone' => '0341234567']);
        $user = User::factory()->create();
        $quote = Quote::create([
            'number' => 'QUO-2026-0001', 'customer_id' => $customer->id,
            'user_id' => $user->id, 'quote_date' => '2026-07-22',
        ]);
        $vehicle = Vehicle::create([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true,
        ]);
        $serviceType = ServiceType::create(['name' => 'Location', 'coefficient' => 1]);

        return QuoteLine::create([
            'quote_id' => $quote->id, 'vehicle_id' => $vehicle->id,
            'service_type_id' => $serviceType->id, 'start_date' => '2026-08-01',
            'number_of_days' => 3, 'distance_km' => 450.5,
            'daily_rate' => 250000, 'service_coefficient' => 1,
        ]);
    }

    public function test_an_option_belongs_to_a_quote_line_and_an_option_type_who_both_list_it(): void
    {
        $line = $this->makeQuoteLine();
        $optionType = OptionType::create([
            'name' => 'Assurance', 'default_mode' => AmountMode::Percentage, 'default_value' => 10,
        ]);

        $option = QuoteLineOption::create([
            'quote_line_id' => $line->id,
            'option_type_id' => $optionType->id,
            'mode' => AmountMode::Percentage,
            'value' => 10,
            'amount' => 75000,
        ]);

        $this->assertTrue($option->quoteLine->is($line));
        $this->assertTrue($option->optionType->is($optionType));
        $this->assertTrue($line->quoteLineOptions->contains($option));
        $this->assertTrue($optionType->quoteLineOptions->contains($option));
        $this->assertSame(AmountMode::Percentage, $option->fresh()->mode);
        $this->assertSame('75000.00', $option->fresh()->amount);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/QuoteLineOptionTest.php`
Expected: FAIL — `Class "App\Models\QuoteLineOption" not found`.

- [ ] **Step 3: Write the model and update QuoteLine and OptionType**

Create `app/Models/QuoteLineOption.php`:

```php
<?php

namespace App\Models;

use App\Enums\AmountMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['quote_line_id', 'option_type_id', 'mode', 'value', 'amount'])]
class QuoteLineOption extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'mode' => AmountMode::class,
            'value' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function quoteLine(): BelongsTo
    {
        return $this->belongsTo(QuoteLine::class);
    }

    public function optionType(): BelongsTo
    {
        return $this->belongsTo(OptionType::class);
    }
}
```

In `app/Models/QuoteLine.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and add:

```php
    public function quoteLineOptions(): HasMany
    {
        return $this->hasMany(QuoteLineOption::class);
    }
```

In `app/Models/OptionType.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and add:

```php
    public function quoteLineOptions(): HasMany
    {
        return $this->hasMany(QuoteLineOption::class);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/QuoteLineOptionTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/QuoteLineOption.php app/Models/QuoteLine.php app/Models/OptionType.php tests/Feature/Models/QuoteLineOptionTest.php
git commit -m "feat: add QuoteLineOption model and its relations"
```

---

### Task 11: `Reservation` model + `Quote::reservation()`

**Files:**
- Create: `app/Models/Reservation.php`
- Modify: `app/Models/Quote.php`
- Test: `tests/Feature/Models/ReservationTest.php`

**Interfaces:**
- Consumes: `App\Models\Quote` (Task 8).
- Produces: `App\Models\Reservation` with fillable `number`, `quote_id`; `quote(): BelongsTo`. `Quote::reservation(): HasOne` added. `Reservation::reservationLines()` is **not** added here — it is added in Task 12 once `ReservationLine` exists.

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/ReservationTest.php`
Expected: FAIL — `Class "App\Models\Reservation" not found`.

- [ ] **Step 3: Write the model and update Quote**

Create `app/Models/Reservation.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['number', 'quote_id'])]
class Reservation extends Model
{
    use HasFactory;

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
```

In `app/Models/Quote.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasOne;` and add:

```php
    public function reservation(): HasOne
    {
        return $this->hasOne(Reservation::class);
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/ReservationTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/Reservation.php app/Models/Quote.php tests/Feature/Models/ReservationTest.php
git commit -m "feat: add Reservation model and Quote::reservation relation"
```

---

### Task 12: `ReservationLine` model + `Reservation::reservationLines()` + `Vehicle::reservationLines()`

**Files:**
- Create: `app/Models/ReservationLine.php`
- Modify: `app/Models/Reservation.php`
- Modify: `app/Models/Vehicle.php`
- Test: `tests/Feature/Models/ReservationLineTest.php`

**Interfaces:**
- Consumes: `App\Models\Reservation` (Task 11), `App\Models\QuoteLine` (Task 9), `App\Models\Vehicle` (Task 3), `App\Enums\ReservationLineStatus` (Task 1).
- Produces: `App\Models\ReservationLine` with fillable `reservation_id`, `quote_line_id`, `vehicle_id`, `start_date`, `end_date`, `status`; casts `start_date`/`end_date` to `date`, `status` to `ReservationLineStatus`; `reservation(): BelongsTo`, `quoteLine(): BelongsTo`, `vehicle(): BelongsTo`. `Reservation::reservationLines()` and `Vehicle::reservationLines()` (`HasMany`) added. This is the final task of Step 2 — every relation from the spec now exists on both sides.

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Models/ReservationLineTest.php`
Expected: FAIL — `Class "App\Models\ReservationLine" not found`.

- [ ] **Step 3: Write the model and update Reservation and Vehicle**

Create `app/Models/ReservationLine.php`:

```php
<?php

namespace App\Models;

use App\Enums\ReservationLineStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['reservation_id', 'quote_line_id', 'vehicle_id', 'start_date', 'end_date', 'status'])]
class ReservationLine extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ReservationLineStatus::class,
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function quoteLine(): BelongsTo
    {
        return $this->belongsTo(QuoteLine::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
```

In `app/Models/Reservation.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and add:

```php
    public function reservationLines(): HasMany
    {
        return $this->hasMany(ReservationLine::class);
    }
```

In `app/Models/Vehicle.php`, add:

```php
    public function reservationLines(): HasMany
    {
        return $this->hasMany(ReservationLine::class);
    }
```

(The `HasMany` import already exists in `Vehicle.php` from Task 7.)

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Models/ReservationLineTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Models/ReservationLine.php app/Models/Reservation.php app/Models/Vehicle.php tests/Feature/Models/ReservationLineTest.php
git commit -m "feat: add ReservationLine model and its relations"
```

---

### Task 13: Full-suite sanity check

**Files:** none created or modified.

**Interfaces:**
- Consumes: all models and enums from Tasks 1–12.
- Produces: nothing (verification-only task).

- [ ] **Step 1: Run the entire test suite**

Run: `php artisan test`
Expected: PASS — all tests from Step 1 (migrations) and Step 2 (models) pass together.

- [ ] **Step 2: Commit (only if Step 1 required fixes)**

If no fixes were needed, skip this step — there is nothing to commit. Otherwise:

```bash
git add -A
git commit -m "fix: resolve issues found in full-suite check"
```
