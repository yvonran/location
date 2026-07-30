<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { index } from '@/routes/simulations';

interface VehicleRef {
    id: number;
    name: string;
    vehicle_model?: {
        name: string;
        brand?: { name: string } | null;
    } | null;
}

interface SimulationLeg {
    id: number;
    position: number;
    direction: 'outbound' | 'return';
    from_point: string;
    to_point: string;
    distance_km: string;
}

interface SimulationProps {
    id: number;
    number_of_days: number;
    departure_time: string | null;
    distance_km: string;
    same_return_route: boolean;
    daily_rate: string;
    meal_included: boolean;
    fuel_included: boolean;
    meal_cost: string;
    fuel_cost: string;
    vehicle_amount: string;
    total: string;
    vehicle: VehicleRef;
    legs: SimulationLeg[];
}

const props = defineProps<{ simulation: SimulationProps }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Simulations', href: index() },
        ],
    },
});

function formatAr(value: string | number): string {
    return `${Number(value).toLocaleString('fr-FR')} Ar`;
}
</script>

<template>
    <Head :title="`Simulation #${props.simulation.id}`" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            :title="`Simulation #${props.simulation.id}`"
            :description="`${props.simulation.vehicle.name} — ${props.simulation.number_of_days} jour(s)`"
        />

        <div
            class="space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <div>
                <h2 class="text-sm font-medium">Aller</h2>
                <ol class="space-y-1 text-sm">
                    <li
                        v-for="leg in props.simulation.legs.filter(
                            (leg) => leg.direction === 'outbound',
                        )"
                        :key="leg.id"
                    >
                        {{ leg.from_point }} → {{ leg.to_point }} ({{
                            leg.distance_km
                        }}
                        km)
                    </li>
                </ol>
            </div>
            <div>
                <h2 class="text-sm font-medium">
                    Retour
                    <Badge v-if="props.simulation.same_return_route" variant="secondary">
                        Même trajet que l'aller
                    </Badge>
                </h2>
                <ol class="space-y-1 text-sm">
                    <li
                        v-for="leg in props.simulation.legs.filter(
                            (leg) => leg.direction === 'return',
                        )"
                        :key="leg.id"
                    >
                        {{ leg.from_point }} → {{ leg.to_point }} ({{
                            leg.distance_km
                        }}
                        km)
                    </li>
                </ol>
            </div>
            <p class="text-xs text-muted-foreground">
                Distance totale : {{ props.simulation.distance_km }} km
            </p>
        </div>

        <dl
            class="grid grid-cols-2 gap-4 rounded-xl border border-sidebar-border/70 p-4 text-sm sm:grid-cols-4 dark:border-sidebar-border"
        >
            <div>
                <dt class="text-muted-foreground">Tarif journalier</dt>
                <dd>{{ formatAr(props.simulation.daily_rate) }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Montant véhicule</dt>
                <dd>{{ formatAr(props.simulation.vehicle_amount) }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Carburant</dt>
                <dd>
                    <Badge
                        v-if="props.simulation.fuel_included"
                        variant="secondary"
                    >
                        À la charge de l'agence
                    </Badge>
                    <span v-else>{{
                        formatAr(props.simulation.fuel_cost)
                    }}</span>
                </dd>
            </div>
            <div>
                <dt class="text-muted-foreground">Repas client</dt>
                <dd>
                    <Badge
                        v-if="props.simulation.meal_included"
                        variant="secondary"
                    >
                        À la charge de l'agence
                    </Badge>
                    <span v-else>{{
                        formatAr(props.simulation.meal_cost)
                    }}</span>
                </dd>
            </div>
        </dl>

        <div class="text-lg font-semibold">
            Total : {{ formatAr(props.simulation.total) }}
        </div>
    </div>
</template>
