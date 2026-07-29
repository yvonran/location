<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Car,
    FileText,
    FolderGit2,
    LayoutGrid,
    Layers,
    Settings2,
    Tags,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as brandsIndex } from '@/routes/configuration/brands';
import { index as vehicleModelsIndex } from '@/routes/configuration/vehicle-models';
import { index as vehicleTypesIndex } from '@/routes/configuration/vehicle-types';
import { index as quotesIndex } from '@/routes/quotes';
import { index as vehiclesIndex } from '@/routes/vehicles';
import type { NavItem } from '@/types';

const page = usePage();

/** Le référentiel n'est ouvert qu'au superadmin ; les routes le vérifient aussi. */
const isSuperAdmin = computed(() => page.props.auth?.isSuperAdmin === true);

const configurationNavItems: NavItem[] = [
    {
        title: 'Marques',
        href: brandsIndex(),
        icon: Tags,
    },
    {
        title: 'Types de véhicule',
        href: vehicleTypesIndex(),
        icon: Layers,
    },
    {
        title: 'Modèles',
        href: vehicleModelsIndex(),
        icon: Settings2,
    },
];

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Véhicules',
        href: vehiclesIndex(),
        icon: Car,
    },
    {
        title: 'Devis',
        href: quotesIndex(),
        icon: FileText,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain
                v-if="isSuperAdmin"
                :items="configurationNavItems"
                label="Configuration"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
