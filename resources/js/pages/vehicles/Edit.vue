<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import VehicleForm from '@/pages/vehicles/VehicleForm.vue';
import { dashboard } from '@/routes';
import { index } from '@/routes/vehicles';
import type { Vehicle, VehicleStatusOption } from '@/types/vehicle';

defineProps<{ vehicle: Vehicle; statuses: VehicleStatusOption[] }>();

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

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            :title="vehicle.name"
            :description="`${vehicle.brand} ${vehicle.model} — ${vehicle.registration_number}`"
        />

        <VehicleForm
            :vehicle="vehicle"
            :statuses="statuses"
            submit-label="Enregistrer les modifications"
        />
    </div>
</template>
