<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard } from '@/routes';
import {
    destroy,
    index,
    store,
    update,
} from '@/routes/configuration/vehicle-models';
import type { Brand, VehicleModel, VehicleType } from '@/types/reference';

interface Paginated<T> {
    data: T[];
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    models: Paginated<VehicleModel>;
    brands: Brand[];
    vehicleTypes: VehicleType[];
    filters: {
        brand_id: number | null;
        vehicle_type_id: number | string | null;
        search: string | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Configuration', href: index() },
            { title: 'Modèles', href: index() },
        ],
    },
});

const filterBrand = ref<number | 'all'>(props.filters.brand_id ?? 'all');
const filterType = ref<number | 'all' | 'none'>(
    (props.filters.vehicle_type_id as number | 'none' | null) ?? 'all',
);
const search = ref(props.filters.search ?? '');

function applyFilters() {
    router.get(
        index().url,
        {
            brand_id:
                filterBrand.value === 'all' ? undefined : filterBrand.value,
            vehicle_type_id:
                filterType.value === 'all' ? undefined : filterType.value,
            search: search.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

watch([filterBrand, filterType], applyFilters);

const createForm = useForm({
    brand_id: null as number | null,
    vehicle_type_id: null as number | null,
    name: '',
});

const editForm = useForm({
    brand_id: null as number | null,
    vehicle_type_id: null as number | null,
    name: '',
});

const editing = ref<VehicleModel | null>(null);

function add() {
    createForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => createForm.reset('name'),
    });
}

function openEdit(model: VehicleModel) {
    editing.value = model;
    editForm.clearErrors();
    editForm.brand_id = model.brand_id;
    editForm.vehicle_type_id = model.vehicle_type_id;
    editForm.name = model.name;
}

function saveEdit() {
    if (!editing.value) {
        return;
    }

    editForm.put(update(editing.value.id).url, {
        preserveScroll: true,
        onSuccess: () => (editing.value = null),
    });
}

function remove(model: VehicleModel) {
    if (!confirm(`Supprimer le modèle « ${model.name} » ?`)) {
        return;
    }

    router.delete(destroy(model.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Modèles" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Modèles"
            description="Chaque modèle appartient à une marque et peut être classé dans un type."
        />

        <form
            class="grid gap-3 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-[1fr_1fr_1fr_auto] dark:border-sidebar-border"
            @submit.prevent="add"
        >
            <div class="grid gap-1">
                <Label for="new_brand" class="text-xs text-muted-foreground">
                    Marque
                </Label>
                <Select v-model="createForm.brand_id">
                    <SelectTrigger id="new_brand" class="w-full">
                        <SelectValue placeholder="Choisir" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="brand in props.brands"
                            :key="brand.id"
                            :value="brand.id"
                        >
                            {{ brand.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="createForm.errors.brand_id" />
            </div>

            <div class="grid gap-1">
                <Label for="new_type" class="text-xs text-muted-foreground">
                    Type (optionnel)
                </Label>
                <Select v-model="createForm.vehicle_type_id">
                    <SelectTrigger id="new_type" class="w-full">
                        <SelectValue placeholder="Non classé" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="type in props.vehicleTypes"
                            :key="type.id"
                            :value="type.id"
                        >
                            {{ type.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="createForm.errors.vehicle_type_id" />
            </div>

            <div class="grid gap-1">
                <Label for="new_model" class="text-xs text-muted-foreground">
                    Nom du modèle
                </Label>
                <Input
                    id="new_model"
                    v-model="createForm.name"
                    placeholder="Ex : County"
                />
                <InputError :message="createForm.errors.name" />
            </div>

            <Button
                type="submit"
                class="mt-5 self-start"
                :disabled="createForm.processing"
            >
                <Plus class="size-4" />
                Ajouter
            </Button>
        </form>

        <div class="grid gap-3 sm:grid-cols-[1fr_1fr_2fr]">
            <Select v-model="filterBrand">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Toutes les marques" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="'all'">Toutes les marques</SelectItem>
                    <SelectItem
                        v-for="brand in props.brands"
                        :key="brand.id"
                        :value="brand.id"
                    >
                        {{ brand.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="filterType">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="Tous les types" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="'all'">Tous les types</SelectItem>
                    <SelectItem :value="'none'">Non classés</SelectItem>
                    <SelectItem
                        v-for="type in props.vehicleTypes"
                        :key="type.id"
                        :value="type.id"
                    >
                        {{ type.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <form class="flex gap-2" @submit.prevent="applyFilters">
                <Input v-model="search" placeholder="Rechercher un modèle…" />
                <Button type="submit" variant="outline">Rechercher</Button>
            </form>
        </div>

        <div
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead
                    class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="p-3">Marque</th>
                        <th class="p-3">Modèle</th>
                        <th class="p-3">Type</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="model in props.models.data"
                        :key="model.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="p-3 text-muted-foreground">
                            {{ model.brand?.name }}
                        </td>
                        <td class="p-3 font-medium">{{ model.name }}</td>
                        <td class="p-3">
                            <Badge
                                v-if="model.vehicle_type"
                                variant="secondary"
                            >
                                {{ model.vehicle_type.name }}
                            </Badge>
                            <span v-else class="text-muted-foreground">
                                Non classé
                            </span>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :title="`Modifier ${model.name}`"
                                    @click="openEdit(model)"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    :title="`Supprimer ${model.name}`"
                                    @click="remove(model)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="props.models.data.length === 0">
                        <td
                            colspan="4"
                            class="p-6 text-center text-muted-foreground"
                        >
                            Aucun modèle ne correspond.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="props.models.total > 0"
            class="flex items-center justify-between text-sm text-muted-foreground"
        >
            <span>
                {{ props.models.from }}–{{ props.models.to }} sur
                {{ props.models.total }} modèles
            </span>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!props.models.prev_page_url"
                    as-child
                >
                    <Link
                        v-if="props.models.prev_page_url"
                        :href="props.models.prev_page_url"
                    >
                        Précédent
                    </Link>
                    <span v-else>Précédent</span>
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!props.models.next_page_url"
                    as-child
                >
                    <Link
                        v-if="props.models.next_page_url"
                        :href="props.models.next_page_url"
                    >
                        Suivant
                    </Link>
                    <span v-else>Suivant</span>
                </Button>
            </div>
        </div>

        <Dialog
            :open="editing !== null"
            @update:open="(open) => !open && (editing = null)"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Modifier le modèle</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="saveEdit">
                    <div class="grid gap-1">
                        <Label for="edit_brand">Marque</Label>
                        <Select v-model="editForm.brand_id">
                            <SelectTrigger id="edit_brand" class="w-full">
                                <SelectValue placeholder="Choisir" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="brand in props.brands"
                                    :key="brand.id"
                                    :value="brand.id"
                                >
                                    {{ brand.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="editForm.errors.brand_id" />
                    </div>

                    <div class="grid gap-1">
                        <Label for="edit_type">Type</Label>
                        <Select v-model="editForm.vehicle_type_id">
                            <SelectTrigger id="edit_type" class="w-full">
                                <SelectValue placeholder="Non classé" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in props.vehicleTypes"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError
                            :message="editForm.errors.vehicle_type_id"
                        />
                    </div>

                    <div class="grid gap-1">
                        <Label for="edit_model">Nom</Label>
                        <Input id="edit_model" v-model="editForm.name" />
                        <InputError :message="editForm.errors.name" />
                    </div>

                    <Button type="submit" :disabled="editForm.processing">
                        Enregistrer
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
