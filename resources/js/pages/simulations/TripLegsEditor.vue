<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface LegState {
    from_point: string;
    to_point: string;
    distance_km: number | null;
}

const props = defineProps<{
    title: string;
    prefix: string;
    errors: Record<string, string>;
}>();

const legs = defineModel<LegState[]>({ required: true });

function addLeg() {
    legs.value = [
        ...legs.value,
        { from_point: '', to_point: '', distance_km: null },
    ];
}

function removeLeg(index: number) {
    legs.value = legs.value.filter((_, i) => i !== index);
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-medium">{{ props.title }}</h3>
                <p class="text-xs text-muted-foreground">
                    Un tronçon par étape, dans l'ordre (point A → point B, B →
                    C, …).
                </p>
            </div>
            <Button type="button" variant="outline" size="sm" @click="addLeg">
                <Plus class="size-4" />
                Ajouter un tronçon
            </Button>
        </div>

        <InputError :message="errors[props.prefix]" />

        <div
            v-for="(leg, index) in legs"
            :key="index"
            class="grid items-start gap-3 rounded-lg border border-sidebar-border/70 p-3 sm:grid-cols-[1fr_1fr_auto_auto] dark:border-sidebar-border"
        >
            <div class="grid gap-1">
                <Label
                    :for="`${prefix}_from_point_${index}`"
                    class="text-xs text-muted-foreground"
                >
                    Point de départ
                </Label>
                <Input
                    :id="`${prefix}_from_point_${index}`"
                    v-model="leg.from_point"
                    placeholder="Ex : Antananarivo"
                />
                <InputError
                    :message="errors[`${prefix}.${index}.from_point`]"
                />
            </div>

            <div class="grid gap-1">
                <Label
                    :for="`${prefix}_to_point_${index}`"
                    class="text-xs text-muted-foreground"
                >
                    Point d'arrivée
                </Label>
                <Input
                    :id="`${prefix}_to_point_${index}`"
                    v-model="leg.to_point"
                    placeholder="Ex : Toamasina"
                />
                <InputError :message="errors[`${prefix}.${index}.to_point`]" />
            </div>

            <div class="grid gap-1">
                <Label
                    :for="`${prefix}_distance_${index}`"
                    class="text-xs text-muted-foreground"
                >
                    Distance (km)
                </Label>
                <Input
                    :id="`${prefix}_distance_${index}`"
                    type="number"
                    min="0"
                    step="0.01"
                    class="w-28"
                    :model-value="leg.distance_km ?? undefined"
                    @update:model-value="
                        (v) =>
                            (leg.distance_km =
                                v === '' || v === null ? null : Number(v))
                    "
                />
                <InputError
                    :message="errors[`${prefix}.${index}.distance_km`]"
                />
            </div>

            <Button
                type="button"
                variant="ghost"
                size="sm"
                class="mt-5 text-destructive hover:text-destructive"
                :disabled="legs.length === 1"
                title="Retirer ce tronçon"
                @click="removeLeg(index)"
            >
                <Trash2 class="size-4" />
            </Button>
        </div>
    </div>
</template>
