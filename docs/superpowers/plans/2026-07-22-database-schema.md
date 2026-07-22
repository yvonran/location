# Database Schema (Step 1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create every database table described in `docs/superpowers/specs/2026-07-22-schema-base-de-donnees-design.md` via Laravel migrations, with correct foreign keys, enums, soft deletes and indexes, plus `spatie/laravel-permission` installed for roles.

**Architecture:** One migration per table, created in dependency order (referenced tables before referencing tables) so foreign keys always resolve. No Eloquent models, seeders, or business logic in this plan — that is explicitly out of scope for Step 1 (see spec's "Hors périmètre" section) and will be covered by later plans.

**Tech Stack:** Laravel 13, PHP 8.4, MySQL in production / SQLite in-memory for tests (per `phpunit.xml`), PHPUnit-style test classes (this repo does not use Pest's functional syntax, despite the `pestphp/pest-plugin` composer allow-list entry — see `tests/Feature/ExampleTest.php`).

## Global Constraints

- Table, column, enum and model names are in **English** (project-wide decision, 2026-07-22). Documentation/commit prose may stay in French elsewhere in this project, but this plan and its code use English identifiers throughout.
- All monetary columns: `decimal(12, 2)`.
- Soft deletes (`softDeletes()`) only on: `customers`, `vehicles`, `quotes`, `reservations`. No other table gets soft deletes.
- No table in this plan implements pricing/coefficient/role business logic — this is schema only. Values referenced by later services (coefficients, tariffs) live in plain columns, never in code.
- Tests extend `Tests\TestCase` and use `Illuminate\Foundation\Testing\RefreshDatabase`, matching the existing convention in `tests/Feature/ExampleTest.php`. Do not use Pest's `it()`/`test()` functional syntax.
- Test DB is SQLite in-memory (`phpunit.xml`). Laravel's `Schema::enum()`/`$table->enum()` and foreign key constraints both work under SQLite for the assertions used in this plan.
- Foreign key delete behavior (decided in this plan, not specified verbatim in the spec): tables holding historical/financial data (`quote_lines`, `reservation_lines`, `quotes`, `reservations`) use `restrictOnDelete()` on references to reference/config data (`vehicles`, `customers`, `users`, `service_types`, `option_types`) so historical records can never be silently orphaned by deleting config data. Parent-to-child ownership relationships (`quotes` → `quote_lines`, `quote_lines` → `quote_line_options`, `reservations` → `reservation_lines`) use `cascadeOnDelete()`. The optional `quote_lines.route_id` uses `nullOnDelete()` since the line already snapshots `distance_km` and does not depend on the route surviving.

---

### Task 1: Install spatie/laravel-permission

**Files:**
- Modify: `composer.json`, `composer.lock` (via `composer require`)
- Create: `config/permission.php` (via `vendor:publish`)
- Create: `database/migrations/xxxx_xx_xx_xxxxxx_create_permission_tables.php` (via `vendor:publish`, exact timestamp set by the publish command at run time)
- Test: `tests/Feature/Database/PermissionsTest.php`

**Interfaces:**
- Consumes: nothing from this plan.
- Produces: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` tables (package-defined schema) usable by later tasks/plans via `Spatie\Permission\Models\Role` and the `Spatie\Permission\Traits\HasRoles` trait on `App\Models\User`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_role_can_be_created_and_assigned_to_a_user(): void
    {
        Role::create(['name' => 'agent', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('agent');

        $this->assertTrue($user->fresh()->hasRole('agent'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/PermissionsTest.php`
Expected: FAIL — `Class "Spatie\Permission\Models\Role" not found` (package not installed yet).

- [ ] **Step 3: Install and publish the package**

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

- [ ] **Step 4: Add the HasRoles trait to the User model**

In `app/Models/User.php`, add the import:

```php
use Spatie\Permission\Traits\HasRoles;
```

Change the existing trait line:

```php
use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;
```

to:

```php
use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;
```

- [ ] **Step 5: Run the published migration**

```bash
php artisan migrate
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/Database/PermissionsTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock config/permission.php database/migrations app/Models/User.php tests/Feature/Database/PermissionsTest.php
git commit -m "feat: install spatie/laravel-permission"
```

---

### Task 2: `customers` table

**Files:**
- Create: `database/migrations/2026_07_22_100000_create_customers_table.php`
- Test: `tests/Feature/Database/CustomersTableTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `customers(id, name, phone, email, address, tax_id, created_at, updated_at, deleted_at)`.

- [ ] **Step 1: Write the failing test**

```php
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
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/CustomersTableTest.php`
Expected: FAIL — table `customers` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/CustomersTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100000_create_customers_table.php tests/Feature/Database/CustomersTableTest.php
git commit -m "feat: add customers table"
```

---

### Task 3: `vehicles` table

**Files:**
- Create: `database/migrations/2026_07_22_100100_create_vehicles_table.php`
- Test: `tests/Feature/Database/VehiclesTableTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `vehicles(id, name, brand, model, seats, registration_number, year, has_air_conditioning, status, created_at, updated_at, deleted_at)`. `status` enum values: `available`, `maintenance`, `out_of_service` (default `available`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehiclesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicles_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('vehicles'));
        $this->assertTrue(Schema::hasColumns('vehicles', [
            'id', 'name', 'brand', 'model', 'seats', 'registration_number',
            'year', 'has_air_conditioning', 'status',
            'created_at', 'updated_at', 'deleted_at',
        ]));
    }

    public function test_a_vehicle_defaults_to_available_status(): void
    {
        $id = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1',
            'brand' => 'Hyundai',
            'model' => 'Starex',
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2020,
            'has_air_conditioning' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('vehicles', [
            'id' => $id,
            'status' => 'available',
        ]);
    }

    public function test_registration_number_must_be_unique(): void
    {
        DB::table('vehicles')->insert([
            'name' => 'Starex 1',
            'brand' => 'Hyundai',
            'model' => 'Starex',
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2020,
            'has_air_conditioning' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('vehicles')->insert([
            'name' => 'Starex 2',
            'brand' => 'Hyundai',
            'model' => 'Starex',
            'seats' => 8,
            'registration_number' => '1234 TBA',
            'year' => 2021,
            'has_air_conditioning' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/VehiclesTableTest.php`
Expected: FAIL — table `vehicles` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand');
            $table->string('model');
            $table->unsignedTinyInteger('seats');
            $table->string('registration_number')->unique();
            $table->unsignedSmallInteger('year');
            $table->boolean('has_air_conditioning')->default(false);
            $table->enum('status', ['available', 'maintenance', 'out_of_service'])->default('available');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/VehiclesTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100100_create_vehicles_table.php tests/Feature/Database/VehiclesTableTest.php
git commit -m "feat: add vehicles table"
```

---

### Task 4: `routes` table

**Files:**
- Create: `database/migrations/2026_07_22_100200_create_routes_table.php`
- Test: `tests/Feature/Database/RoutesTableTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `routes(id, name, departure_city, arrival_city, distance_km, estimated_duration_minutes, description, created_at, updated_at)`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoutesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_routes_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('routes'));
        $this->assertTrue(Schema::hasColumns('routes', [
            'id', 'name', 'departure_city', 'arrival_city', 'distance_km',
            'estimated_duration_minutes', 'description', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_route_can_be_created_without_optional_fields(): void
    {
        $id = DB::table('routes')->insertGetId([
            'name' => 'RN2',
            'departure_city' => 'Antananarivo',
            'arrival_city' => 'Toamasina',
            'distance_km' => 367,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('routes', [
            'id' => $id,
            'estimated_duration_minutes' => null,
            'description' => null,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/RoutesTableTest.php`
Expected: FAIL — table `routes` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('departure_city');
            $table->string('arrival_city');
            $table->decimal('distance_km', 8, 2);
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/RoutesTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100200_create_routes_table.php tests/Feature/Database/RoutesTableTest.php
git commit -m "feat: add routes table"
```

---

### Task 5: `service_types` table

**Files:**
- Create: `database/migrations/2026_07_22_100300_create_service_types_table.php`
- Test: `tests/Feature/Database/ServiceTypesTableTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `service_types(id, name, coefficient, description, active, created_at, updated_at)`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceTypesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_types_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('service_types'));
        $this->assertTrue(Schema::hasColumns('service_types', [
            'id', 'name', 'coefficient', 'description', 'active', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_service_type_defaults_to_active(): void
    {
        $id = DB::table('service_types')->insertGetId([
            'name' => 'Transfert',
            'coefficient' => 2.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('service_types', [
            'id' => $id,
            'active' => 1,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/ServiceTypesTableTest.php`
Expected: FAIL — table `service_types` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('coefficient', 5, 2);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/ServiceTypesTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100300_create_service_types_table.php tests/Feature/Database/ServiceTypesTableTest.php
git commit -m "feat: add service_types table"
```

---

### Task 6: `option_types` table

**Files:**
- Create: `database/migrations/2026_07_22_100400_create_option_types_table.php`
- Test: `tests/Feature/Database/OptionTypesTableTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `option_types(id, name, default_mode, default_value, active, created_at, updated_at)`. `default_mode` enum values: `fixed`, `percentage`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OptionTypesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_option_types_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('option_types'));
        $this->assertTrue(Schema::hasColumns('option_types', [
            'id', 'name', 'default_mode', 'default_value', 'active', 'created_at', 'updated_at',
        ]));
    }

    public function test_an_option_type_can_be_created_with_percentage_mode(): void
    {
        $id = DB::table('option_types')->insertGetId([
            'name' => 'Assurance',
            'default_mode' => 'percentage',
            'default_value' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('option_types', [
            'id' => $id,
            'default_mode' => 'percentage',
            'active' => 1,
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/OptionTypesTableTest.php`
Expected: FAIL — table `option_types` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('default_mode', ['fixed', 'percentage']);
            $table->decimal('default_value', 12, 2);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_types');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/OptionTypesTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100400_create_option_types_table.php tests/Feature/Database/OptionTypesTableTest.php
git commit -m "feat: add option_types table"
```

---

### Task 7: `tariffs` table

**Files:**
- Create: `database/migrations/2026_07_22_100500_create_tariffs_table.php`
- Test: `tests/Feature/Database/TariffsTableTest.php`

**Interfaces:**
- Consumes: `vehicles.id` (Task 3).
- Produces: `tariffs(id, vehicle_id, min_distance_km, max_distance_km, min_days, max_days, daily_rate, created_at, updated_at)`, composite index on `(vehicle_id, min_distance_km, max_distance_km)`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TariffsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_tariffs_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('tariffs'));
        $this->assertTrue(Schema::hasColumns('tariffs', [
            'id', 'vehicle_id', 'min_distance_km', 'max_distance_km',
            'min_days', 'max_days', 'daily_rate', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_tariff_can_be_created_for_a_vehicle_with_no_max_bounds(): void
    {
        $vehicleId = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $id = DB::table('tariffs')->insertGetId([
            'vehicle_id' => $vehicleId,
            'min_distance_km' => 0,
            'max_distance_km' => 799,
            'min_days' => 11,
            'max_days' => null,
            'daily_rate' => 200000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('tariffs', ['id' => $id, 'max_days' => null]);
    }

    public function test_deleting_the_vehicle_cascades_to_its_tariffs(): void
    {
        $vehicleId = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $tariffId = DB::table('tariffs')->insertGetId([
            'vehicle_id' => $vehicleId, 'min_distance_km' => 0, 'max_distance_km' => 799,
            'min_days' => 1, 'max_days' => 5, 'daily_rate' => 250000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('vehicles')->where('id', $vehicleId)->delete();

        $this->assertDatabaseMissing('tariffs', ['id' => $tariffId]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/TariffsTableTest.php`
Expected: FAIL — table `tariffs` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->unsignedInteger('min_distance_km');
            $table->unsignedInteger('max_distance_km')->nullable();
            $table->unsignedSmallInteger('min_days');
            $table->unsignedSmallInteger('max_days')->nullable();
            $table->decimal('daily_rate', 12, 2);
            $table->timestamps();

            $table->index(['vehicle_id', 'min_distance_km', 'max_distance_km']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tariffs');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/TariffsTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100500_create_tariffs_table.php tests/Feature/Database/TariffsTableTest.php
git commit -m "feat: add tariffs table"
```

---

### Task 8: `quotes` table

**Files:**
- Create: `database/migrations/2026_07_22_100600_create_quotes_table.php`
- Test: `tests/Feature/Database/QuotesTableTest.php`

**Interfaces:**
- Consumes: `customers.id` (Task 2), `users.id` (existing).
- Produces: `quotes(id, number, customer_id, user_id, quote_date, status, subtotal, total, notes, created_at, updated_at, deleted_at)`. `status` enum values: `draft`, `sent`, `accepted`, `rejected` (default `draft`).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuotesTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotes_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('quotes'));
        $this->assertTrue(Schema::hasColumns('quotes', [
            'id', 'number', 'customer_id', 'user_id', 'quote_date', 'status',
            'subtotal', 'total', 'notes', 'created_at', 'updated_at', 'deleted_at',
        ]));
    }

    public function test_a_quote_defaults_to_draft_status_and_zero_totals(): void
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();

        $id = DB::table('quotes')->insertGetId([
            'number' => 'QUO-2026-0001',
            'customer_id' => $customerId,
            'user_id' => $user->id,
            'quote_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('quotes', [
            'id' => $id,
            'status' => 'draft',
            'subtotal' => 0,
            'total' => 0,
        ]);
    }

    public function test_quote_number_must_be_unique(): void
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();

        DB::table('quotes')->insert([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('quotes')->insert([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/QuotesTableTest.php`
Expected: FAIL — table `quotes` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('quote_date');
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected'])->default('draft');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/QuotesTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100600_create_quotes_table.php tests/Feature/Database/QuotesTableTest.php
git commit -m "feat: add quotes table"
```

---

### Task 9: `quote_lines` table

**Files:**
- Create: `database/migrations/2026_07_22_100700_create_quote_lines_table.php`
- Test: `tests/Feature/Database/QuoteLinesTableTest.php`

**Interfaces:**
- Consumes: `quotes.id` (Task 8), `vehicles.id` (Task 3), `routes.id` (Task 4), `service_types.id` (Task 5).
- Produces: `quote_lines(id, quote_id, vehicle_id, route_id, service_type_id, start_date, number_of_days, distance_km, daily_rate, service_coefficient, discount_type, discount_value, discount_amount, options_amount, line_total, position, created_at, updated_at)`. `discount_type` enum values: `fixed`, `percentage` (nullable).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuoteLinesTableTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuoteId(): int
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();

        return DB::table('quotes')->insertGetId([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeVehicleId(): int
    {
        return DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function makeServiceTypeId(): int
    {
        return DB::table('service_types')->insertGetId([
            'name' => 'Location', 'coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_quote_lines_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('quote_lines'));
        $this->assertTrue(Schema::hasColumns('quote_lines', [
            'id', 'quote_id', 'vehicle_id', 'route_id', 'service_type_id',
            'start_date', 'number_of_days', 'distance_km', 'daily_rate',
            'service_coefficient', 'discount_type', 'discount_value',
            'discount_amount', 'options_amount', 'line_total', 'position',
            'created_at', 'updated_at',
        ]));
    }

    public function test_a_quote_line_can_be_created_without_a_route(): void
    {
        $id = DB::table('quote_lines')->insertGetId([
            'quote_id' => $this->makeQuoteId(),
            'vehicle_id' => $this->makeVehicleId(),
            'route_id' => null,
            'service_type_id' => $this->makeServiceTypeId(),
            'start_date' => now()->toDateString(),
            'number_of_days' => 3,
            'distance_km' => 450.50,
            'daily_rate' => 250000,
            'service_coefficient' => 1.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('quote_lines', [
            'id' => $id,
            'route_id' => null,
            'discount_amount' => 0,
            'options_amount' => 0,
            'line_total' => 0,
            'position' => 0,
        ]);
    }

    public function test_deleting_the_quote_cascades_to_its_lines(): void
    {
        $quoteId = $this->makeQuoteId();
        $lineId = DB::table('quote_lines')->insertGetId([
            'quote_id' => $quoteId,
            'vehicle_id' => $this->makeVehicleId(),
            'service_type_id' => $this->makeServiceTypeId(),
            'start_date' => now()->toDateString(),
            'number_of_days' => 3,
            'distance_km' => 450.50,
            'daily_rate' => 250000,
            'service_coefficient' => 1.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('quotes')->where('id', $quoteId)->delete();

        $this->assertDatabaseMissing('quote_lines', ['id' => $lineId]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/QuoteLinesTableTest.php`
Expected: FAIL — table `quote_lines` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types')->restrictOnDelete();
            $table->date('start_date');
            $table->unsignedSmallInteger('number_of_days');
            $table->decimal('distance_km', 8, 2);
            $table->decimal('daily_rate', 12, 2);
            $table->decimal('service_coefficient', 5, 2);
            $table->enum('discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('options_amount', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_lines');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/QuoteLinesTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100700_create_quote_lines_table.php tests/Feature/Database/QuoteLinesTableTest.php
git commit -m "feat: add quote_lines table"
```

---

### Task 10: `quote_line_options` table

**Files:**
- Create: `database/migrations/2026_07_22_100800_create_quote_line_options_table.php`
- Test: `tests/Feature/Database/QuoteLineOptionsTableTest.php`

**Interfaces:**
- Consumes: `quote_lines.id` (Task 9), `option_types.id` (Task 6).
- Produces: `quote_line_options(id, quote_line_id, option_type_id, mode, value, amount, created_at, updated_at)`. `mode` enum values: `fixed`, `percentage`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuoteLineOptionsTableTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuoteLineId(): int
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();
        $quoteId = DB::table('quotes')->insertGetId([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $vehicleId = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $serviceTypeId = DB::table('service_types')->insertGetId([
            'name' => 'Location', 'coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return DB::table('quote_lines')->insertGetId([
            'quote_id' => $quoteId, 'vehicle_id' => $vehicleId, 'service_type_id' => $serviceTypeId,
            'start_date' => now()->toDateString(), 'number_of_days' => 3, 'distance_km' => 450.50,
            'daily_rate' => 250000, 'service_coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_quote_line_options_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('quote_line_options'));
        $this->assertTrue(Schema::hasColumns('quote_line_options', [
            'id', 'quote_line_id', 'option_type_id', 'mode', 'value', 'amount',
            'created_at', 'updated_at',
        ]));
    }

    public function test_an_option_can_be_attached_to_a_quote_line(): void
    {
        $optionTypeId = DB::table('option_types')->insertGetId([
            'name' => 'Assurance', 'default_mode' => 'percentage', 'default_value' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $id = DB::table('quote_line_options')->insertGetId([
            'quote_line_id' => $this->makeQuoteLineId(),
            'option_type_id' => $optionTypeId,
            'mode' => 'percentage',
            'value' => 10,
            'amount' => 75000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('quote_line_options', ['id' => $id, 'mode' => 'percentage']);
    }

    public function test_deleting_the_quote_line_cascades_to_its_options(): void
    {
        $quoteLineId = $this->makeQuoteLineId();
        $optionTypeId = DB::table('option_types')->insertGetId([
            'name' => 'Assurance', 'default_mode' => 'percentage', 'default_value' => 10,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $optionId = DB::table('quote_line_options')->insertGetId([
            'quote_line_id' => $quoteLineId, 'option_type_id' => $optionTypeId,
            'mode' => 'percentage', 'value' => 10, 'amount' => 75000,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('quote_lines')->where('id', $quoteLineId)->delete();

        $this->assertDatabaseMissing('quote_line_options', ['id' => $optionId]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/QuoteLineOptionsTableTest.php`
Expected: FAIL — table `quote_line_options` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_line_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_line_id')->constrained('quote_lines')->cascadeOnDelete();
            $table->foreignId('option_type_id')->constrained('option_types')->restrictOnDelete();
            $table->enum('mode', ['fixed', 'percentage']);
            $table->decimal('value', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_line_options');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/QuoteLineOptionsTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100800_create_quote_line_options_table.php tests/Feature/Database/QuoteLineOptionsTableTest.php
git commit -m "feat: add quote_line_options table"
```

---

### Task 11: `reservations` table

**Files:**
- Create: `database/migrations/2026_07_22_100900_create_reservations_table.php`
- Test: `tests/Feature/Database/ReservationsTableTest.php`

**Interfaces:**
- Consumes: `quotes.id` (Task 8).
- Produces: `reservations(id, number, quote_id, created_at, updated_at)`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationsTableTest extends TestCase
{
    use RefreshDatabase;

    private function makeQuoteId(): int
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();

        return DB::table('quotes')->insertGetId([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'status' => 'accepted',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_reservations_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('reservations'));
        $this->assertTrue(Schema::hasColumns('reservations', [
            'id', 'number', 'quote_id', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_reservation_can_be_created_from_an_accepted_quote(): void
    {
        $id = DB::table('reservations')->insertGetId([
            'number' => 'RES-2026-0001',
            'quote_id' => $this->makeQuoteId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('reservations', ['id' => $id, 'number' => 'RES-2026-0001']);
    }

    public function test_reservation_number_must_be_unique(): void
    {
        $quoteId = $this->makeQuoteId();

        DB::table('reservations')->insert([
            'number' => 'RES-2026-0001', 'quote_id' => $quoteId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('reservations')->insert([
            'number' => 'RES-2026-0001', 'quote_id' => $quoteId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/ReservationsTableTest.php`
Expected: FAIL — table `reservations` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();
            $table->foreignId('quote_id')->constrained('quotes')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/ReservationsTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_100900_create_reservations_table.php tests/Feature/Database/ReservationsTableTest.php
git commit -m "feat: add reservations table"
```

---

### Task 12: `reservation_lines` table

**Files:**
- Create: `database/migrations/2026_07_22_101000_create_reservation_lines_table.php`
- Test: `tests/Feature/Database/ReservationLinesTableTest.php`

**Interfaces:**
- Consumes: `reservations.id` (Task 11), `quote_lines.id` (Task 9), `vehicles.id` (Task 3).
- Produces: `reservation_lines(id, reservation_id, quote_line_id, vehicle_id, start_date, end_date, status, created_at, updated_at)`. `status` enum values: `confirmed`, `in_progress`, `completed`, `cancelled` (default `confirmed`). Composite index on `(vehicle_id, start_date, end_date)` for calendar queries.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReservationLinesTableTest extends TestCase
{
    use RefreshDatabase;

    private function makeReservationAndDependencies(): array
    {
        $customerId = DB::table('customers')->insertGetId([
            'name' => 'Jean Rakoto', 'phone' => '0341234567',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = User::factory()->create();
        $quoteId = DB::table('quotes')->insertGetId([
            'number' => 'QUO-2026-0001', 'customer_id' => $customerId, 'user_id' => $user->id,
            'quote_date' => now()->toDateString(), 'status' => 'accepted',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $vehicleId = DB::table('vehicles')->insertGetId([
            'name' => 'Starex 1', 'brand' => 'Hyundai', 'model' => 'Starex',
            'seats' => 8, 'registration_number' => '1234 TBA', 'year' => 2020,
            'has_air_conditioning' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $serviceTypeId = DB::table('service_types')->insertGetId([
            'name' => 'Location', 'coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $quoteLineId = DB::table('quote_lines')->insertGetId([
            'quote_id' => $quoteId, 'vehicle_id' => $vehicleId, 'service_type_id' => $serviceTypeId,
            'start_date' => now()->toDateString(), 'number_of_days' => 3, 'distance_km' => 450.50,
            'daily_rate' => 250000, 'service_coefficient' => 1.00,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $reservationId = DB::table('reservations')->insertGetId([
            'number' => 'RES-2026-0001', 'quote_id' => $quoteId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return compact('reservationId', 'quoteLineId', 'vehicleId');
    }

    public function test_reservation_lines_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('reservation_lines'));
        $this->assertTrue(Schema::hasColumns('reservation_lines', [
            'id', 'reservation_id', 'quote_line_id', 'vehicle_id',
            'start_date', 'end_date', 'status', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_reservation_line_defaults_to_confirmed_status(): void
    {
        $deps = $this->makeReservationAndDependencies();

        $id = DB::table('reservation_lines')->insertGetId([
            'reservation_id' => $deps['reservationId'],
            'quote_line_id' => $deps['quoteLineId'],
            'vehicle_id' => $deps['vehicleId'],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('reservation_lines', ['id' => $id, 'status' => 'confirmed']);
    }

    public function test_deleting_the_reservation_cascades_to_its_lines(): void
    {
        $deps = $this->makeReservationAndDependencies();

        $lineId = DB::table('reservation_lines')->insertGetId([
            'reservation_id' => $deps['reservationId'],
            'quote_line_id' => $deps['quoteLineId'],
            'vehicle_id' => $deps['vehicleId'],
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reservations')->where('id', $deps['reservationId'])->delete();

        $this->assertDatabaseMissing('reservation_lines', ['id' => $lineId]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Database/ReservationLinesTableTest.php`
Expected: FAIL — table `reservation_lines` does not exist.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('quote_line_id')->constrained('quote_lines')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['confirmed', 'in_progress', 'completed', 'cancelled'])->default('confirmed');
            $table->timestamps();

            $table->index(['vehicle_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_lines');
    }
};
```

- [ ] **Step 4: Run migration and test to verify they pass**

Run: `php artisan migrate && php artisan test tests/Feature/Database/ReservationLinesTableTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_07_22_101000_create_reservation_lines_table.php tests/Feature/Database/ReservationLinesTableTest.php
git commit -m "feat: add reservation_lines table"
```

---

### Task 13: Full-suite sanity check

**Files:** none created or modified.

**Interfaces:**
- Consumes: all tables from Tasks 1–12.
- Produces: nothing (verification-only task).

- [ ] **Step 1: Run the entire test suite**

Run: `php artisan test`
Expected: PASS — all tests from Tasks 1–12 (and pre-existing tests) pass together, confirming migration order and foreign keys are consistent end-to-end.

- [ ] **Step 2: Run a fresh migration from scratch**

Run: `php artisan migrate:fresh`
Expected: All migrations run in order with no foreign key errors, ending on `Migration table created successfully.` followed by every migration listed as `DONE`.

- [ ] **Step 3: Commit (only if Steps 1–2 required fixes)**

If no fixes were needed, skip this step — there is nothing to commit. Otherwise:

```bash
git add -A
git commit -m "fix: resolve migration ordering/constraint issues found in full-suite check"
```
