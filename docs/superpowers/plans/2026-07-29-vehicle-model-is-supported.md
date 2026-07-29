# Colonne `is_supported` sur `vehicle_models` — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a manually-toggled `is_supported` flag to `vehicle_models`, and only show classified + supported models in the vehicle create/edit "Modèle" select.

**Architecture:** A new boolean column, backfilled from the existing `vehicle_type_id`-presence convention. Superadmins toggle it from the model configuration page. `VehicleController` filters the models it hands to the vehicle form on `vehicle_type_id IS NOT NULL AND is_supported = true`, with an escape hatch so editing a vehicle never hides its own (possibly now-excluded) model.

**Tech Stack:** Laravel 12 (migrations, Eloquent, FormRequest), Inertia + Vue 3 + TypeScript, PHPUnit feature tests.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-29-vehicle-model-is-supported-design.md`
- `is_supported` is a boolean, default `false` at the column level.
- Migration backfill: `is_supported = true` wherever `vehicle_type_id` is not null (preserves current behavior for existing data).
- `is_supported` is a manual, independent toggle — never auto-derived from `vehicle_type_id` after the initial backfill.
- Default value in the "add model" form (`VehicleModels.vue`): `true`.
- Vehicle create/edit select shows a model only if BOTH `vehicle_type_id` is not null AND `is_supported` is true.
- Exception: the vehicle's currently-assigned model must always appear in the edit page's model list, even if it no longer satisfies the two conditions above.
- All user-facing labels are in French, matching existing copy in the touched files.
- No JS test runner exists in this repo (no vitest/*.test.ts) — frontend-only changes are verified with `npm run types:check` plus a manual dev-server check, not an automated test.

---

### Task 1: Migration + `VehicleModel` casts

**Files:**
- Create: `database/migrations/2026_07_29_160400_add_is_supported_to_vehicle_models_table.php`
- Modify: `app/Models/VehicleModel.php`
- Test: `tests/Feature/Database/VehicleModelsTableTest.php`

**Interfaces:**
- Produces: `vehicle_models.is_supported` (boolean, default `false`, backfilled `true` where `vehicle_type_id IS NOT NULL`). `VehicleModel::$is_supported` cast to `bool`, fillable.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Database/VehicleModelsTableTest.php`:

```php
<?php

namespace Tests\Feature\Database;

use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VehicleModelsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_models_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('vehicle_models'));
        $this->assertTrue(Schema::hasColumns('vehicle_models', [
            'id', 'brand_id', 'vehicle_type_id', 'name', 'is_supported', 'created_at', 'updated_at',
        ]));
    }

    public function test_a_model_is_not_supported_by_default(): void
    {
        $brand = Brand::create(['name' => 'Hyundai']);

        $id = DB::table('vehicle_models')->insertGetId([
            'brand_id' => $brand->id,
            'name' => 'County',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('vehicle_models', ['id' => $id, 'is_supported' => false]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=VehicleModelsTableTest`
Expected: FAIL — `is_supported` column does not exist yet.

- [ ] **Step 3: Write the migration**

Create `database/migrations/2026_07_29_160400_add_is_supported_to_vehicle_models_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Avant ce champ, la présence d'un vehicle_type_id valait "disponible" : le
 * backfill préserve ce comportement pour les modèles déjà classés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->boolean('is_supported')->default(false)->after('vehicle_type_id');
        });

        DB::table('vehicle_models')->whereNotNull('vehicle_type_id')->update(['is_supported' => true]);
    }

    public function down(): void
    {
        Schema::table('vehicle_models', function (Blueprint $table) {
            $table->dropColumn('is_supported');
        });
    }
};
```

- [ ] **Step 4: Update the `VehicleModel` model**

In `app/Models/VehicleModel.php`, change the `Fillable` attribute and add a `casts()` method:

```php
#[Fillable(['brand_id', 'vehicle_type_id', 'name', 'is_supported'])]
class VehicleModel extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_supported' => 'boolean',
        ];
    }

    // ... existing relations unchanged
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=VehicleModelsTableTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_29_160400_add_is_supported_to_vehicle_models_table.php app/Models/VehicleModel.php tests/Feature/Database/VehicleModelsTableTest.php
git commit -m "feat: add is_supported column to vehicle_models"
```

---

### Task 2: Validate and persist `is_supported` from the model configuration form

