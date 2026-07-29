<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Separator } from '@/components/ui/separator';
import { vehicleIdentity } from '@/lib/vehicles';
import RentalConditionForm from '@/pages/vehicles/RentalConditionForm.vue';
import VehicleForm from '@/pages/vehicles/VehicleForm.vue';
import { dashboard } from '@/routes';
import { index } from '@/routes/vehicles';
import type { VehicleModel, VehicleType } from '@/types/reference';
import type { RentalZone } from '@/types/rental';
import type { Vehicle, VehicleStatusOption } from '@/types/vehicle';

defineProps<{
    vehicle: Vehicle;
    statuses: VehicleStatusOption[];
    zones: RentalZone[];
    vehicleModels: VehicleModel[];
    vehicleTypes: VehicleType[];
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
            :description="`${vehicleIdentity(vehicle)} — ${vehicle.registration_number}`"
        />

        <VehicleForm
            :vehicle="vehicle"
            :statuses="statuses"
            :vehicle-models="vehicleModels"
            :vehicle-types="vehicleTypes"
            submit-label="Enregistrer les modifications"
        />

        <Separator />

        <section class="space-y-6">
            <div>
                <h2 class="text-base font-medium">Conditions de location</h2>
                <p class="text-sm text-muted-foreground">
                    Zones de distance et tarifs journaliers, propres à ce
                    véhicule. Cette section s'enregistre séparément de la fiche.
                </p>
            </div>

            <RentalConditionForm :vehicle-id="vehicle.id" :zones="zones" />
        </section>
    </div>
</template>
