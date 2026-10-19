<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import logoUrl from '../../../images/logo-wide.png';
import { dashboard, home, login, privacy } from '@/routes';
import { home as childHome } from '@/routes/child';
import { index as infoIndex, situations } from '@/routes/info';

// Logged in: back to the app. Otherwise: log in. The pages are the same for all.
const page = usePage();
const appLink = computed(() => {
    if (!page.props.auth?.user) {
        return { href: login(), label: 'Logga in' };
    }

    return page.props.family?.role === 'child'
        ? { href: childHome(), label: 'Till Min dag' }
        : { href: dashboard(), label: 'Till appen' };
});
</script>

<!--
    Om NPF: öppna sidor om NPF och vardagen, utan inloggning, så att de kan
    delas med släkt och skola. Lugn läsbar layout, mobil först.
-->
<template>
    <div class="flex min-h-svh flex-col bg-surface text-ink">
        <header class="border-b border-line bg-surface-raised">
            <div
                class="mx-auto flex w-full max-w-4xl flex-wrap items-center justify-between gap-3 px-4 py-3"
            >
                <Link :href="home()" class="flex items-center">
                    <img
                        :src="logoUrl"
                        alt="NPF-familjen"
                        class="h-12 w-auto"
                    />
                </Link>
                <nav
                    class="flex flex-wrap items-center gap-2"
                    aria-label="Om NPF"
                >
                    <Link
                        :href="infoIndex()"
                        class="flex min-h-tap-adult items-center px-3 font-bold"
                    >
                        Om NPF
                    </Link>
                    <Link
                        :href="situations()"
                        class="flex min-h-tap-adult items-center px-3 font-bold"
                    >
                        Känner du igen dig?
                    </Link>
                    <Link
                        :href="appLink.href"
                        class="flex min-h-tap-adult items-center rounded-full border-2 border-primary px-4 font-bold"
                    >
                        {{ appLink.label }}
                    </Link>
                </nav>
            </div>
        </header>

        <main
            class="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-10 px-4 py-8 md:py-12"
        >
            <slot />
        </main>

        <footer class="border-t border-line bg-surface-raised">
            <div
                class="mx-auto flex w-full max-w-4xl flex-col gap-3 px-4 py-6 text-caption text-ink-muted"
            >
                <p>
                    Informationen i NPF Familjen är generell och ersätter inte
                    individuell rådgivning, utredning eller stöd från vården,
                    elevhälsan eller andra professionella.
                </p>
                <p>
                    Mer om NPF finns hos
                    <a
                        href="https://www.1177.se/sjukdomar--besvar/hjarna-och-nerver/neuropsykiatriska-funktionsnedsattningar/"
                        class="underline"
                        rel="noopener"
                        target="_blank"
                        >1177</a
                    >. Behöver du prata med någon nu, ring 1177 eller i
                    nödsituation 112.
                </p>
                <Link :href="privacy()" class="w-fit underline"
                    >Integritet</Link
                >
            </div>
        </footer>
    </div>
</template>
