<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { create, show } from '@/routes/quotes';

interface QuoteRow {
    id: number;
    number: string;
    quote_date: string;
    status: 'draft' | 'sent' | 'accepted' | 'rejected';
    total: string;
    customer: { id: number; name: string };
}

interface Paginated<T> {
    data: T[];
}

defineProps<{ quotes: Paginated<QuoteRow> }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Devis', href: create().url },
        ],
    },
});

function formatAr(value: string | number): string {
    return `${Number(value).toLocaleString('fr-FR')} Ar`;
}

const statusLabels: Record<QuoteRow['status'], string> = {
    draft: 'Brouillon',
    sent: 'Envoyé',
    accepted: 'Accepté',
    rejected: 'Refusé',
};
</script>

<template>
    <Head title="Devis" />

    <div class="flex flex-col space-y-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                title="Devis"
                description="Historique des devis générés."
            />
            <Button as-child>
                <Link :href="create().url">Nouveau devis</Link>
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
                        <th class="p-3">Numéro</th>
                        <th class="p-3">Client</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Statut</th>
                        <th class="p-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="quote in quotes.data"
                        :key="quote.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="p-3">
                            <Link
                                :href="show(quote.id).url"
                                class="underline"
                                >{{ quote.number }}</Link
                            >
                        </td>
                        <td class="p-3">{{ quote.customer.name }}</td>
                        <td class="p-3">{{ quote.quote_date }}</td>
                        <td class="p-3">
                            <Badge>{{ statusLabels[quote.status] }}</Badge>
                        </td>
                        <td class="p-3 text-right">
                            {{ formatAr(quote.total) }}
                        </td>
                    </tr>
                    <tr v-if="quotes.data.length === 0">
                        <td
                            colspan="5"
                            class="p-6 text-center text-muted-foreground"
                        >
                            Aucun devis pour l'instant.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
