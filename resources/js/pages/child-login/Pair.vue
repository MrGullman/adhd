<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import logoUrl from '../../../images/logo-320.png';
import ChildPairingController from '@/actions/App/Http/Controllers/Family/ChildPairingController';

defineProps<{
    token: string;
    childName: string | null;
}>();
</script>

<template>
    <Head title="Koppla enhet" />

    <main
        class="flex min-h-svh flex-col items-center justify-center gap-8 bg-surface px-4 py-8 text-center text-ink"
    >
        <img :src="logoUrl" alt="NPF-familjen" class="size-24" />

        <template v-if="childName">
            <h1 class="text-heading font-semibold">
                Koppla den här enheten till {{ childName }}?
            </h1>
            <p class="max-w-sm text-ink-muted">
                Sedan loggar {{ childName }} in här med sin kod.
            </p>
            <Form
                v-bind="ChildPairingController.update.form(token)"
                v-slot="{ processing }"
            >
                <button
                    type="submit"
                    class="h-tap-child rounded-full bg-primary px-10 font-display text-2xl font-semibold text-on-primary"
                    :disabled="processing"
                >
                    Ja, koppla
                </button>
            </Form>
        </template>

        <template v-else>
            <h1 class="text-heading font-semibold">
                Länken gäller inte längre
            </h1>
            <p class="max-w-sm text-ink-muted">
                Den har redan använts eller gått ut. Be en vuxen visa en ny kod.
            </p>
        </template>
    </main>
</template>
