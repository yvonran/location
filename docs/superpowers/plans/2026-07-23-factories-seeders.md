# Factories and Seeders (Step 3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the 11 Eloquent factories and the seeders described in `docs/superpowers/specs/2026-07-23-factories-seeders-design.md` (roles/users, customers, vehicles + tariffs, routes, service types, option types), on top of the models and migrations already built in Steps 1–2.

**Architecture:** One factory per model in `database/factories/`, grouped into tasks by FK dependency wave (same pattern as Step 2). Seeders in `database/seeders/`, one class per concern, each wired into `DatabaseSeeder::run()` via `$this->call([...])` in dependency order. Seeder tests instantiate the seeder class directly (`(new XSeeder())->run()`) rather than using Laravel's `$this->seed()` helper, so a missing seeder class fails the test with a clean, predictable "Class not found" error.

**Tech Stack:** Laravel 13, PHP 8.4, PHPUnit-style test classes with `RefreshDatabase`, Faker (via the `fake()` helper), SQLite in-memory for tests.

## Global Constraints

- All models and migrations already exist (Steps 1–2, already run). This plan creates **no migrations** and **no new models**.
- Factories use `protected $model = X::class;` and a `definition(): array` method, using the `fake()` helper (not `$this->faker`).
- Unique columns (`vehicles.registration_number`, `quotes.number`, `reservations.number`) are generated with `fake()->unique()` in factories to avoid collisions across multiple `::factory()->count(n)->create()` calls.
- The exact seed values (route list, service type coefficients, option type defaults, tariff grids) are copied verbatim from the spec — no invented placeholders beyond what the spec already specifies.
- `User.password` uses the model's existing `'password' => 'hashed'` cast — seeders assign a plain-text string (e.g. `'password'`) and let the cast hash it; never call `bcrypt()`/`Hash::make()` manually.
- Tests live in `tests/Feature/Factories/` (one file per task group) and `tests/Feature/Seeders/` (one file per seeder task).

---

### Task 1: Base factories — `Customer`, `Vehicle`, `Route`, `ServiceType`, `OptionType`

**Files:**
- Create: `database/factories/CustomerFactory.php`
- Create: `database/factories/VehicleFactory.php`
- Create: `database/factories/RouteFactory.php`
- Create: `database/factories/ServiceTypeFactory.php`
- Create: `database/factories/OptionTypeFactory.php`
- Test: `tests/Feature/Factories/BaseFactoriesTest.php`

**Interfaces:**
- Consumes: `App\Models\Customer`, `App\Models\Vehicle`, `App\Models\Route`, `App\Models\ServiceType`, `App\Models\OptionType` (Step 2), `App\Enums\VehicleStatus`, `App\Enums\AmountMode` (Step 2).
- Produces: `Customer::factory()`, `Vehicle::factory()`, `Route::factory()`, `ServiceType::factory()`, `OptionType::factory()`, all usable by later tasks.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Factories;

use App\Models\Customer;
use App\Models\OptionType;
use App\Models\Route;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_factory_creates_a_valid_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_vehicle_factory_can_create_many_without_unique_collisions(): void
    {
        $vehicles = Vehicle::factory()->count(5)->create();

        $this->assertCount(5, $vehicles);
        $this->assertCount(5, $vehicles->pluck('registration_number')->unique());
    }

    public function test_route_factory_creates_a_valid_route(): void
    {
        $route = Route::factory()->create();

        $this->assertDatabaseHas('routes', ['id' => $route->id]);
    }

    public function test_service_type_factory_creates_a_valid_service_type(): void
    {
        $serviceType = ServiceType::factory()->create();

        $this->assertDatabaseHas('service_types', ['id' => $serviceType->id]);
    }

    public function test_option_type_factory_creates_a_valid_option_type(): void
    {
        $optionType = OptionType::factory()->create();

        $this->assertDatabaseHas('option_types', ['id' => $optionType->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Factories/BaseFactoriesTest.php`
Expected: FAIL — Laravel cannot resolve a factory class for `Customer` (no `database/factories/CustomerFactory.php` exists yet), and likewise for the other four models.

- [ ] **Step 3: Write the factories**

Create `database/factories/CustomerFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '03'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->city(),
            'tax_id' => fake()->optional()->numerify('NIF#######'),
        ];
    }
}
```

Create `database/factories/VehicleFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'brand' => fake()->randomElement(['Toyota', 'Hyundai', 'Nissan', 'Mitsubishi']),
            'model' => fake()->word(),
            'seats' => fake()->numberBetween(4, 30),
            'registration_number' => fake()->unique()->numerify('####').' '.fake()->randomElement(['TBA', 'TBT', 'TBM']),
            'year' => fake()->numberBetween(2015, 2026),
            'has_air_conditioning' => fake()->boolean(80),
            'status' => VehicleStatus::Available,
        ];
    }
}
```

Create `database/factories/RouteFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Route>
 */
class RouteFactory extends Factory
{
    protected $model = Route::class;

