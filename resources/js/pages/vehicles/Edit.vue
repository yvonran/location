<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Separator } from '@/components/ui/separator';
import RentalConditionForm from '@/pages/vehicles/RentalConditionForm.vue';
import VehicleForm from '@/pages/vehicles/VehicleForm.vue';
import { dashboard } from '@/routes';
import { index } from '@/routes/vehicles';
import type {
    RentalConditionThresholds,
    RentalRate,
    RentalZoneOption,
} from '@/types/rental';
import type { Vehicle, VehicleStatusOption } from '@/types/vehicle';

defineProps<{
    vehicle: Vehicle;
    statuses: VehicleStatusOption[];
    condition: RentalConditionThresholds;
    rates: RentalRate[];
    zones: RentalZoneOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Véhicules', href: index() },
            { title: 'Modifier', href: index() },
        ],
    },
});
</script>

<template>
    <Head :title="`Modifier ${vehicle.name}`" />

    <div class="flex flex-col space-y-8 p-4">
        <Heading
            :title="vehicle.name"
            :description="`${vehicle.brand} ${vehicle.model} — ${vehicle.registration_number}`"
        />

        <VehicleForm
            :vehicle="vehicle"
            :statuses="statuses"
            submit-label="Enregistrer les modifications"
        />

        <Separator />

        <section class="space-y-6">
            <div>
                <h2 class="text-base font-medium">Conditions de location</h2>
                <p class="text-sm text-muted-foreground">
                    Tarifs journaliers par zone de distance. Cette section
                    s'enregistre séparément de la fiche du véhicule.
                </p>
            </div>

            <RentalConditionForm
                :vehicle-id="vehicle.id"
                :condition="condition"
                :rates="rates"
                :zones="zones"
            />
        </section>
    </div>
</template>
