<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { vehicleIdentity } from '@/lib/vehicles';
import { dashboard } from '@/routes';
import { index } from '@/routes/quotes';

interface OptionTypeRef {
    id: number;
    name: string;
}

interface QuoteLineOption {
    id: number;
    mode: 'fixed' | 'percentage';
    value: string;
    amount: string;
    option_type: OptionTypeRef;
}

interface VehicleRef {
    id: number;
    name: string;
    vehicle_model?: {
        name: string;
        brand?: { name: string } | null;
    } | null;
}

interface RouteRef {
    id: number;
    name: string;
    departure_city: string;
    arrival_city: string;
}

interface ServiceTypeRef {
    id: number;
    name: string;
}

interface QuoteLine {
    id: number;
    start_date: string;
    departure_time: string | null;
    number_of_days: number;
    distance_km: string;
    daily_rate: string;
    service_coefficient: string;
    discount_type: 'fixed' | 'percentage' | null;
    discount_value: string | null;
    discount_amount: string;
    options_amount: string;
    line_total: string;
    vehicle: VehicleRef;
    route: RouteRef | null;
    service_type: ServiceTypeRef;
    quote_line_options: QuoteLineOption[];
}

interface Quote {
    id: number;
    number: string;
    quote_date: string;
    status: 'draft' | 'sent' | 'accepted' | 'rejected';
    subtotal: string;
    total: string;
    notes: string | null;
    customer: { id: number; name: string };
    user: { id: number; name: string };
    quote_lines: QuoteLine[];
}

const props = defineProps<{ quote: Quote }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Devis', href: index().url },
        ],
    },
});

function formatAr(value: string | number): string {
    return `${Number(value).toLocaleString('fr-FR')} Ar`;
}

const statusLabels: Record<Quote['status'], string> = {
    draft: 'Brouillon',
    sent: 'Envoyé',
    accepted: 'Accepté',
    rejected: 'Refusé',
};
</script>

<template>
    <Head :title="`Devis ${quote.number}`" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                :title="`Devis ${quote.number}`"
                :description="quote.quote_date"
            />
            <Badge>{{ statusLabels[quote.status] }}</Badge>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <h3 class="mb-2 text-sm font-medium text-muted-foreground">
                    Client
                </h3>
                <p>{{ quote.customer.name }}</p>
            </div>
            <div
                class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <h3 class="mb-2 text-sm font-medium text-muted-foreground">
                    Agent
                </h3>
                <p>{{ quote.user.name }}</p>
            </div>
        </div>

        <div
            v-for="line in quote.quote_lines"
            :key="line.id"
            class="space-y-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <div class="flex items-center justify-between">
                <h3 class="font-medium">
                    {{ line.vehicle.name }} ({{
                        vehicleIdentity(line.vehicle)
                    }})
                </h3>
                <span class="text-sm text-muted-foreground">{{
                    line.service_type.name
                }}</span>
            </div>

            <p v-if="line.route" class="text-sm text-muted-foreground">
                Trajet : {{ line.route.name }} ({{
                    line.route.departure_city
                }}
                → {{ line.route.arrival_city }})
            </p>

            <dl class="grid grid-cols-2 gap-2 text-sm md:grid-cols-4">
                <div>
                    <dt class="text-muted-foreground">Distance</dt>
                    <dd>{{ line.distance_km }} km</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Jours</dt>
                    <dd>{{ line.number_of_days }}</dd>
                </div>
                <div v-if="line.departure_time">
                    <dt class="text-muted-foreground">Départ</dt>
                    <dd>{{ line.departure_time }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Prix / jour</dt>
                    <dd>{{ formatAr(line.daily_rate) }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Coefficient</dt>
                    <dd>{{ line.service_coefficient }}</dd>
                </div>
            </dl>

            <ul v-if="line.quote_line_options.length" class="space-y-1 text-sm">
                <li
                    v-for="option in line.quote_line_options"
                    :key="option.id"
                    class="flex justify-between"
                >
                    <span>{{ option.option_type.name }}</span>
                    <span>{{ formatAr(option.amount) }}</span>
                </li>
            </ul>

            <div
                v-if="line.discount_type"
                class="flex justify-between text-sm text-muted-foreground"
            >
                <span
                    >Remise ({{
                        line.discount_type === 'percentage'
                            ? `${line.discount_value}%`
                            : 'fixe'
                    }})</span
                >
                <span>-{{ formatAr(line.discount_amount) }}</span>
            </div>

            <div
                class="flex justify-between border-t border-sidebar-border/70 pt-2 font-medium dark:border-sidebar-border"
            >
                <span>Total ligne</span>
                <span>{{ formatAr(line.line_total) }}</span>
            </div>
        </div>

        <div
            class="flex justify-end rounded-xl border border-sidebar-border/70 p-4 text-lg font-semibold dark:border-sidebar-border"
        >
            Total du devis : {{ formatAr(quote.total) }}
        </div>

        <Link
            :href="index().url"
            class="text-sm text-muted-foreground underline"
        >
            Retour à la liste des devis
        </Link>
    </div>
</template>
