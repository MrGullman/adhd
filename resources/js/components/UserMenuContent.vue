<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Check, LogOut, Settings } from '@lucide/vue';
import { computed } from 'vue';
import ActiveFamilyController from '@/actions/App/Http/Controllers/Family/ActiveFamilyController';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();

const page = usePage();
const families = computed(() => page.props.families);
const currentFamilyId = computed(() => page.props.family?.id);
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <template v-if="families.length > 1">
        <DropdownMenuSeparator />
        <DropdownMenuLabel class="text-xs text-muted-foreground">
            Byt familj
        </DropdownMenuLabel>
        <DropdownMenuItem
            v-for="family in families"
            :key="family.id"
            :as-child="true"
        >
            <Link
                class="flex w-full cursor-pointer items-center"
                :href="ActiveFamilyController.update.url(family.id)"
                method="post"
                as="button"
            >
                <Check
                    class="mr-2 h-4 w-4"
                    :class="family.id !== currentFamilyId && 'invisible'"
                />
                {{ family.name }}
            </Link>
        </DropdownMenuItem>
    </template>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Inställningar
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Logga ut
        </Link>
    </DropdownMenuItem>
</template>
