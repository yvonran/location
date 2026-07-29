<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { vehicleIdentity } from '@/lib/vehicles';
import { dashboard } from '@/routes';
import { store } from '@/routes/quotes';

interface Customer {
    id: number;
    name: string;
}

interface Vehicle {
    id: number;
    name: string;
    vehicle_model?: {
        name: string;
        brand?: { name: string } | null;
    } | null;
}

interface RouteOption {
    id: number;
    name: string;
    departure_city: string;
    arrival_city: string;
    distance_km: string;
}

interface ServiceType {
    id: number;
    name: string;
    coefficient: string;
}

interface OptionType {
    id: number;
    name: string;
    default_mode: 'fixed' | 'percentage';
    default_value: string;
}

const props = defineProps<{
    customers: Customer[];
    vehicles: Vehicle[];
    routes: RouteOption[];
    serviceTypes: ServiceType[];
    optionTypes: OptionType[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Nouveau devis', href: store().url },
        ],
    },
});

interface LineOptionState {
    option_type_id: number;
    enabled: boolean;
    mode: 'fixed' | 'percentage';
    value: number;
}

interface LineState {
    vehicle_id: number | null;
    route_id: number | 'none';
    distance_km: number | null;
    service_type_id: number | null;
    start_date: string;
    departure_time: string;
    number_of_days: number;
    discount_type: 'fixed' | 'percentage' | 'none';
    discount_value: number | null;
    options: LineOptionState[];
}

function makeLine(): LineState {
    return {
        vehicle_id: null,
        route_id: 'none',
        distance_km: null,
        service_type_id: null,
        start_date: new Date().toISOString().slice(0, 10),
        departure_time: '',
        number_of_days: 1,
        discount_type: 'none',
        discount_value: null,
        options: props.optionTypes.map((optionType) => ({
            option_type_id: optionType.id,
            enabled: false,
            mode: optionType.default_mode,
            value: Number(optionType.default_value),
        })),
    };
}

const form = useForm({
    customer_id: null as number | null,
    notes: '',
    lines: [makeLine()],
});

function addLine() {
    form.lines.push(makeLine());
}

function removeLine(index: number) {
    form.lines.splice(index, 1);
}

function routeDistance(line: LineState): string | null {
    const route = props.routes.find((r) => r.id === line.route_id);

    return route ? route.distance_km : null;
}

function submit() {
    form.transform((data) => ({
        ...data,
        lines: data.lines.map((line) => ({
            vehicle_id: line.vehicle_id,
            route_id: line.route_id === 'none' ? null : line.route_id,
            distance_km: line.route_id === 'none' ? line.distance_km : null,
            service_type_id: line.service_type_id,
            start_date: line.start_date,
            departure_time: line.departure_time || null,
            number_of_days: line.number_of_days,
            discount_type:
                line.discount_type === 'none' ? null : line.discount_type,
            discount_value:
                line.discount_type !== 'none' ? line.discount_value : null,
            options: line.options
                .filter((option) => option.enabled)
                .map((option) => ({
                    option_type_id: option.option_type_id,
                    mode: option.mode,
                    value: option.value,
                })),
        })),
    })).post(store().url);
}

const lineErrors = computed(() => form.errors as Record<string, string>);
</script>