    public function definition(): array
    {
        return [
            'name' => 'RN'.fake()->unique()->numberBetween(60, 999),
            'departure_city' => fake()->city(),
            'arrival_city' => fake()->city(),
            'distance_km' => fake()->numberBetween(20, 900),
            'estimated_duration_minutes' => fake()->numberBetween(30, 900),
            'description' => null,
        ];
    }
}
```

Create `database/factories/ServiceTypeFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    protected $model = ServiceType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'coefficient' => fake()->randomFloat(2, 1, 2),
            'description' => null,
            'active' => true,
        ];
    }
}
```

Create `database/factories/OptionTypeFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Enums\AmountMode;
use App\Models\OptionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OptionType>
 */
class OptionTypeFactory extends Factory
{
    protected $model = OptionType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'default_mode' => fake()->randomElement(AmountMode::cases()),
            'default_value' => fake()->randomFloat(2, 5000, 100000),
            'active' => true,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Factories/BaseFactoriesTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/factories/CustomerFactory.php database/factories/VehicleFactory.php database/factories/RouteFactory.php database/factories/ServiceTypeFactory.php database/factories/OptionTypeFactory.php tests/Feature/Factories/BaseFactoriesTest.php
git commit -m "feat: add base model factories"
```

---

### Task 2: `TariffFactory`

**Files:**
- Create: `database/factories/TariffFactory.php`
- Test: `tests/Feature/Factories/TariffFactoryTest.php`

**Interfaces:**
- Consumes: `App\Models\Tariff` (Step 2), `Vehicle::factory()` (Task 1).
- Produces: `Tariff::factory()`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Factories;

use App\Models\Tariff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TariffFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_tariff_factory_creates_a_valid_tariff_with_its_vehicle(): void
    {
        $tariff = Tariff::factory()->create();

        $this->assertDatabaseHas('tariffs', ['id' => $tariff->id]);
        $this->assertNotNull($tariff->vehicle);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Factories/TariffFactoryTest.php`
Expected: FAIL — Laravel cannot resolve a factory class for `Tariff`.

- [ ] **Step 3: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\Tariff;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tariff>
 */
class TariffFactory extends Factory
{
    protected $model = Tariff::class;

    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'min_distance_km' => 0,
            'max_distance_km' => 799,
            'min_days' => 1,
            'max_days' => 5,
            'daily_rate' => fake()->randomFloat(2, 100000, 400000),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Factories/TariffFactoryTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/factories/TariffFactory.php tests/Feature/Factories/TariffFactoryTest.php
git commit -m "feat: add Tariff factory"
```

---

### Task 3: `QuoteFactory`

**Files:**
- Create: `database/factories/QuoteFactory.php`
- Test: `tests/Feature/Factories/QuoteFactoryTest.php`

**Interfaces:**
- Consumes: `App\Models\Quote` (Step 2), `Customer::factory()` (Task 1), `App\Models\User` (existing, already has a factory).
- Produces: `Quote::factory()`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Factories;

use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_factory_can_create_many_without_unique_collisions(): void
    {
        $quotes = Quote::factory()->count(5)->create();

        $this->assertCount(5, $quotes);
        $this->assertCount(5, $quotes->pluck('number')->unique());
        $this->assertNotNull($quotes->first()->customer);
        $this->assertNotNull($quotes->first()->user);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Factories/QuoteFactoryTest.php`
Expected: FAIL — Laravel cannot resolve a factory class for `Quote`.

- [ ] **Step 3: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    public function definition(): array
    {
        return [
            'number' => 'QUO-'.fake()->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'quote_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'status' => QuoteStatus::Draft,
            'subtotal' => 0,
            'total' => 0,
            'notes' => null,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Factories/QuoteFactoryTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/factories/QuoteFactory.php tests/Feature/Factories/QuoteFactoryTest.php
git commit -m "feat: add Quote factory"
```

---

### Task 4: `QuoteLineFactory`

**Files:**
- Create: `database/factories/QuoteLineFactory.php`
- Test: `tests/Feature/Factories/QuoteLineFactoryTest.php`

**Interfaces:**
- Consumes: `App\Models\QuoteLine` (Step 2), `Quote::factory()` (Task 3), `Vehicle::factory()` (Task 1), `ServiceType::factory()` (Task 1).
- Produces: `QuoteLine::factory()`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Factories;

use App\Models\QuoteLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteLineFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_line_factory_creates_a_valid_line_without_a_route(): void
    {
        $line = QuoteLine::factory()->create();

        $this->assertDatabaseHas('quote_lines', ['id' => $line->id]);
        $this->assertNull($line->route_id);
        $this->assertNotNull($line->quote);
        $this->assertNotNull($line->vehicle);
        $this->assertNotNull($line->serviceType);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Factories/QuoteLineFactoryTest.php`
Expected: FAIL — Laravel cannot resolve a factory class for `QuoteLine`.

- [ ] **Step 3: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteLine>
 */
class QuoteLineFactory extends Factory
{
    protected $model = QuoteLine::class;

    public function definition(): array
    {
        return [
            'quote_id' => Quote::factory(),
            'vehicle_id' => Vehicle::factory(),
            'route_id' => null,
            'service_type_id' => ServiceType::factory(),
            'start_date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'number_of_days' => fake()->numberBetween(1, 15),
            'distance_km' => fake()->randomFloat(2, 50, 900),
            'daily_rate' => fake()->randomFloat(2, 100000, 400000),
            'service_coefficient' => 1,
            'discount_type' => null,
            'discount_value' => null,
            'discount_amount' => 0,
            'options_amount' => 0,
            'line_total' => 0,
            'position' => 0,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Factories/QuoteLineFactoryTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/factories/QuoteLineFactory.php tests/Feature/Factories/QuoteLineFactoryTest.php
git commit -m "feat: add QuoteLine factory"
```

---

### Task 5: `QuoteLineOptionFactory`

**Files:**
- Create: `database/factories/QuoteLineOptionFactory.php`
- Test: `tests/Feature/Factories/QuoteLineOptionFactoryTest.php`

**Interfaces:**
- Consumes: `App\Models\QuoteLineOption` (Step 2), `QuoteLine::factory()` (Task 4), `OptionType::factory()` (Task 1).
- Produces: `QuoteLineOption::factory()`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Factories;

use App\Models\QuoteLineOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteLineOptionFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_line_option_factory_creates_a_valid_option(): void
    {
        $option = QuoteLineOption::factory()->create();

        $this->assertDatabaseHas('quote_line_options', ['id' => $option->id]);
        $this->assertNotNull($option->quoteLine);
        $this->assertNotNull($option->optionType);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Factories/QuoteLineOptionFactoryTest.php`
Expected: FAIL — Laravel cannot resolve a factory class for `QuoteLineOption`.

- [ ] **Step 3: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Enums\AmountMode;
use App\Models\OptionType;
use App\Models\QuoteLine;
use App\Models\QuoteLineOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteLineOption>
 */
class QuoteLineOptionFactory extends Factory
{
    protected $model = QuoteLineOption::class;

    public function definition(): array
    {
        return [
            'quote_line_id' => QuoteLine::factory(),
            'option_type_id' => OptionType::factory(),
            'mode' => AmountMode::Fixed,
            'value' => fake()->randomFloat(2, 10000, 100000),
            'amount' => fake()->randomFloat(2, 10000, 100000),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Factories/QuoteLineOptionFactoryTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/factories/QuoteLineOptionFactory.php tests/Feature/Factories/QuoteLineOptionFactoryTest.php
git commit -m "feat: add QuoteLineOption factory"
```

---

### Task 6: `ReservationFactory`

**Files:**
- Create: `database/factories/ReservationFactory.php`
- Test: `tests/Feature/Factories/ReservationFactoryTest.php`

**Interfaces:**
- Consumes: `App\Models\Reservation` (Step 2), `Quote::factory()` (Task 3).
- Produces: `Reservation::factory()`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Factories;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_factory_can_create_many_without_unique_collisions(): void
    {
        $reservations = Reservation::factory()->count(5)->create();

        $this->assertCount(5, $reservations);
        $this->assertCount(5, $reservations->pluck('number')->unique());
        $this->assertNotNull($reservations->first()->quote);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Factories/ReservationFactoryTest.php`
Expected: FAIL — Laravel cannot resolve a factory class for `Reservation`.

- [ ] **Step 3: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\Quote;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        return [
            'number' => 'RES-'.fake()->unique()->numerify('######'),
            'quote_id' => Quote::factory(),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Factories/ReservationFactoryTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/factories/ReservationFactory.php tests/Feature/Factories/ReservationFactoryTest.php
git commit -m "feat: add Reservation factory"
```

---

### Task 7: `ReservationLineFactory`

**Files:**
- Create: `database/factories/ReservationLineFactory.php`
- Test: `tests/Feature/Factories/ReservationLineFactoryTest.php`

**Interfaces:**
- Consumes: `App\Models\ReservationLine` (Step 2), `Reservation::factory()` (Task 6), `QuoteLine::factory()` (Task 4), `Vehicle::factory()` (Task 1).
- Produces: `ReservationLine::factory()`. This is the final factory task — all 11 models now have a factory.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Factories;

use App\Models\ReservationLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationLineFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_line_factory_creates_a_valid_line(): void
    {
        $line = ReservationLine::factory()->create();

        $this->assertDatabaseHas('reservation_lines', ['id' => $line->id]);
        $this->assertNotNull($line->reservation);
        $this->assertNotNull($line->quoteLine);
        $this->assertNotNull($line->vehicle);
        $this->assertTrue($line->end_date->greaterThan($line->start_date));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Factories/ReservationLineFactoryTest.php`
Expected: FAIL — Laravel cannot resolve a factory class for `ReservationLine`.

- [ ] **Step 3: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Enums\ReservationLineStatus;
use App\Models\QuoteLine;
use App\Models\Reservation;
use App\Models\ReservationLine;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationLine>
 */
class ReservationLineFactory extends Factory
{
    protected $model = ReservationLine::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+2 months');
        $endDate = (clone $startDate)->modify('+3 days');

        return [
            'reservation_id' => Reservation::factory(),
            'quote_line_id' => QuoteLine::factory(),
            'vehicle_id' => Vehicle::factory(),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'status' => ReservationLineStatus::Confirmed,
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Factories/ReservationLineFactoryTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/factories/ReservationLineFactory.php tests/Feature/Factories/ReservationLineFactoryTest.php
git commit -m "feat: add ReservationLine factory"
```

---

### Task 8: `RoleSeeder` + `UserSeeder`

**Files:**
- Create: `database/seeders/RoleSeeder.php`
- Create: `database/seeders/UserSeeder.php`
- Test: `tests/Feature/Seeders/RoleAndUserSeederTest.php`

**Interfaces:**
- Consumes: `Spatie\Permission\Models\Role`, `App\Models\User` (existing, with `HasRoles`).
- Produces: `Database\Seeders\RoleSeeder`, `Database\Seeders\UserSeeder`. Creates roles `admin`/`agent` and users `admin@agence.mg`/`agent@agence.mg` with those roles assigned.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Seeders;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndUserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_default_users_are_seeded_with_correct_role_assignments(): void
    {
        (new RoleSeeder())->run();
        (new UserSeeder())->run();

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'agent']);

        $admin = User::where('email', 'admin@agence.mg')->first();
        $agent = User::where('email', 'agent@agence.mg')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertNotNull($agent);
        $this->assertTrue($agent->hasRole('agent'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Seeders/RoleAndUserSeederTest.php`
Expected: FAIL — `Class "Database\Seeders\RoleSeeder" not found`.

- [ ] **Step 3: Write the seeders**

Create `database/seeders/RoleSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
    }
}
```

Create `database/seeders/UserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@agence.mg'],
            ['name' => 'Administrateur', 'password' => 'password']
        );
        $admin->assignRole('admin');

        $agent = User::firstOrCreate(
            ['email' => 'agent@agence.mg'],
            ['name' => 'Agent Commercial', 'password' => 'password']
        );
        $agent->assignRole('agent');
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Seeders/RoleAndUserSeederTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/seeders/RoleSeeder.php database/seeders/UserSeeder.php tests/Feature/Seeders/RoleAndUserSeederTest.php
git commit -m "feat: add role and user seeders"
```

---

### Task 9: `CustomerSeeder`

**Files:**
- Create: `database/seeders/CustomerSeeder.php`
- Test: `tests/Feature/Seeders/CustomerSeederTest.php`

**Interfaces:**
- Consumes: `App\Models\Customer` (Step 2), `Customer::factory()` (Task 1).
- Produces: `Database\Seeders\CustomerSeeder`. Creates 3 named customers + 10 via factory (13 total).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\CustomerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_named_customers_and_factory_customers(): void
    {
        (new CustomerSeeder())->run();

        $this->assertDatabaseCount('customers', 13);
        $this->assertDatabaseHas('customers', ['name' => 'Hery Rakotondrabe']);
        $this->assertDatabaseHas('customers', ['name' => 'Voahangy Rasoanaivo']);
        $this->assertDatabaseHas('customers', ['name' => 'Société Malagasy Trans']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Seeders/CustomerSeederTest.php`
Expected: FAIL — `Class "Database\Seeders\CustomerSeeder" not found`.

- [ ] **Step 3: Write the seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::create([
            'name' => 'Hery Rakotondrabe',
            'phone' => '0341112233',
            'email' => 'hery.rakoto@example.mg',
            'address' => 'Analakely, Antananarivo',
            'tax_id' => 'NIF0012345',
        ]);

        Customer::create([
            'name' => 'Voahangy Rasoanaivo',
            'phone' => '0331234567',
            'email' => 'voahangy.rasoanaivo@example.mg',
            'address' => 'Isotry, Antananarivo',
            'tax_id' => 'NIF0067890',
        ]);

        Customer::create([
            'name' => 'Société Malagasy Trans',
            'phone' => '0209411122',
            'email' => 'contact@malagasytrans.mg',
            'address' => 'Zone Industrielle, Toamasina',
            'tax_id' => 'NIF0099887',
        ]);

        Customer::factory()->count(10)->create();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Seeders/CustomerSeederTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/seeders/CustomerSeeder.php tests/Feature/Seeders/CustomerSeederTest.php
git commit -m "feat: add customer seeder"
```

---

### Task 10: `VehicleSeeder`

**Files:**
- Create: `database/seeders/VehicleSeeder.php`
- Test: `tests/Feature/Seeders/VehicleSeederTest.php`

**Interfaces:**
- Consumes: `App\Models\Vehicle` (Step 2), `App\Enums\VehicleStatus` (Step 2), `Vehicle::factory()` (Task 1).
- Produces: `Database\Seeders\VehicleSeeder`. Creates 4 named vehicles (`1234 TBA`, `5678 TBB`, `9012 TBC`, `3456 TBD`) + 3 via factory (7 total). Later consumed by Task 13 (`TariffSeeder`), which looks up vehicles by these exact `registration_number` values.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\VehicleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_named_vehicles_and_factory_vehicles(): void
    {
        (new VehicleSeeder())->run();

        $this->assertDatabaseCount('vehicles', 7);
        $this->assertDatabaseHas('vehicles', ['registration_number' => '1234 TBA', 'name' => 'Starex 1']);
        $this->assertDatabaseHas('vehicles', ['registration_number' => '5678 TBB', 'name' => 'Land Cruiser 1']);
        $this->assertDatabaseHas('vehicles', ['registration_number' => '9012 TBC', 'name' => 'Corolla 1']);
        $this->assertDatabaseHas('vehicles', ['registration_number' => '3456 TBD', 'name' => 'Coaster 1']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Seeders/VehicleSeederTest.php`
Expected: FAIL — `Class "Database\Seeders\VehicleSeeder" not found`.

- [ ] **Step 3: Write the seeder**

```php
<?php

namespace Database\Seeders;

use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        Vehicle::create([
            'name' => 'Starex 1',
            'brand' => 'Hyundai',
            'model' => 'Starex',
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2020,
            'has_air_conditioning' => true,
            'status' => VehicleStatus::Available,
        ]);

        Vehicle::create([
            'name' => 'Land Cruiser 1',
            'brand' => 'Toyota',
            'model' => 'Land Cruiser',
            'seats' => 7,
            'registration_number' => '5678 TBB',
            'year' => 2019,
            'has_air_conditioning' => true,
            'status' => VehicleStatus::Available,
        ]);

        Vehicle::create([
            'name' => 'Corolla 1',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'seats' => 4,
            'registration_number' => '9012 TBC',
            'year' => 2021,
            'has_air_conditioning' => true,
            'status' => VehicleStatus::Available,
        ]);

        Vehicle::create([
            'name' => 'Coaster 1',
            'brand' => 'Toyota',
            'model' => 'Coaster',
            'seats' => 28,
            'registration_number' => '3456 TBD',
            'year' => 2018,
            'has_air_conditioning' => true,
            'status' => VehicleStatus::Available,
        ]);

        Vehicle::factory()->count(3)->create();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Seeders/VehicleSeederTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/seeders/VehicleSeeder.php tests/Feature/Seeders/VehicleSeederTest.php
git commit -m "feat: add vehicle seeder"
```

---

### Task 11: `RouteSeeder`

**Files:**
- Create: `database/seeders/RouteSeeder.php`
- Test: `tests/Feature/Seeders/RouteSeederTest.php`

**Interfaces:**
- Consumes: `App\Models\Route` (Step 2).
- Produces: `Database\Seeders\RouteSeeder`. Creates exactly 38 routes (RN1–RN55, excluding the 3 with unknown distance).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Seeders;

use Database\Seeders\RouteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_38_known_national_routes(): void
    {
        (new RouteSeeder())->run();

        $this->assertDatabaseCount('routes', 38);
        $this->assertDatabaseHas('routes', ['name' => 'RN2', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Toamasina', 'distance_km' => 367]);
        $this->assertDatabaseHas('routes', ['name' => 'RN7', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Toliara', 'distance_km' => 956]);
        $this->assertDatabaseMissing('routes', ['name' => 'RNT19']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Seeders/RouteSeederTest.php`
Expected: FAIL — `Class "Database\Seeders\RouteSeeder" not found`.

- [ ] **Step 3: Write the seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Route;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $routes = [
            ['name' => 'RN1', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Analavory', 'distance_km' => 110, 'description' => 'Antananarivo → Arivonimamo → Miarinarivo → Analavory'],
            ['name' => 'RN1A', 'departure_city' => 'Tsiroanomandidy', 'arrival_city' => 'Maintirano', 'distance_km' => 405, 'description' => 'Tsiroanomandidy → Maintirano'],
            ['name' => 'RN1b', 'departure_city' => 'Analavory', 'arrival_city' => 'Tsiroanomandidy', 'distance_km' => 110, 'description' => 'Analavory → Ankadinondry Sakay → Tsiroanomandidy'],
            ['name' => 'RN2', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Toamasina', 'distance_km' => 367, 'description' => 'Antananarivo → Moramanga → Brickaville → Toamasina'],
            ['name' => 'RN3', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Lac Alaotra', 'distance_km' => 91, 'description' => 'Antananarivo → Anjozorobe → Lac Alaotra'],
            ['name' => 'RN3a', 'departure_city' => 'Lac Alaotra', 'arrival_city' => 'Andilamena', 'distance_km' => 180, 'description' => 'Lac Alaotra → Andilamena'],
            ['name' => 'RN3b', 'departure_city' => 'Sambava', 'arrival_city' => 'Andapa', 'distance_km' => 106, 'description' => 'Sambava → Andapa'],
            ['name' => 'RN4', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Mahajanga', 'distance_km' => 576, 'description' => 'Antananarivo → Maevatanana → Ambondromamy → Mahajanga'],
            ['name' => 'RN5', 'departure_city' => 'Toamasina', 'arrival_city' => 'Maroantsetra', 'distance_km' => 402, 'description' => 'Toamasina → Fénérive Est → Soanerana Ivongo → Mananara Nord → Maroantsetra'],
            ['name' => 'RN5a', 'departure_city' => 'Ambilobe', 'arrival_city' => 'Antalaha', 'distance_km' => 406, 'description' => 'Ambilobe → Vohemar → Sambava → Antalaha'],
            ['name' => 'RN6', 'departure_city' => 'Ambondromamy', 'arrival_city' => 'Antsiranana', 'distance_km' => 706, 'description' => 'Ambondromamy → Port Bergé → Antsohihy → Ambanja → Ambilobe → Antsiranana'],
            ['name' => 'RN7', 'departure_city' => 'Antananarivo', 'arrival_city' => 'Toliara', 'distance_km' => 956, 'description' => 'Antananarivo → Antsirabe → Ambositra → Ambohimahasoa → Fianarantsoa → Ambalavao → Ihosy → Sakaraha → Toliara'],
            ['name' => 'RN8', 'departure_city' => 'Morondava', 'arrival_city' => 'Bekopaka', 'distance_km' => 198, 'description' => 'Morondava → Belo/Tsiribihina → Bekopaka'],
            ['name' => 'RN8A', 'departure_city' => 'Maintirano', 'arrival_city' => 'Antsahalova', 'distance_km' => 119, 'description' => 'Maintirano → Antsahalova'],
            ['name' => 'RN9', 'departure_city' => 'Toliara', 'arrival_city' => 'Mandabe', 'distance_km' => 382, 'description' => 'Toliara → Bevoay → Manja → Morondava → Mandabe'],
            ['name' => 'RN10', 'departure_city' => 'Andranovory', 'arrival_city' => 'Ambovombe', 'distance_km' => 512, 'description' => 'Andranovory → Betioky → Ampanihy → Beloha → Tsihombe → Ambovombe'],
            ['name' => 'RN11', 'departure_city' => 'Mananjary', 'arrival_city' => 'Nosy Varika', 'distance_km' => 103, 'description' => 'Mananjary → Nosy Varika'],
            ['name' => 'RN11a', 'departure_city' => 'Antsapanana', 'arrival_city' => 'Mahanoro', 'distance_km' => 125, 'description' => 'Antsapanana → Vatomandry → Ilaka Est → Mahanoro'],
            ['name' => 'RN12', 'departure_city' => 'Irondro', 'arrival_city' => 'Vangaindrano', 'distance_km' => 300, 'description' => 'Irondro → Manakara → Vohipeno → Farafangana → Vangaindrano'],
            ['name' => 'RN12a', 'departure_city' => 'Tolagnaro', 'arrival_city' => 'Vangaindrano', 'distance_km' => 256, 'description' => 'Tolagnaro → Manantenina → Manambondro → Vangaindrano'],
            ['name' => 'RN13', 'departure_city' => 'Ihosy', 'arrival_city' => 'Tolagnaro', 'distance_km' => 493, 'description' => 'Ihosy → Betroka → Ambovombe → Amboasary Sud → Tolagnaro'],
            ['name' => 'RN22', 'departure_city' => 'Fénérive Est', 'arrival_city' => 'Vavatenina', 'distance_km' => 38, 'description' => 'Fénérive Est → Vavatenina'],
            ['name' => 'RN24', 'departure_city' => 'Mananjary', 'arrival_city' => 'Vohilava', 'distance_km' => 45, 'description' => 'Mananjary → Vohilava'],
            ['name' => 'RN25', 'departure_city' => 'Ambohimahasoa', 'arrival_city' => 'Irondro', 'distance_km' => 161, 'description' => 'Ambohimahasoa → Vohiparara → Ranomafana Sud → Ifanadiana → Irondro'],
            ['name' => 'Car RN7 (PK 355)', 'departure_city' => 'RN7 (PK 355)', 'arrival_city' => 'Mananjary', 'distance_km' => 176, 'description' => 'Bretelle RN7 au PK 355 → Mananjary'],
            ['name' => 'RN27', 'departure_city' => 'Ihosy', 'arrival_city' => 'Farafangana', 'distance_km' => 275, 'description' => 'Ihosy → Ivohibe → Farafangana'],
            ['name' => 'RN30 (bretelle RN6)', 'departure_city' => 'Ambalavelona', 'arrival_city' => 'Ankify', 'distance_km' => 20, 'description' => 'Ambalavelona → Ankify'],
            ['name' => 'RN30 (bretelle RN57)', 'departure_city' => 'Hellville', 'arrival_city' => 'Andilana', 'distance_km' => 25, 'description' => 'Hellville → Djamandjary → Andilana'],
            ['name' => 'RN31', 'departure_city' => 'Antsohihy', 'arrival_city' => 'Bealalana', 'distance_km' => 129, 'description' => 'Antsohihy → Bealalana'],
            ['name' => 'RN32', 'departure_city' => 'Antsohihy', 'arrival_city' => 'Mandritsara', 'distance_km' => 200, 'description' => 'Antsohihy → Befandriana Nord → Mandritsara'],
            ['name' => 'RN33', 'departure_city' => 'Ambatondrazaka', 'arrival_city' => 'Ambondromamy', 'distance_km' => 340, 'description' => 'Ambatondrazaka → Ambondromamy'],
            ['name' => 'RN34', 'departure_city' => 'Antsirabe', 'arrival_city' => 'Malaimbandy', 'distance_km' => 368, 'description' => 'Antsirabe → Miandrivazo → Malaimbandy'],
            ['name' => 'RN35', 'departure_city' => 'Ambositra', 'arrival_city' => 'Morondava', 'distance_km' => 460, 'description' => 'Ambositra → Malaimbandy → Morondava'],
            ['name' => 'RN41', 'departure_city' => 'Ambositra', 'arrival_city' => 'Fandriana', 'distance_km' => 41, 'description' => 'Ambositra → Fandriana'],
            ['name' => 'RN42', 'departure_city' => 'Fianarantsoa', 'arrival_city' => 'Ikalamavony', 'distance_km' => 94, 'description' => 'Fianarantsoa → Ikalamavony'],
            ['name' => 'RN43', 'departure_city' => 'Analavory', 'arrival_city' => 'Sambaina', 'distance_km' => 133, 'description' => 'Analavory → Ampefy → Soavinandriana → Ambohibary → Sambaina'],
            ['name' => 'RN44', 'departure_city' => 'Moramanga', 'arrival_city' => 'Amboary', 'distance_km' => 228, 'description' => 'Moramanga → Ambatondrazaka → Imerimandroso → Amboary'],
            ['name' => 'RN55', 'departure_city' => 'Bevoay', 'arrival_city' => 'Morombe', 'distance_km' => 78, 'description' => 'Bevoay → Morombe'],
        ];

        foreach ($routes as $route) {
            Route::create($route);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Seeders/RouteSeederTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/seeders/RouteSeeder.php tests/Feature/Seeders/RouteSeederTest.php
git commit -m "feat: add route seeder with the 38 known national routes"
```

---

### Task 12: `ServiceTypeSeeder` + `OptionTypeSeeder`

**Files:**
- Create: `database/seeders/ServiceTypeSeeder.php`
- Create: `database/seeders/OptionTypeSeeder.php`
- Test: `tests/Feature/Seeders/ServiceTypeAndOptionTypeSeederTest.php`

**Interfaces:**
- Consumes: `App\Models\ServiceType`, `App\Models\OptionType` (Step 2), `App\Enums\AmountMode` (Step 2).
- Produces: `Database\Seeders\ServiceTypeSeeder` (6 rows), `Database\Seeders\OptionTypeSeeder` (7 rows).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Seeders;

use App\Enums\AmountMode;
use Database\Seeders\OptionTypeSeeder;
use Database\Seeders\ServiceTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTypeAndOptionTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_six_service_types_with_their_coefficients(): void
    {
        (new ServiceTypeSeeder())->run();

        $this->assertDatabaseCount('service_types', 6);
        $this->assertDatabaseHas('service_types', ['name' => 'Location', 'coefficient' => 1.00]);
        $this->assertDatabaseHas('service_types', ['name' => 'Transfert', 'coefficient' => 2.00]);
        $this->assertDatabaseHas('service_types', ['name' => 'Circuit touristique', 'coefficient' => 1.50]);
        $this->assertDatabaseHas('service_types', ['name' => 'Mise à disposition', 'coefficient' => 1.20]);
        $this->assertDatabaseHas('service_types', ['name' => 'Aller simple', 'coefficient' => 1.00]);
        $this->assertDatabaseHas('service_types', ['name' => 'Aller-retour', 'coefficient' => 1.80]);
    }

    public function test_it_seeds_the_seven_option_types_with_their_defaults(): void
    {
        (new OptionTypeSeeder())->run();

        $this->assertDatabaseCount('option_types', 7);
        $this->assertDatabaseHas('option_types', ['name' => 'Assurance', 'default_mode' => AmountMode::Percentage->value, 'default_value' => 5]);
        $this->assertDatabaseHas('option_types', ['name' => 'Carburant', 'default_mode' => AmountMode::Fixed->value, 'default_value' => 100000]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Seeders/ServiceTypeAndOptionTypeSeederTest.php`
Expected: FAIL — `Class "Database\Seeders\ServiceTypeSeeder" not found`.

- [ ] **Step 3: Write the seeders**

Create `database/seeders/ServiceTypeSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $serviceTypes = [
            ['name' => 'Location', 'coefficient' => 1.00],
            ['name' => 'Transfert', 'coefficient' => 2.00],
            ['name' => 'Circuit touristique', 'coefficient' => 1.50],
            ['name' => 'Mise à disposition', 'coefficient' => 1.20],
            ['name' => 'Aller simple', 'coefficient' => 1.00],
            ['name' => 'Aller-retour', 'coefficient' => 1.80],
        ];

        foreach ($serviceTypes as $serviceType) {
            ServiceType::create($serviceType);
        }
    }
}
```

Create `database/seeders/OptionTypeSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Enums\AmountMode;
use App\Models\OptionType;
use Illuminate\Database\Seeder;

class OptionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $optionTypes = [
            ['name' => 'Chauffeur supplémentaire', 'default_mode' => AmountMode::Fixed, 'default_value' => 50000],
            ['name' => 'Carburant', 'default_mode' => AmountMode::Fixed, 'default_value' => 100000],
            ['name' => 'Péages', 'default_mode' => AmountMode::Fixed, 'default_value' => 20000],
            ['name' => 'Ferry', 'default_mode' => AmountMode::Fixed, 'default_value' => 150000],
            ['name' => 'Hébergement chauffeur', 'default_mode' => AmountMode::Fixed, 'default_value' => 30000],
            ['name' => 'Guide', 'default_mode' => AmountMode::Fixed, 'default_value' => 80000],
            ['name' => 'Assurance', 'default_mode' => AmountMode::Percentage, 'default_value' => 5],
        ];

        foreach ($optionTypes as $optionType) {
            OptionType::create($optionType);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Seeders/ServiceTypeAndOptionTypeSeederTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/seeders/ServiceTypeSeeder.php database/seeders/OptionTypeSeeder.php tests/Feature/Seeders/ServiceTypeAndOptionTypeSeederTest.php
git commit -m "feat: add service type and option type seeders"
```

---

### Task 13: `TariffSeeder` + wire `DatabaseSeeder`

**Files:**
- Create: `database/seeders/TariffSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Seeders/TariffSeederTest.php`

**Interfaces:**
- Consumes: `App\Models\Tariff`, `App\Models\Vehicle` (Step 2), `Database\Seeders\VehicleSeeder` (Task 10, run first in the test to create the vehicles being priced).
- Produces: `Database\Seeders\TariffSeeder` (24 rows: 4 vehicles × 6 tiers). `DatabaseSeeder::run()` now calls every seeder from Tasks 8–13 in dependency order.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Seeders;

use App\Models\Vehicle;
use Database\Seeders\TariffSeeder;
use Database\Seeders\VehicleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TariffSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_six_tier_grid_for_each_of_the_four_named_vehicles(): void
    {
        (new VehicleSeeder())->run();
        (new TariffSeeder())->run();

        $this->assertDatabaseCount('tariffs', 24);

        $starex = Vehicle::where('registration_number', '1234 TBA')->firstOrFail();
        $this->assertDatabaseHas('tariffs', [
            'vehicle_id' => $starex->id, 'min_distance_km' => 0, 'max_distance_km' => 799,
            'min_days' => 1, 'max_days' => 5, 'daily_rate' => 250000,
        ]);
        $this->assertDatabaseHas('tariffs', [
            'vehicle_id' => $starex->id, 'min_distance_km' => 800, 'max_distance_km' => null,
            'min_days' => 11, 'max_days' => null, 'daily_rate' => 250000,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Seeders/TariffSeederTest.php`
Expected: FAIL — `Class "Database\Seeders\TariffSeeder" not found`.

- [ ] **Step 3: Write the seeder and wire DatabaseSeeder**

Create `database/seeders/TariffSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Tariff;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class TariffSeeder extends Seeder
{
    public function run(): void
    {
        $starex = Vehicle::where('registration_number', '1234 TBA')->firstOrFail();
        $landCruiser = Vehicle::where('registration_number', '5678 TBB')->firstOrFail();
        $corolla = Vehicle::where('registration_number', '9012 TBC')->firstOrFail();
        $coaster = Vehicle::where('registration_number', '3456 TBD')->firstOrFail();

        $this->createGrid($starex->id, [
            [0, 799, 1, 5, 250000],
            [0, 799, 6, 10, 220000],
            [0, 799, 11, null, 200000],
            [800, null, 1, 5, 350000],
            [800, null, 6, 10, 310000],
            [800, null, 11, null, 250000],
        ]);

        $this->createGrid($landCruiser->id, [
            [0, 799, 1, 5, 300000],
            [0, 799, 6, 10, 270000],
            [0, 799, 11, null, 240000],
            [800, null, 1, 5, 400000],
            [800, null, 6, 10, 360000],
            [800, null, 11, null, 300000],
        ]);

        $this->createGrid($corolla->id, [
            [0, 799, 1, 5, 150000],
            [0, 799, 6, 10, 130000],
            [0, 799, 11, null, 110000],
            [800, null, 1, 5, 200000],
            [800, null, 6, 10, 180000],
            [800, null, 11, null, 150000],
        ]);

        $this->createGrid($coaster->id, [
            [0, 799, 1, 5, 350000],
            [0, 799, 6, 10, 320000],
            [0, 799, 11, null, 280000],
            [800, null, 1, 5, 450000],
            [800, null, 6, 10, 400000],
            [800, null, 11, null, 350000],
        ]);
    }

    private function createGrid(int $vehicleId, array $tiers): void
    {
        foreach ($tiers as [$minDistance, $maxDistance, $minDays, $maxDays, $dailyRate]) {
            Tariff::create([
                'vehicle_id' => $vehicleId,
                'min_distance_km' => $minDistance,
                'max_distance_km' => $maxDistance,
                'min_days' => $minDays,
                'max_days' => $maxDays,
                'daily_rate' => $dailyRate,
            ]);
        }
    }
}
```

Modify `database/seeders/DatabaseSeeder.php` to:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            CustomerSeeder::class,
            VehicleSeeder::class,
            RouteSeeder::class,
            ServiceTypeSeeder::class,
            OptionTypeSeeder::class,
            TariffSeeder::class,
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Seeders/TariffSeederTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/seeders/TariffSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/Seeders/TariffSeederTest.php
git commit -m "feat: add tariff seeder and wire DatabaseSeeder"
```

---

### Task 14: Full-suite sanity check

**Files:** none created or modified.

**Interfaces:**
- Consumes: all factories and seeders from Tasks 1–13.
- Produces: nothing (verification-only task).

- [ ] **Step 1: Run the entire test suite**

Run: `php artisan test`
Expected: PASS — all tests from Steps 1–2 and Step 3 pass together.

- [ ] **Step 2: Run the full seeder chain against a fresh database**

Run: `php artisan migrate:fresh --seed`
Expected: All migrations run, then every seeder in `DatabaseSeeder::run()` completes with no errors (no unique constraint violations, no missing-vehicle lookup failures in `TariffSeeder`).

- [ ] **Step 3: Commit (only if Steps 1–2 required fixes)**

If no fixes were needed, skip this step — there is nothing to commit. Otherwise:

```bash
git add -A
git commit -m "fix: resolve issues found in full-suite and seeder check"
```
