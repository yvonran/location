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
import { destroy, index, store, update } from '@/routes/configuration/brands';
import type { Brand } from '@/types/reference';

const props = defineProps<{ brands: Brand[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Configuration', href: index() },
            { title: 'Marques', href: index() },
        ],
    },
});

const createForm = useForm({ name: '' });
const editForm = useForm({ name: '' });
const editing = ref<Brand | null>(null);

function add() {
    createForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function openEdit(brand: Brand) {
    editing.value = brand;
    editForm.clearErrors();
    editForm.name = brand.name;
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

function remove(brand: Brand) {
    if (!confirm(`Supprimer la marque « ${brand.name} » ?`)) {
        return;
    }

    router.delete(destroy(brand.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Marques" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Marques"
            description="Référentiel des marques. Un modèle appartient toujours à une marque."
        />

        <form class="flex max-w-xl items-end gap-2" @submit.prevent="add">
            <div class="grid flex-1 gap-1">
                <Label for="new_brand" class="text-xs text-muted-foreground">
                    Nouvelle marque
                </Label>
                <Input
                    id="new_brand"
                    v-model="createForm.name"
                    placeholder="Ex : Hyundai"
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
                        <th class="p-3">Marque</th>
                        <th class="p-3">Modèles</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="brand in props.brands"
                        :key="brand.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="p-3 font-medium">{{ brand.name }}</td>
                        <td class="p-3 text-muted-foreground">
                            {{ brand.vehicle_models_count ?? 0 }}
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :title="`Renommer ${brand.name}`"
                                    @click="openEdit(brand)"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    :title="`Supprimer ${brand.name}`"
                                    @click="remove(brand)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="props.brands.length === 0">
                        <td
                            colspan="3"
                            class="p-6 text-center text-muted-foreground"
                        >
                            Aucune marque enregistrée.
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
                    <DialogTitle>Renommer la marque</DialogTitle>
                </DialogHeader>
                <form class="space-y-4" @submit.prevent="saveEdit">
                    <div class="grid gap-1">
                        <Label for="edit_brand">Nom</Label>
                        <Input id="edit_brand" v-model="editForm.name" />
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