<template>
    <Head title="Nouveau devis" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Nouveau devis"
            description="Sélectionnez un client, un ou plusieurs véhicules, et générez le devis."
        />

        <InputError :message="lineErrors.lines" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid max-w-sm gap-2">
                <Label for="customer_id">Client</Label>
                <Select v-model="form.customer_id">
                    <SelectTrigger id="customer_id" class="w-full">
                        <SelectValue placeholder="Choisir un client" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="customer in props.customers"
                            :key="customer.id"
                            :value="customer.id"
                        >
                            {{ customer.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="lineErrors.customer_id" />
            </div>

            <div
                v-for="(line, index) in form.lines"
                :key="index"
                class="space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium">
                        Véhicule {{ index + 1 }}
                    </h3>
                    <Button
                        v-if="form.lines.length > 1"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="removeLine(index)"
                    >
                        Retirer
                    </Button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label :for="`vehicle_${index}`">Véhicule</Label>
                        <Select v-model="line.vehicle_id">
                            <SelectTrigger
                                :id="`vehicle_${index}`"
                                class="w-full"
                            >
                                <SelectValue
                                    placeholder="Choisir un véhicule"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="vehicle in props.vehicles"
                                    :key="vehicle.id"
                                    :value="vehicle.id"
                                >
                                    {{ vehicle.name }} ({{
                                        vehicleIdentity(vehicle)
                                    }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="lineErrors[`lines.${index}.vehicle_id`]"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`service_type_${index}`"
                            >Type de prestation</Label
                        >
                        <Select v-model="line.service_type_id">
                            <SelectTrigger
                                :id="`service_type_${index}`"
                                class="w-full"
                            >
                                <SelectValue
                                    placeholder="Choisir une prestation"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="serviceType in props.serviceTypes"
                                    :key="serviceType.id"
                                    :value="serviceType.id"
                                >
                                    {{ serviceType.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="
                                lineErrors[`lines.${index}.service_type_id`]
                            "
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`route_${index}`"
                            >Trajet (optionnel)</Label
                        >
                        <Select v-model="line.route_id">
                            <SelectTrigger
                                :id="`route_${index}`"
                                class="w-full"
                            >
                                <SelectValue
                                    placeholder="Aucun trajet — saisir la distance"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none"
                                    >Aucun trajet — saisir la
                                    distance</SelectItem
                                >
                                <SelectItem
                                    v-for="route in props.routes"
                                    :key="route.id"
                                    :value="route.id"
                                >
                                    {{ route.name }} ({{
                                        route.departure_city
                                    }}
                                    → {{ route.arrival_city }},
                                    {{ route.distance_km }} km)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="lineErrors[`lines.${index}.route_id`]"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`distance_${index}`">Distance (km)</Label>
                        <Input
                            :id="`distance_${index}`"
                            type="number"
                            min="0"
                            step="0.01"
                            :disabled="line.route_id !== 'none'"
                            :model-value="
                                line.route_id !== 'none'
                                    ? (routeDistance(line) ?? undefined)
                                    : (line.distance_km ?? undefined)
                            "
                            @update:model-value="
                                (v) => (line.distance_km = v ? Number(v) : null)
                            "
                        />
                        <InputError
                            :message="lineErrors[`lines.${index}.distance_km`]"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`start_date_${index}`"
                            >Date de début</Label
                        >
                        <Input
                            :id="`start_date_${index}`"
                            v-model="line.start_date"
                            type="date"
                        />
                        <InputError
                            :message="lineErrors[`lines.${index}.start_date`]"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`days_${index}`">Nombre de jours</Label>
                        <Input
                            :id="`days_${index}`"
                            type="number"
                            min="1"
                            :model-value="line.number_of_days"
                            @update:model-value="
                                (v) => (line.number_of_days = Number(v))
                            "
                        />
                        <InputError
                            :message="
                                lineErrors[`lines.${index}.number_of_days`]
                            "
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`departure_time_${index}`"
                            >Heure de départ (optionnel)</Label
                        >
                        <Input
                            :id="`departure_time_${index}`"
                            v-model="line.departure_time"
                            type="time"
                        />
                        <InputError
                            :message="
                                lineErrors[`lines.${index}.departure_time`]
                            "
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label>Options</Label>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div
                            v-for="option in line.options"
                            :key="option.option_type_id"
                            class="flex items-center gap-3 rounded-lg border border-sidebar-border/70 p-2 dark:border-sidebar-border"
                        >
                            <Checkbox v-model="option.enabled" />
                            <span class="flex-1 text-sm">
                                {{
                                    props.optionTypes.find(
                                        (o) => o.id === option.option_type_id,
                                    )?.name
                                }}
                            </span>
                            <Input
                                v-if="option.enabled"
                                type="number"
                                min="0"
                                step="0.01"
                                class="w-24"
                                :model-value="option.value"
                                @update:model-value="
                                    (v) => (option.value = Number(v))
                                "
                            />
                            <span
                                v-if="option.enabled"
                                class="text-xs text-muted-foreground"
                            >
                                {{ option.mode === 'percentage' ? '%' : 'Ar' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label :for="`discount_type_${index}`">Remise</Label>
                        <Select v-model="line.discount_type">
                            <SelectTrigger
                                :id="`discount_type_${index}`"
                                class="w-full"
                            >
                                <SelectValue placeholder="Aucune remise" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none"
                                    >Aucune remise</SelectItem
                                >
                                <SelectItem value="fixed"
                                    >Montant fixe (Ar)</SelectItem
                                >
                                <SelectItem value="percentage"
                                    >Pourcentage (%)</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>

                    <div
                        v-if="line.discount_type !== 'none'"
                        class="grid gap-2"
                    >
                        <Label :for="`discount_value_${index}`"
                            >Valeur de la remise</Label
                        >
                        <Input
                            :id="`discount_value_${index}`"
                            type="number"
                            min="0"
                            step="0.01"
                            :model-value="line.discount_value ?? undefined"
                            @update:model-value="
                                (v) =>
                                    (line.discount_value = v ? Number(v) : null)
                            "
                        />
                        <InputError
                            :message="
                                lineErrors[`lines.${index}.discount_value`]
                            "
                        />
                    </div>
                </div>
            </div>

            <Button type="button" variant="outline" @click="addLine">
                Ajouter un véhicule
            </Button>

            <div class="grid max-w-xl gap-2">
                <Label for="notes">Notes (optionnel)</Label>
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="3"
                    class="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
                ></textarea>
            </div>

            <Button type="submit" :disabled="form.processing"
                >Générer le devis</Button
            >
        </form>
    </div>
</template>
