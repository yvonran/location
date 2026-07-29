<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
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

interface LegState {
    from_point: string;
    to_point: string;
    distance_km: number | null;
}

function makeLeg(): LegState {
    return { from_point: '', to_point: '', distance_km: null };
}

const form = useForm({
    vehicle_id: null as number | null,
    number_of_days: 1,
    departure_time: '',
    meal_included: false,
    fuel_included: false,
    legs: [makeLeg(), makeLeg()],
});

function addLeg() {
    form.legs.push(makeLeg());
}

function removeLeg(index: number) {
    form.legs.splice(index, 1);
}

function submit() {
    form.transform((data) => ({
        ...data,
        departure_time: data.departure_time || null,
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

            <div class="space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium">Trajet</h3>
                        <p class="text-xs text-muted-foreground">
                            Un tronçon par étape, dans l'ordre (point A → point
                            B, B → C, … retour).
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addLeg"
                    >
                        <Plus class="size-4" />
                        Ajouter un tronçon
                    </Button>
                </div>

                <InputError :message="errors.legs" />

                <div
                    v-for="(leg, index) in form.legs"
                    :key="index"
                    class="grid items-start gap-3 rounded-lg border border-sidebar-border/70 p-3 sm:grid-cols-[1fr_1fr_auto_auto] dark:border-sidebar-border"
                >
                    <div class="grid gap-1">
                        <Label
                            :for="`from_point_${index}`"
                            class="text-xs text-muted-foreground"
                        >
                            Point de départ
                        </Label>
                        <Input
                            :id="`from_point_${index}`"
                            v-model="leg.from_point"
                            placeholder="Ex : Antananarivo"
                        />
                        <InputError
                            :message="errors[`legs.${index}.from_point`]"
                        />
                    </div>

                    <div class="grid gap-1">
                        <Label
                            :for="`to_point_${index}`"
                            class="text-xs text-muted-foreground"
                        >
                            Point d'arrivée
                        </Label>
                        <Input
                            :id="`to_point_${index}`"
                            v-model="leg.to_point"
                            placeholder="Ex : Toamasina"
                        />
                        <InputError
                            :message="errors[`legs.${index}.to_point`]"
                        />
                    </div>

                    <div class="grid gap-1">
                        <Label
                            :for="`distance_${index}`"
                            class="text-xs text-muted-foreground"
                        >
                            Distance (km)
                        </Label>
                        <Input
                            :id="`distance_${index}`"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-28"
                            :model-value="leg.distance_km ?? undefined"
                            @update:model-value="
                                (v) =>
                                    (leg.distance_km =
                                        v === '' || v === null
                                            ? null
                                            : Number(v))
                            "
                        />
                        <InputError
                            :message="errors[`legs.${index}.distance_km`]"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="mt-5 text-destructive hover:text-destructive"
                        :disabled="form.legs.length === 1"
                        title="Retirer ce tronçon"
                        @click="removeLeg(index)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>

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
