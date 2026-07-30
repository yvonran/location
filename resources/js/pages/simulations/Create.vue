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
import { store } from '@/routes/simulations';
import TripLegsEditor from './TripLegsEditor.vue';

interface Vehicle {
    id: number;
    name: string;
    vehicle_model?: {
        name: string;
        brand?: { name: string } | null;
    } | null;
}

const props = defineProps<{ vehicles: Vehicle[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Nouvelle simulation', href: store().url },
        ],
    },
});

function makeLeg() {
    return { from_point: '', to_point: '', distance_km: null as number | null };
}

const form = useForm({
    vehicle_id: null as number | null,
    number_of_days: 1,
    departure_time: '',
    meal_included: false,
    fuel_included: false,
    same_return_route: true,
    legs: {
        outbound: [makeLeg()],
        return: [makeLeg()],
    },
});

function submit() {
    form.transform((data) => ({
        ...data,
        departure_time: data.departure_time || null,
        legs: data.same_return_route
            ? { outbound: data.legs.outbound }
            : data.legs,
    })).post(store().url);
}

const errors = computed(() => form.errors as Record<string, string>);
</script>

<template>
    <Head title="Nouvelle simulation" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Nouvelle simulation"
            description="Estimez rapidement le coût d'un trajet pour un véhicule, sans créer de devis."
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="vehicle_id">Véhicule</Label>
                    <Select v-model="form.vehicle_id">
                        <SelectTrigger id="vehicle_id" class="w-full">
                            <SelectValue placeholder="Choisir un véhicule" />
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
                    <InputError :message="errors.vehicle_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="number_of_days">Nombre de jours</Label>
                    <Input
                        id="number_of_days"
                        type="number"
                        min="1"
                        :model-value="form.number_of_days"
                        @update:model-value="
                            (v) => (form.number_of_days = Number(v))
                        "
                    />
                    <InputError :message="errors.number_of_days" />
                </div>

                <div class="grid gap-2">
                    <Label for="departure_time"
                        >Heure de départ (optionnel)</Label
                    >
                    <Input
                        id="departure_time"
                        v-model="form.departure_time"
                        type="time"
                    />
                    <InputError :message="errors.departure_time" />
                </div>
            </div>

            <TripLegsEditor
                v-model="form.legs.outbound"
                title="Aller"
                prefix="legs.outbound"
                :errors="errors"
            />

            <div
                class="flex items-center gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
            >
                <Checkbox
                    id="same_return_route"
                    v-model="form.same_return_route"
                />
                <Label for="same_return_route" class="font-normal">
                    Utiliser le même trajet pour le retour
                </Label>
            </div>

            <TripLegsEditor
                v-if="!form.same_return_route"
                v-model="form.legs.return"
                title="Retour"
                prefix="legs.return"
                :errors="errors"
            />

            <div class="grid gap-3 sm:grid-cols-2">
                <div
                    class="flex items-center gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <Checkbox id="meal_included" v-model="form.meal_included" />
                    <Label for="meal_included" class="font-normal">
                        Repas client à ma charge
                    </Label>
                </div>

                <div
                    class="flex items-center gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <Checkbox id="fuel_included" v-model="form.fuel_included" />
                    <Label for="fuel_included" class="font-normal">
                        Carburant à ma charge
                    </Label>
                </div>
            </div>
            <p class="text-xs text-muted-foreground">
                Si une case n'est pas cochée, son coût est calculé
                automatiquement.
            </p>

            <Button type="submit" :disabled="form.processing">
                Calculer la simulation
            </Button>
        </form>
    </div>
</template>
