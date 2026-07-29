<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { create, show } from '@/routes/simulations';

interface SimulationRow {
    id: number;
    number_of_days: number;
    distance_km: string;
    total: string;
    vehicle: { id: number; name: string };
}

interface Paginated<T> {
    data: T[];
}

defineProps<{ simulations: Paginated<SimulationRow> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Simulations', href: create().url },
        ],
    },
});

function formatAr(value: string | number): string {
    return `${Number(value).toLocaleString('fr-FR')} Ar`;
}
</script>

<template>
    <Head title="Simulations" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Simulations"
                description="Historique des estimations réalisées."
            />
            <Button as-child>
                <Link :href="create().url">Nouvelle simulation</Link>
            </Button>
        </div>

        <div
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead
                    class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="p-3">Véhicule</th>
                        <th class="p-3">Jours</th>
                        <th class="p-3">Distance</th>
                        <th class="p-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="simulation in simulations.data"
                        :key="simulation.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="p-3">
                            <Link
                                :href="show(simulation.id).url"
                                class="underline"
                                >{{ simulation.vehicle.name }}</Link
                            >
                        </td>
                        <td class="p-3">{{ simulation.number_of_days }}</td>
                        <td class="p-3">{{ simulation.distance_km }} km</td>
                        <td class="p-3 text-right">
                            {{ formatAr(simulation.total) }}
                        </td>
                    </tr>
                    <tr v-if="simulations.data.length === 0">
                        <td
                            colspan="4"
                            class="p-6 text-center text-muted-foreground"
                        >
                            Aucune simulation pour l'instant.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
