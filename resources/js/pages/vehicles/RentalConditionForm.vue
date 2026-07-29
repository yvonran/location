<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/vehicles/conditions';
import type {
    RentalConditionThresholds,
    RentalRate,
    RentalZone,
    RentalZoneOption,
} from '@/types/rental';

const props = defineProps<{
    vehicleId: number;
    condition: RentalConditionThresholds;
    rates: RentalRate[];
    zones: RentalZoneOption[];
}>();

interface RateRow {
    zone: RentalZone;
    min_days: number;
    max_days: number | null;
    daily_rate: number | null;
}

const form = useForm({
    city_max_km: props.condition.city_max_km,
    suburb_max_km: props.condition.suburb_max_km,
    long_distance_max_km: props.condition.long_distance_max_km,
    rates: props.rates.map<RateRow>((rate) => ({
        zone: rate.zone,
        min_days: rate.min_days,
        max_days: rate.max_days,
        daily_rate: Number(rate.daily_rate),
    })),
});

/**
 * Les lignes sont stockées à plat pour que les index correspondent exactement
 * aux clés d'erreur renvoyées par le serveur (`rates.3.min_days`).
 */
function rowsFor(zone: RentalZone) {
    return form.rates
        .map((rate, index) => ({ rate, index }))
        .filter((entry) => entry.rate.zone === zone);
}

function addRow(zone: RentalZone) {
    form.rates.push({
        zone,
        min_days: 1,
        max_days: null,
        daily_rate: null,
    });
}

function removeRow(index: number) {
    form.rates.splice(index, 1);
}

/** Bornes affichées sous chaque zone, recalculées à la saisie des seuils. */
const zoneBounds = computed<Record<RentalZone, string>>(() => ({
    city: `Trajet aller ≤ ${form.city_max_km} km`,
    suburb: `Trajet aller > ${form.city_max_km} km et ≤ ${form.suburb_max_km} km`,
    long_distance: `Trajet aller > ${form.suburb_max_km} km et ≤ ${form.long_distance_max_km} km`,
    very_long_distance: `Trajet aller > ${form.long_distance_max_km} km`,
}));

const errors = computed(() => form.errors as Record<string, string>);

function submit() {
    form.put(update(props.vehicleId).url);
}
</script>

<template>
    <form class="space-y-8" @submit.prevent="submit">
        <section class="space-y-4">
            <div>
                <h2 class="text-sm font-medium">Découpage par distance</h2>
                <p class="text-sm text-muted-foreground">
                    La zone est déterminée par la distance du trajet aller.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="city_max_km">Ville — jusqu'à (km)</Label>
                    <Input
                        id="city_max_km"
                        type="number"
                        min="1"
                        :model-value="form.city_max_km"
                        @update:model-value="
                            (v) => (form.city_max_km = Number(v))
                        "
                    />
                    <InputError :message="errors.city_max_km" />
                </div>

                <div class="grid gap-2">
                    <Label for="suburb_max_km">Périphérie — jusqu'à (km)</Label>
                    <Input
                        id="suburb_max_km"
                        type="number"
                        min="1"
                        :model-value="form.suburb_max_km"
                        @update:model-value="
                            (v) => (form.suburb_max_km = Number(v))
                        "
                    />
                    <InputError :message="errors.suburb_max_km" />
                </div>

                <div class="grid gap-2">
                    <Label for="long_distance_max_km">
                        Longue distance — jusqu'à (km)
                    </Label>
                    <Input
                        id="long_distance_max_km"
                        type="number"
                        min="1"
                        :model-value="form.long_distance_max_km"
                        @update:model-value="
                            (v) => (form.long_distance_max_km = Number(v))
                        "
                    />
                    <InputError :message="errors.long_distance_max_km" />
                </div>
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <h2 class="text-sm font-medium">Tarifs journaliers</h2>
                <p class="text-sm text-muted-foreground">
                    Pour chaque zone, définissez un tarif par tranche de durée.
                    Laissez « jusqu'à » vide pour une tranche sans limite haute.
                </p>
            </div>

            <div
                v-for="zone in zones"
                :key="zone.value"
                class="space-y-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium">{{ zone.label }}</h3>
                        <p class="text-xs text-muted-foreground">
                            {{ zoneBounds[zone.value] }}
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addRow(zone.value)"
                    >
                        <Plus class="size-4" />
                        Ajouter une tranche
                    </Button>
                </div>

                <p
                    v-if="rowsFor(zone.value).length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Aucun tarif défini pour cette zone.
                </p>

                <div
                    v-for="entry in rowsFor(zone.value)"
                    :key="entry.index"
                    class="grid items-start gap-3 sm:grid-cols-[1fr_1fr_1.5fr_auto]"
                >
                    <div class="grid gap-1">
                        <Label
                            :for="`min_days_${entry.index}`"
                            class="text-xs text-muted-foreground"
                        >
                            À partir de (jours)
                        </Label>
                        <Input
                            :id="`min_days_${entry.index}`"
                            type="number"
                            min="1"
                            :model-value="entry.rate.min_days"
                            @update:model-value="
                                (v) => (entry.rate.min_days = Number(v))
                            "
                        />
                        <InputError
                            :message="errors[`rates.${entry.index}.min_days`]"
                        />
                    </div>

                    <div class="grid gap-1">
                        <Label
                            :for="`max_days_${entry.index}`"
                            class="text-xs text-muted-foreground"
                        >
                            Jusqu'à (jours)
                        </Label>
                        <Input
                            :id="`max_days_${entry.index}`"
                            type="number"
                            min="1"
                            placeholder="Sans limite"
                            :model-value="entry.rate.max_days ?? undefined"
                            @update:model-value="
                                (v) =>
                                    (entry.rate.max_days =
                                        v === '' || v === null
                                            ? null
                                            : Number(v))
                            "
                        />
                        <InputError
                            :message="errors[`rates.${entry.index}.max_days`]"
                        />
                    </div>

                    <div class="grid gap-1">
                        <Label
                            :for="`daily_rate_${entry.index}`"
                            class="text-xs text-muted-foreground"
                        >
                            Tarif journalier (Ar)
                        </Label>
                        <Input
                            :id="`daily_rate_${entry.index}`"
                            type="number"
                            min="0"
                            step="0.01"
                            :model-value="entry.rate.daily_rate ?? undefined"
                            @update:model-value="
                                (v) =>
                                    (entry.rate.daily_rate =
                                        v === '' || v === null
                                            ? null
                                            : Number(v))
                            "
                        />
                        <InputError
                            :message="errors[`rates.${entry.index}.daily_rate`]"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="mt-5 text-destructive hover:text-destructive"
                        title="Retirer cette tranche"
                        @click="removeRow(entry.index)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>
        </section>

        <Button type="submit" :disabled="form.processing">
            Enregistrer les conditions
        </Button>
    </form>
</template>
