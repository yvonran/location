<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/vehicles/conditions';
import type { RentalZone } from '@/types/rental';

const props = defineProps<{
    vehicleId: number;
    zones: RentalZone[];
}>();

interface RateRow {
    min_days: number;
    max_days: number | null;
    daily_rate: number | null;
}

interface ZoneRow {
    name: string;
    max_km: number | null;
    rates: RateRow[];
}

const form = useForm({
    zones: props.zones.map<ZoneRow>((zone) => ({
        name: zone.name,
        max_km: zone.max_km,
        rates: zone.rates.map((rate) => ({
            min_days: rate.min_days,
            max_days: rate.max_days,
            daily_rate: Number(rate.daily_rate),
        })),
    })),
});

const errors = computed(() => form.errors as Record<string, string>);

function isLastZone(index: number): boolean {
    return index === form.zones.length - 1;
}

/**
 * La zone ajoutée devient la dernière : elle reprend le rôle de zone ouverte et
 * celle qui l'était doit désormais porter une borne.
 */
function addZone() {
    const previousLast = form.zones[form.zones.length - 1];
    const previousMax = form.zones[form.zones.length - 2]?.max_km ?? 0;

    if (previousLast && previousLast.max_km === null) {
        previousLast.max_km = previousMax + 100;
    }

    form.zones.push({ name: '', max_km: null, rates: [] });
}

function removeZone(index: number) {
    form.zones.splice(index, 1);

    // La nouvelle dernière zone reprend le rôle de zone ouverte.
    const last = form.zones[form.zones.length - 1];

    if (last) {
        last.max_km = null;
    }
}

function addRate(zone: ZoneRow) {
    zone.rates.push({ min_days: 1, max_days: null, daily_rate: null });
}

function removeRate(zone: ZoneRow, index: number) {
    zone.rates.splice(index, 1);
}

/** Borne basse implicite d'une zone : la borne haute de la précédente. */
function lowerBound(index: number): number {
    return index === 0 ? 0 : (form.zones[index - 1]?.max_km ?? 0);
}

function boundsLabel(index: number): string {
    const zone = form.zones[index];
    const from = lowerBound(index);

    if (zone.max_km === null) {
        return `Trajet aller > ${from} km`;
    }

    return index === 0
        ? `Trajet aller ≤ ${zone.max_km} km`
        : `Trajet aller > ${from} km et ≤ ${zone.max_km} km`;
}

