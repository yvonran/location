<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { edit, update } from '@/routes/configuration/simulation-settings';

interface SimulationSettingProps {
    fuel_price_per_liter: string;
    client_meal_price: string;
}

const props = defineProps<{ setting: SimulationSettingProps }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Configuration', href: edit() },
            { title: 'Réglages simulation', href: edit() },
        ],
    },
});

const form = useForm({
    fuel_price_per_liter: Number(props.setting.fuel_price_per_liter),
    client_meal_price: Number(props.setting.client_meal_price),
});

function submit() {
    form.put(update().url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Réglages simulation" />

    <div class="flex flex-col space-y-6 p-4">
        <Heading
            title="Réglages simulation"
            description="Montants par défaut utilisés par le calcul automatique du carburant et du repas client dans les simulations."
        />

        <form
            class="grid max-w-sm gap-4"
            @submit.prevent="submit"
        >
            <div class="grid gap-1">
                <Label for="fuel_price_per_liter">
                    Prix du litre de carburant (Ar)
                </Label>
                <Input
                    id="fuel_price_per_liter"
                    type="number"
                    min="0"
                    step="0.01"
                    :model-value="form.fuel_price_per_liter"
                    @update:model-value="
                        (v) => (form.fuel_price_per_liter = Number(v))
                    "
                />
                <InputError :message="form.errors.fuel_price_per_liter" />
            </div>

            <div class="grid gap-1">
                <Label for="client_meal_price">
                    Prix du repas client (Ar)
                </Label>
                <Input
                    id="client_meal_price"
                    type="number"
                    min="0"
                    step="0.01"
                    :model-value="form.client_meal_price"
                    @update:model-value="
                        (v) => (form.client_meal_price = Number(v))
                    "
                />
                <InputError :message="form.errors.client_meal_price" />
            </div>

            <Button type="submit" class="justify-self-start" :disabled="form.processing">
                Enregistrer
            </Button>
        </form>
    </div>
</template>
