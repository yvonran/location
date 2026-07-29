<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import {
    destroy,
    index,
    store,
    update,
} from '@/routes/configuration/vehicle-types';
import type { VehicleType } from '@/types/reference';

const props = defineProps<{ vehicleTypes: VehicleType[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Configuration', href: index() },
            { title: 'Types de véhicule', href: index() },
        ],
    },
});

const createForm = useForm({ name: '' });
const editForm = useForm({ name: '', position: 0 });
const editing = ref<VehicleType | null>(null);

function add() {
    createForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function openEdit(type: VehicleType) {
    editing.value = type;
    editForm.clearErrors();
    editForm.name = type.name;
    editForm.position = type.position ?? 0;
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

function remove(type: VehicleType) {
    if (
        !confirm(
            `Supprimer le type « ${type.name} » ? Les modèles rattachés seront simplement déclassés.`,
        )
    ) {
        return;
    }

    router.delete(destroy(type.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Types de véhicule" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Types de véhicule"
            description="Bus, minibus, 4x4… Le type sert à filtrer les modèles au moment de créer un véhicule."
        />

        <form class="flex max-w-xl items-end gap-2" @submit.prevent="add">
            <div class="grid flex-1 gap-1">
                <Label for="new_type" class="text-xs text-muted-foreground">
                    Nouveau type
                </Label>
                <Input
                    id="new_type"
                    v-model="createForm.name"
                    placeholder="Ex : Pick-up"
                />
                <InputError :message="createForm.errors.name" />
            </div>
            <Button type="submit" :disabled="createForm.processing">
                <Plus class="size-4" />
                Ajouter
            </Button>
        </form>

        <div
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead
                    class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="p-3">Type</th>
                        <th class="p-3">Modèles rattachés</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="type in props.vehicleTypes"
                        :key="type.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="p-3 font-medium">{{ type.name }}</td>
                        <td class="p-3 text-muted-foreground">
                            {{ type.vehicle_models_count ?? 0 }}
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :title="`Modifier ${type.name}`"
                                    @click="openEdit(type)"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    :title="`Supprimer ${type.name}`"
                                    @click="remove(type)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="props.vehicleTypes.length === 0">
                        <td
                            colspan="3"
                            class="p-6 text-center text-muted-foreground"
                        >
                            Aucun type enregistré.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Dialog
            :open="editing !== null"
            @update:open="(open) => !open && (editing = null)"
        >
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Modifier le type</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="saveEdit">
                    <div class="grid gap-1">
                        <Label for="edit_type">Nom</Label>
                        <Input id="edit_type" v-model="editForm.name" />
                        <InputError :message="editForm.errors.name" />
                    </div>
                    <div class="grid gap-1">
                        <Label for="edit_position">Ordre d'affichage</Label>
                        <Input
                            id="edit_position"
                            type="number"
                            min="0"
                            :model-value="editForm.position"
                            @update:model-value="
                                (v) => (editForm.position = Number(v))
                            "
                        />
                        <InputError :message="editForm.errors.position" />
                    </div>
                    <Button type="submit" :disabled="editForm.processing">
                        Enregistrer
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
