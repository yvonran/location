<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import VehicleForm from '@/pages/vehicles/VehicleForm.vue';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/vehicles';
import type { VehicleStatusOption } from '@/types/vehicle';

defineProps<{ statuses: VehicleStatusOption[] }>();

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

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Nouveau véhicule"
            description="Renseignez les informations et ajoutez une photo."
        />

        <VehicleForm :statuses="statuses" submit-label="Ajouter le véhicule" />
    </div>
</template>
