<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import VehicleForm from '@/pages/vehicles/VehicleForm.vue';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/vehicles';
import type { VehicleModel, VehicleType } from '@/types/reference';
import type { RentalZone } from '@/types/rental';
import type { VehicleStatusOption } from '@/types/vehicle';

defineProps<{
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
            { title: 'Nouveau véhicule', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Nouveau véhicule" />

    <div class="flex flex-col space-y-8 p-4">
        <Heading
            title="Nouveau véhicule"
            description="Renseignez la fiche, ajoutez une photo et définissez vos zones tarifaires."
        />

        <VehicleForm
            :statuses="statuses"
            :zones="zones"
            :vehicle-models="vehicleModels"
            :vehicle-types="vehicleTypes"
            submit-label="Ajouter le véhicule"
        />
    </div>
</template>
