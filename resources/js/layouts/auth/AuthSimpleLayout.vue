<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import logoUrl from '../../../images/logo-wide.png';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="home()" class="flex flex-col items-center">
                        <img
                            :src="logoUrl"
                            alt="NPF-familjen"
                            width="600"
                            height="372"
                            class="h-auto w-72"
                        />
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-heading font-semibold">{{ title }}</h1>
                        <p class="text-center text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
