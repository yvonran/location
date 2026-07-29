<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Car, Pencil, Snowflake, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { dashboard } from '@/routes';
import { create, destroy, edit } from '@/routes/vehicles';
import type { Vehicle, VehicleStatus } from '@/types/vehicle';

interface Paginated<T> {
    data: T[];
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}

defineProps<{ vehicles: Paginated<Vehicle> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Véhicules', href: create() },
        ],
    },
});

const statusLabels: Record<VehicleStatus, string> = {
    available: 'Disponible',
    maintenance: 'En maintenance',
    out_of_service: 'Hors service',
};

const statusVariants: Record<
    VehicleStatus,
    'default' | 'secondary' | 'destructive'
> = {
    available: 'default',
    maintenance: 'secondary',
    out_of_service: 'destructive',
};

const preview = ref<Vehicle | null>(null);

function remove(vehicle: Vehicle) {
    if (
        !confirm(
            `Supprimer « ${vehicle.name} » ? Cette action le retire de la liste.`,
        )
    ) {
        return;
    }

    router.delete(destroy(vehicle.id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Véhicules" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Véhicules"
                description="Le parc automobile de l'agence."
            />
            <Button as-child>
                <Link :href="create().url">Ajouter un véhicule</Link>
            </Button>
        </div>

        <div
            class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead
                    class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="p-3">Photo</th>
                        <th class="p-3">Véhicule</th>
                        <th class="p-3">Immatriculation</th>
                        <th class="p-3">Places</th>
                        <th class="p-3">Année</th>
                        <th class="p-3">Clim.</th>
                        <th class="p-3">Statut</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="vehicle in vehicles.data"
                        :key="vehicle.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="p-3">
                            <button
                                v-if="vehicle.image_url"
                                type="button"
                                class="block overflow-hidden rounded-md ring-offset-background transition hover:opacity-80 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                :title="`Agrandir la photo de ${vehicle.name}`"
                                @click="preview = vehicle"
                            >
                                <img
                                    :src="vehicle.image_url"
                                    :alt="vehicle.name"
                                    class="h-12 w-20 object-cover"
                                />
                            </button>
                            <div
                                v-else
                                class="flex h-12 w-20 items-center justify-center rounded-md bg-muted text-muted-foreground"
                            >
                                <Car class="size-5" />
                            </div>
                        </td>
                        <td class="p-3">
                            <div class="font-medium">{{ vehicle.name }}</div>
                            <div class="text-xs text-muted-foreground">
                                {{ vehicle.brand }} {{ vehicle.model }}
                            </div>
                        </td>
                        <td class="p-3">{{ vehicle.registration_number }}</td>
                        <td class="p-3">{{ vehicle.seats }}</td>
                        <td class="p-3">{{ vehicle.year }}</td>
                        <td class="p-3">
                            <Snowflake
                                v-if="vehicle.has_air_conditioning"
                                class="size-4 text-muted-foreground"
                            />
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="p-3">
                            <Badge :variant="statusVariants[vehicle.status]">
                                {{ statusLabels[vehicle.status] }}
                            </Badge>
                        </td>
                        <td class="p-3">
                            <div class="flex justify-end gap-1">
                                <Button variant="ghost" size="sm" as-child>
                                    <Link
                                        :href="edit(vehicle.id).url"
                                        :title="`Modifier ${vehicle.name}`"
                                    >
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive hover:text-destructive"
                                    :title="`Supprimer ${vehicle.name}`"
                                    @click="remove(vehicle)"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="vehicles.data.length === 0">
                        <td
                            colspan="8"
                            class="p-6 text-center text-muted-foreground"
                        >
                            Aucun véhicule enregistré pour l'instant.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="vehicles.total > 0"
            class="flex items-center justify-between text-sm text-muted-foreground"
        >
            <span>
                {{ vehicles.from }}–{{ vehicles.to }} sur
                {{ vehicles.total }} véhicule{{ vehicles.total > 1 ? 's' : '' }}
            </span>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!vehicles.prev_page_url"
                    as-child
                >
                    <Link
                        v-if="vehicles.prev_page_url"
                        :href="vehicles.prev_page_url"
                        >Précédent</Link
                    >
                    <span v-else>Précédent</span>
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!vehicles.next_page_url"
                    as-child
                >
                    <Link
                        v-if="vehicles.next_page_url"
                        :href="vehicles.next_page_url"
                        >Suivant</Link
                    >
                    <span v-else>Suivant</span>
                </Button>
            </div>
        </div>

        <Dialog
            :open="preview !== null"
            @update:open="(open) => !open && (preview = null)"
        >
            <DialogContent v-if="preview" class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{{ preview.name }}</DialogTitle>
                    <DialogDescription>
                        {{ preview.brand }} {{ preview.model }} —
                        {{ preview.registration_number }}
                    </DialogDescription>
                </DialogHeader>
                <img
                    v-if="preview.image_url"
                    :src="preview.image_url"
                    :alt="preview.name"
                    class="w-full rounded-lg object-contain"
                />
            </DialogContent>
        </Dialog>
    </div>
</template>