**Files:**
- Modify: `app/Http/Requests/Configuration/StoreVehicleModelRequest.php`
- Test: `tests/Feature/Configuration/ReferenceControllersTest.php`

**Interfaces:**
- Consumes: `VehicleModel::$is_supported` (Task 1).
- Produces: `is_supported` accepted by `POST configuration.vehicle-models.store` and `PUT configuration.vehicle-models.update` (validated boolean; `VehicleModelController::store`/`update` already spread `$request->validated()` into `VehicleModel::create()`/`update()`, so no controller change is needed).

- [ ] **Step 1: Write the failing test**

In `tests/Feature/Configuration/ReferenceControllersTest.php`, add (after `test_a_model_is_created_under_a_brand_and_a_type`):

```php
    public function test_a_model_can_be_marked_supported(): void
    {
        $brand = Brand::create(['name' => 'Hyundai']);
        $type = VehicleType::create(['name' => 'Bus', 'position' => 0]);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.vehicle-models.index'))
            ->post(route('configuration.vehicle-models.store'), [
                'brand_id' => $brand->id,
                'vehicle_type_id' => $type->id,
                'name' => 'County',
                'is_supported' => true,
            ])
            ->assertSessionHasNoErrors();

        $model = VehicleModel::firstOrFail();
        $this->assertTrue((bool) $model->is_supported);

        $this->actingAs($this->superAdmin())
            ->from(route('configuration.vehicle-models.index'))
            ->put(route('configuration.vehicle-models.update', $model), [
                'brand_id' => $brand->id,
                'vehicle_type_id' => $type->id,
                'name' => 'County',
                'is_supported' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $model->fresh()->is_supported);
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_a_model_can_be_marked_supported`
Expected: FAIL — `assertTrue((bool) $model->is_supported)` fails because `is_supported` isn't in the validated payload yet, so it stays at its default `false`.

- [ ] **Step 3: Add the validation rule**

In `app/Http/Requests/Configuration/StoreVehicleModelRequest.php`, update `rules()` and `attributes()`:

```php
    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'vehicle_type_id' => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'is_supported' => ['boolean'],
            'name' => [
                'required', 'string', 'max:255',
                // Un même nom de modèle peut exister chez deux marques différentes.
                Rule::unique('vehicle_models', 'name')->where('brand_id', $this->integer('brand_id')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'brand_id' => 'marque',
            'vehicle_type_id' => 'type',
            'is_supported' => 'disponibilité',
            'name' => 'nom du modèle',
        ];
    }
```

`UpdateVehicleModelRequest` extends this class and only overrides `name`'s uniqueness rule, so it inherits `is_supported` automatically — no change needed there.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_a_model_can_be_marked_supported`
Expected: PASS

- [ ] **Step 5: Run the full configuration test suite**

