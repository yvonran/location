<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ImageUp, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import { store, update } from '@/routes/vehicles';
import type { Vehicle, VehicleStatusOption } from '@/types/vehicle';

const props = defineProps<{
    vehicle?: Vehicle;
    statuses: VehicleStatusOption[];
    submitLabel: string;
}>();

const form = useForm({
    name: props.vehicle?.name ?? '',
    brand: props.vehicle?.brand ?? '',
    model: props.vehicle?.model ?? '',
    seats: props.vehicle?.seats ?? 4,
    registration_number: props.vehicle?.registration_number ?? '',
    year: props.vehicle?.year ?? new Date().getFullYear(),
    has_air_conditioning: props.vehicle?.has_air_conditioning ?? true,
    status: props.vehicle?.status ?? 'available',
    image: null as File | null,
    remove_image: false as boolean,
});

const fileInput = ref<HTMLInputElement | null>(null);
const selectedPreview = ref<string | null>(null);

/** Preview of the freshly picked file, falling back to the stored photo. */
const previewUrl = computed<string | null>(() => {
    if (selectedPreview.value) {
        return selectedPreview.value;
    }

    return form.remove_image ? null : (props.vehicle?.image_url ?? null);
});

function onFileChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;

    if (selectedPreview.value) {
        URL.revokeObjectURL(selectedPreview.value);
    }

    form.image = file;
    form.remove_image = false;
    selectedPreview.value = file ? URL.createObjectURL(file) : null;
}

function clearImage() {
    if (selectedPreview.value) {
        URL.revokeObjectURL(selectedPreview.value);
        selectedPreview.value = null;
    }

    form.image = null;
    form.remove_image = true;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submit() {
    if (props.vehicle) {
        // Inertia needs a POST + method spoofing to send multipart data on update.
        form.transform((data) => ({ ...data, _method: 'put' })).post(
            update(props.vehicle.id).url,
            { forceFormData: true },
        );

        return;
    }

    form.post(store().url, { forceFormData: true });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2 md:col-span-2">
                    <Label for="name">Nom du véhicule</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="Ex : Starex 1"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="brand">Marque</Label>
                    <Input
                        id="brand"
                        v-model="form.brand"
                        placeholder="Ex : Hyundai"
                    />
                    <InputError :message="form.errors.brand" />
                </div>

                <div class="grid gap-2">
                    <Label for="model">Modèle</Label>
                    <Input
                        id="model"
                        v-model="form.model"
                        placeholder="Ex : Starex"
                    />
                    <InputError :message="form.errors.model" />
                </div>

                <div class="grid gap-2">
                    <Label for="registration_number">Immatriculation</Label>
                    <Input
                        id="registration_number"
                        v-model="form.registration_number"
                        placeholder="Ex : 1234 TBA"
                    />
                    <InputError :message="form.errors.registration_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="year">Année</Label>
                    <Input
                        id="year"
                        type="number"
                        min="1950"
                        :max="new Date().getFullYear() + 1"
                        :model-value="form.year"
                        @update:model-value="(v) => (form.year = Number(v))"
                    />
                    <InputError :message="form.errors.year" />
                </div>

                <div class="grid gap-2">
                    <Label for="seats">Nombre de places</Label>
                    <Input
                        id="seats"
                        type="number"
                        min="1"
                        :model-value="form.seats"
                        @update:model-value="(v) => (form.seats = Number(v))"
                    />
                    <InputError :message="form.errors.seats" />
                </div>

                <div class="grid gap-2">
                    <Label for="status">Statut</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="status" class="w-full">
                            <SelectValue placeholder="Choisir un statut" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="status in props.statuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.status" />
                </div>

                <div class="flex items-center gap-3 md:col-span-2">
                    <Checkbox
                        id="has_air_conditioning"
                        v-model="form.has_air_conditioning"
                    />
                    <Label for="has_air_conditioning" class="font-normal"
                        >Climatisation</Label
                    >
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="image">Photo du véhicule</Label>

                <div
                    class="flex aspect-video items-center justify-center overflow-hidden rounded-xl border border-dashed border-sidebar-border/70 bg-muted/30 dark:border-sidebar-border"
                >
                    <img
                        v-if="previewUrl"
                        :src="previewUrl"
                        alt="Aperçu du véhicule"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 p-4 text-center text-muted-foreground"
                    >
                        <ImageUp class="size-6" />
                        <span class="text-xs"
                            >Aucune photo — JPG, PNG ou WEBP, 4 Mo max.</span
                        >
                    </div>
                </div>

                <input
                    id="image"
                    ref="fileInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    class="block w-full text-sm text-muted-foreground file:mr-3 file:rounded-md file:border file:border-input file:bg-background file:px-3 file:py-1.5 file:text-sm file:text-foreground hover:file:bg-accent"
                    @change="onFileChange"
                />
                <InputError :message="form.errors.image" />

                <Button
                    v-if="previewUrl"
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="justify-self-start text-muted-foreground"
                    @click="clearImage"
                >
                    <Trash2 class="size-4" />
                    Retirer la photo
                </Button>

                <p v-if="form.progress" class="text-xs text-muted-foreground">
                    Envoi en cours… {{ form.progress.percentage }} %
                </p>
            </div>
        </div>

        <Button type="submit" :disabled="form.processing">{{
            props.submitLabel
        }}</Button>
    </form>
</template>
