<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    CalendarRange,
    ChefHat,
    ClipboardList,
    NotebookPen,
    Star,
    LayoutGrid,
    ListOrdered,
    ListTodo,
    MessagesSquare,
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
    useSidebar,
} from '@/components/ui/sidebar';
import { dashboard, tomorrow, week } from '@/routes';
import { index as activitiesIndex } from '@/routes/activities';
import { show as familyShow } from '@/routes/family';
import { index as communityIndex } from '@/routes/community';
import { index as listsIndex } from '@/routes/lists';
import { index as mealPlanIndex } from '@/routes/meal-plan';
import { index as routinesIndex } from '@/routes/routines';
import { index as tasksIndex } from '@/routes/tasks';
import { computed, onUnmounted } from 'vue';
import { index as journalIndex } from '@/routes/journal';
import { index as rewardsIndex } from '@/routes/rewards';
import type { NavItem } from '@/types';

const page = usePage();

// On the phone the menu closes when a page is chosen.
const { setOpenMobile } = useSidebar();
onUnmounted(router.on('navigate', () => setOpenMobile(false)));

const allItems: NavItem[] = [
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
        title: 'Mat',
        href: mealPlanIndex(),
        icon: ChefHat,
    },
    {
        title: 'Listor',
        href: listsIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Dagbok',
        href: journalIndex(),
        icon: NotebookPen,
    },
    {
        title: 'Belöningar',
        href: rewardsIndex(),
        icon: Star,
    },
    {
        title: 'Community',
        href: communityIndex(),
        icon: MessagesSquare,
    },
    {
        title: 'Familjen',
        href: familyShow(),
        icon: Users,
    },
];

// Dagboken, Belöningar and Community are for parents only.
const parentOnly = ['Dagbok', 'Belöningar', 'Community'];
const mainNavItems = computed(() =>
    allItems.filter(
        (item) =>
            !parentOnly.includes(item.title) ||
            (page.props.family?.canManage ?? false),
    ),
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" class="h-auto py-1" as-child>
                        <Link
                            :href="dashboard()"
                            aria-label="NPF-familjen, Idag"
                        >
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