function submit() {
    form.put(update(props.vehicleId).url);
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-medium">Zones de distance</h2>
                <p class="text-sm text-muted-foreground">
                    Définissez vos propres tranches, dans l'ordre croissant. La
                    dernière n'a pas de limite et couvre les trajets les plus
                    longs.
                </p>
            </div>
            <Button type="button" variant="outline" size="sm" @click="addZone">
                <Plus class="size-4" />
                Ajouter une zone
            </Button>
        </div>

        <InputError :message="errors.zones" />

        <div
            v-for="(zone, zoneIndex) in form.zones"
            :key="zoneIndex"
            class="space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
        >
            <div class="grid gap-3 sm:grid-cols-[2fr_1fr_auto]">
                <div class="grid gap-1">
                    <Label
                        :for="`zone_name_${zoneIndex}`"
                        class="text-xs text-muted-foreground"
                    >
                        Nom de la zone
                    </Label>
                    <Input
                        :id="`zone_name_${zoneIndex}`"
                        v-model="zone.name"
                        placeholder="Ex : Ville"
                    />
                    <InputError :message="errors[`zones.${zoneIndex}.name`]" />
                </div>

                <div class="grid gap-1">
                    <Label
                        :for="`zone_max_km_${zoneIndex}`"
                        class="text-xs text-muted-foreground"
                    >
                        Jusqu'à (km)
                    </Label>
                    <Input
                        :id="`zone_max_km_${zoneIndex}`"
                        type="number"
                        min="1"
                        :disabled="isLastZone(zoneIndex)"
                        :placeholder="
                            isLastZone(zoneIndex) ? 'Sans limite' : '50'
                        "
                        :model-value="zone.max_km ?? undefined"
                        @update:model-value="
                            (v) =>
                                (zone.max_km =
                                    v === '' || v === null ? null : Number(v))
                        "
                    />
                    <InputError
                        :message="errors[`zones.${zoneIndex}.max_km`]"
                    />
                </div>

                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="mt-5 text-destructive hover:text-destructive"
                    :disabled="form.zones.length === 1"
                    title="Retirer cette zone"
                    @click="removeZone(zoneIndex)"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>

            <p class="text-xs text-muted-foreground">
                {{ boundsLabel(zoneIndex) }}
            </p>

            <div
                class="space-y-3 border-t border-sidebar-border/70 pt-3 dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-xs font-medium">Tarifs journaliers</h3>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        @click="addRate(zone)"
                    >
                        <Plus class="size-4" />
                        Ajouter une tranche
                    </Button>
                </div>

                <p
                    v-if="zone.rates.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    Aucun tarif défini pour cette zone.
                </p>

                <div
                    v-for="(rate, rateIndex) in zone.rates"
                    :key="rateIndex"
                    class="grid items-start gap-3 sm:grid-cols-[1fr_1fr_1.5fr_auto]"
                >
                    <div class="grid gap-1">
                        <Label
                            :for="`min_days_${zoneIndex}_${rateIndex}`"
                            class="text-xs text-muted-foreground"
                        >
                            À partir de (jours)
                        </Label>
                        <Input
                            :id="`min_days_${zoneIndex}_${rateIndex}`"
                            type="number"
                            min="1"
                            :model-value="rate.min_days"
                            @update:model-value="
                                (v) => (rate.min_days = Number(v))
                            "
                        />
                        <InputError
                            :message="
                                errors[
                                    `zones.${zoneIndex}.rates.${rateIndex}.min_days`
                                ]
                            "
                        />
                    </div>

                    <div class="grid gap-1">
                        <Label
                            :for="`max_days_${zoneIndex}_${rateIndex}`"
                            class="text-xs text-muted-foreground"
                        >
                            Jusqu'à (jours)
                        </Label>
                        <Input
                            :id="`max_days_${zoneIndex}_${rateIndex}`"
                            type="number"
                            min="1"
                            placeholder="Sans limite"
                            :model-value="rate.max_days ?? undefined"
                            @update:model-value="
                                (v) =>
                                    (rate.max_days =
                                        v === '' || v === null
                                            ? null
                                            : Number(v))
                            "
                        />
                        <InputError
                            :message="
                                errors[
                                    `zones.${zoneIndex}.rates.${rateIndex}.max_days`
                                ]
                            "
                        />
                    </div>

                    <div class="grid gap-1">
                        <Label
                            :for="`daily_rate_${zoneIndex}_${rateIndex}`"
                            class="text-xs text-muted-foreground"
                        >
                            Tarif journalier (Ar)
                        </Label>
                        <Input
                            :id="`daily_rate_${zoneIndex}_${rateIndex}`"
                            type="number"
                            min="0"
                            step="0.01"
                            :model-value="rate.daily_rate ?? undefined"
                            @update:model-value="
                                (v) =>
                                    (rate.daily_rate =
                                        v === '' || v === null
                                            ? null
                                            : Number(v))
                            "
                        />
                        <InputError
                            :message="
                                errors[
                                    `zones.${zoneIndex}.rates.${rateIndex}.daily_rate`
                                ]
                            "
                        />
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="mt-5 text-destructive hover:text-destructive"
                        title="Retirer cette tranche"
                        @click="removeRate(zone, rateIndex)"
                    >
                        <Trash2 class="size-4" />
                    </Button>
                </div>
            </div>
        </div>

        <Button type="submit" :disabled="form.processing">
            Enregistrer les conditions
        </Button>
    </form>
</template>
