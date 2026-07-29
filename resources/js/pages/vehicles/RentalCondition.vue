<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import RentalConditionForm from '@/pages/vehicles/RentalConditionForm.vue';
import { dashboard } from '@/routes';
import { index } from '@/routes/vehicles';
import type {
    RentalConditionThresholds,
    RentalRate,
    RentalZoneOption,
} from '@/types/rental';

defineProps<{
    vehicle: {
        id: number;
        name: string;
        brand: string;
        model: string;
        registration_number: string;
    };
    condition: RentalConditionThresholds;
    rates: RentalRate[];
    zones: RentalZoneOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Véhicules', href: index() },
            { title: 'Conditions de location', href: index() },
        ],
    },
});
</script>

<template>
    <Head :title="`Conditions — ${vehicle.name}`" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Conditions de location"
            :description="`${vehicle.name} — ${vehicle.brand} ${vehicle.model} (${vehicle.registration_number})`"
        />

        <RentalConditionForm
            :vehicle-id="vehicle.id"
            :condition="condition"
            :rates="rates"
            :zones="zones"
        />
    </div>
</template>
