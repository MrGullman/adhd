<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    CalendarRange,
    ClipboardList,
    LayoutGrid,
    ListOrdered,
    ListTodo,
    Sunrise,
    Users,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
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
import { dashboard, tomorrow, week } from '@/routes';
import { index as activitiesIndex } from '@/routes/activities';
import { show as familyShow } from '@/routes/family';
import { index as listsIndex } from '@/routes/lists';
import { index as routinesIndex } from '@/routes/routines';
import { index as tasksIndex } from '@/routes/tasks';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Idag',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Imorgon',
        href: tomorrow(),
        icon: Sunrise,
    },
    {
        title: 'Veckan',
        href: week(),
        icon: CalendarRange,
    },
    {
        title: 'Uppgifter',
        href: tasksIndex(),
        icon: ListTodo,
    },
    {
        title: 'Rutiner',
        href: routinesIndex(),
        icon: ListOrdered,
    },
    {
        title: 'Aktiviteter',
        href: activitiesIndex(),
        icon: CalendarDays,
    },
    {
        title: 'Listor',
        href: listsIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Familjen',
        href: familyShow(),
        icon: Users,
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
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