Run: `php artisan test --filter=ReferenceControllersTest`
Expected: PASS (no regression on existing model/brand/type tests).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/Configuration/StoreVehicleModelRequest.php tests/Feature/Configuration/ReferenceControllersTest.php
git commit -m "feat: allow toggling is_supported from model configuration"
```

---

### Task 3: Filter the vehicle form's model list

**Files:**
- Modify: `app/Http/Controllers/VehicleController.php`
- Test: `tests/Feature/VehicleControllerTest.php`

**Interfaces:**
- Consumes: `VehicleModel::$is_supported`, `$vehicle_type_id` (Task 1); `Vehicle::vehicleModel(): BelongsTo` (existing, `app/Models/Vehicle.php:50`).
- Produces: `VehicleController::referenceData(?VehicleModel $currentModel = null): array` — `vehicleModels` now only contains rows where `vehicle_type_id` is not null and `is_supported` is true, plus `$currentModel` if passed and otherwise excluded.

- [ ] **Step 1: Write the failing tests**

In `tests/Feature/VehicleControllerTest.php`, add `use App\Models\Brand;` and `use App\Models\VehicleType;` to the imports, then add these two tests (anywhere after `test_the_create_page_offers_the_default_zones`):

```php
    public function test_the_create_page_only_lists_supported_typed_models(): void
    {
        $type = VehicleType::create(['name' => 'Bus', 'position' => 0]);
        $brand = Brand::create(['name' => 'Hyundai']);

        $supported = VehicleModel::create([
            'brand_id' => $brand->id, 'vehicle_type_id' => $type->id, 'name' => 'County', 'is_supported' => true,
        ]);
        VehicleModel::create([
            'brand_id' => $brand->id, 'vehicle_type_id' => $type->id, 'name' => 'Starex', 'is_supported' => false,
        ]);
        VehicleModel::create([
            'brand_id' => $brand->id, 'vehicle_type_id' => null, 'name' => 'Importé', 'is_supported' => true,
        ]);

        $this->actingAs($this->user())
            ->get(route('vehicles.create'))
            ->assertInertia(fn ($page) => $page
                ->has('vehicleModels', 1)
                ->where('vehicleModels.0.id', $supported->id));
    }

    public function test_the_edit_page_still_includes_the_vehicles_own_excluded_model(): void
    {
        $model = VehicleModel::factory()->create(['vehicle_type_id' => null, 'is_supported' => false]);
        $vehicle = Vehicle::factory()->create(['vehicle_model_id' => $model->id]);

        $this->actingAs($this->user())
            ->get(route('vehicles.edit', $vehicle))
            ->assertInertia(fn ($page) => $page
                ->has('vehicleModels', 1)
                ->where('vehicleModels.0.id', $model->id));
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=VehicleControllerTest`
Expected: `test_the_create_page_only_lists_supported_typed_models` FAILS — `referenceData()` currently returns all 3 models unfiltered instead of just the supported one. `test_the_edit_page_still_includes_the_vehicles_own_excluded_model` PASSES already (today every model is included, so the vehicle's own model trivially shows up too) — it starts earning its keep once Step 3 adds the filter; keep it since it now guards the edit-page edge case going forward.

- [ ] **Step 3: Filter `referenceData()` and pass the current model on edit**

In `app/Http/Controllers/VehicleController.php`, replace `referenceData()`:

```php
    /**
     * Référentiel servant les listes déroulantes : le type filtre les modèles,
     * et le modèle porte sa marque. Seuls les modèles classés et disponibles
     * apparaissent, sauf le modèle déjà choisi par le véhicule en édition.
     *
     * @return array{vehicleModels: mixed, vehicleTypes: mixed}
     */
    private function referenceData(?VehicleModel $currentModel = null): array
    {
        return [
            'vehicleModels' => VehicleModel::query()
                ->with('brand:id,name')
                ->where(function ($query) use ($currentModel) {
                    $query->whereNotNull('vehicle_type_id')->where('is_supported', true);

                    if ($currentModel) {
                        $query->orWhere('id', $currentModel->id);
                    }
                })
                ->orderBy('name')
                ->get(['id', 'brand_id', 'vehicle_type_id', 'name', 'is_supported']),
            'vehicleTypes' => VehicleType::orderBy('position')->orderBy('name')->get(['id', 'name']),
        ];
    }
```

Then update `edit()` to load the vehicle's model before building props and pass it through:

```php
    public function edit(Vehicle $vehicle, RentalConditionService $rentalConditionService): Response
    {
        $vehicle->loadMissing('vehicleModel.brand', 'vehicleModel.vehicleType');

        return Inertia::render('vehicles/Edit', [
            'vehicle' => $vehicle,
            'statuses' => $this->statuses(),
            ...$this->referenceData($vehicle->vehicleModel),
            ...$rentalConditionService->editorProps($vehicle),
        ]);
    }
```

`create()` is unchanged — it already calls `$this->referenceData()` with no argument.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=VehicleControllerTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/VehicleController.php tests/Feature/VehicleControllerTest.php
git commit -m "feat: only offer supported typed models when picking a vehicle's model"
```

---

### Task 4: Configuration UI — toggle `is_supported` and show it in the model list

**Files:**
- Modify: `resources/js/types/reference.ts`
- Modify: `resources/js/pages/configuration/VehicleModels.vue`

**Interfaces:**
- Consumes: `is_supported` field now accepted by `POST/PUT configuration.vehicle-models.*` (Task 2); `VehicleModel.is_supported` returned by the backend (Task 1/2 — `VehicleModelController::index` already does `VehicleModel::query()->with(['brand', 'vehicleType'])`, which returns all columns including the new one, so no controller change is needed here).
- Produces: `VehicleModel` TS type carries `is_supported: boolean`; the configuration page lets the superadmin set it on create/edit and displays it in the table.

- [ ] **Step 1: Add `is_supported` to the `VehicleModel` TS type**

In `resources/js/types/reference.ts`, update:

```typescript
export type VehicleModel = {
    id: number;
    brand_id: number;
    /** null tant que le superadmin ne l'a pas classé. */
    vehicle_type_id: number | null;
    is_supported: boolean;
    name: string;
    brand?: Brand;
    vehicle_type?: VehicleType | null;
};
```

- [ ] **Step 2: Add `Checkbox` import in `VehicleModels.vue`**

In `resources/js/pages/configuration/VehicleModels.vue`, add to the imports (alongside the existing `Input`/`Label` imports):

```typescript
import { Checkbox } from '@/components/ui/checkbox';
```

- [ ] **Step 3: Add `is_supported` to both forms**

Update the two `useForm` calls:

```typescript
const createForm = useForm({
    brand_id: null as number | null,
    vehicle_type_id: null as number | null,
    name: '',
    is_supported: true as boolean,
});

const editForm = useForm({
    brand_id: null as number | null,
    vehicle_type_id: null as number | null,
    name: '',
    is_supported: true as boolean,
});
```

Update `openEdit()` to seed the flag from the model being edited:

```typescript
function openEdit(model: VehicleModel) {
    editing.value = model;
    editForm.clearErrors();
    editForm.brand_id = model.brand_id;
    editForm.vehicle_type_id = model.vehicle_type_id;
    editForm.name = model.name;
    editForm.is_supported = model.is_supported;
}
```

- [ ] **Step 4: Add the checkbox to the create form**

In the create `<form>`, right after the "Nom du modèle" field's closing `</div>` and before the submit `<Button>`, add a full-width row:

```html
            <div class="flex items-center gap-3 sm:col-span-4">
                <Checkbox
                    id="new_is_supported"
                    v-model="createForm.is_supported"
                />
                <Label for="new_is_supported" class="font-normal">
                    Disponible dans le formulaire véhicule
                </Label>
                <InputError :message="createForm.errors.is_supported" />
            </div>
```

- [ ] **Step 5: Add the checkbox to the edit dialog**

In the edit `<Dialog>` form, right after the "Nom" field's closing `</div>` and before the submit `<Button>`, add:

```html
                    <div class="flex items-center gap-3">
                        <Checkbox
                            id="edit_is_supported"
                            v-model="editForm.is_supported"
                        />
                        <Label for="edit_is_supported" class="font-normal">
                            Disponible dans le formulaire véhicule
                        </Label>
                        <InputError :message="editForm.errors.is_supported" />
                    </div>
```

- [ ] **Step 6: Show the flag in the table**

Add a column header between "Type" and "Actions":

```html
                        <th class="p-3">Type</th>
                        <th class="p-3">Disponibilité</th>
                        <th class="p-3 text-right">Actions</th>
```

Add the matching cell between the "Type" `<td>` and the actions `<td>`:

```html
                        <td class="p-3">
                            <Badge v-if="model.is_supported" variant="secondary">
                                Disponible
                            </Badge>
                            <span v-else class="text-muted-foreground">
                                Non disponible
                            </span>
                        </td>
```

Update the empty-state row's `colspan` from `4` to `5`:

```html
                    <tr v-if="props.models.data.length === 0">
                        <td
                            colspan="5"
                            class="p-6 text-center text-muted-foreground"
                        >
```

- [ ] **Step 7: Type-check**

Run: `npm run types:check`
Expected: no errors in `resources/js/types/reference.ts` or `resources/js/pages/configuration/VehicleModels.vue`.

- [ ] **Step 8: Manual verification**

Run: `php artisan serve` and `npm run dev` (or your usual local setup), log in as a super admin, open `/configuration/vehicle-models`:
- Create a model with "Disponible dans le formulaire véhicule" checked and one unchecked; confirm the table badge matches.
- Edit an existing model, toggle the checkbox, save, confirm the badge updates.
- Open the vehicle creation form (`/vehicles/create`) and confirm only classified + disponible models appear in "Modèle".

- [ ] **Step 9: Commit**

```bash
git add resources/js/types/reference.ts resources/js/pages/configuration/VehicleModels.vue
git commit -m "feat: toggle and display is_supported in vehicle model configuration"
```
