<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import logoUrl from '../../images/logo-320.png';
import { dashboard, login, pictureSources } from '@/routes';
</script>

<template>
    <Head title="Välkommen" />

    <div
        class="flex min-h-svh flex-col items-center justify-center gap-8 bg-surface px-4 py-10 text-ink"
    >
        <img
            :src="logoUrl"
            alt="NPF-familjen"
            width="200"
            height="200"
            class="size-50 rounded-xl"
        />

        <div class="flex max-w-md flex-col items-center gap-3 text-center">
            <h1 class="text-display-l font-semibold">
                <span class="text-primary">NPF</span>-<span
                    class="text-orange-strong"
                    >familjen</span
                >
            </h1>
            <p class="text-ink-muted">
                Bildstöd, rutiner och lugn för hela familjen.
            </p>
        </div>

        <Link
            v-if="$page.props.auth.user"
            :href="dashboard()"
            class="inline-flex min-h-tap-adult items-center rounded-full bg-primary px-8 font-bold text-on-primary"
        >
            Gå till Idag
        </Link>
        <Link
            v-else
            :href="login()"
            class="inline-flex min-h-tap-adult items-center rounded-full bg-primary px-8 font-bold text-on-primary"
        >
            Logga in
        </Link>

        <Link
            :href="pictureSources()"
            class="text-caption text-ink-muted underline"
        >
            Bildkällor
        </Link>
    </div>
</template>
