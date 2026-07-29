<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { toZoneInputs } from '@/lib/rentalZones';
import RentalZonesEditor from '@/pages/vehicles/RentalZonesEditor.vue';
import { update } from '@/routes/vehicles/conditions';
import type { RentalZone } from '@/types/rental';

const props = defineProps<{
    vehicleId: number;
    zones: RentalZone[];
}>();

const form = useForm({
    zones: toZoneInputs(props.zones),
});

const errors = computed(() => form.errors as Record<string, string>);

function submit() {
    form.put(update(props.vehicleId).url);
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <RentalZonesEditor v-model="form.zones" :errors="errors" />

        <Button type="submit" :disabled="form.processing">
            Enregistrer les conditions
        </Button>
    </form>
</template>
