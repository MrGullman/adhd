<script setup lang="ts">
import { Head, Link, router, useForm, usePoll } from '@inertiajs/vue3';
import { Download } from '@lucide/vue';
import { computed, watch } from 'vue';
import DataController from '@/actions/App/Http/Controllers/Family/DataController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import { privacy } from '@/routes';
import { show as familyShow } from '@/routes/family';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Familjen', href: familyShow() },
            { title: 'Familjens data', href: DataController.index() },
        ],
    },
});

const props = defineProps<{
    familyName: string;
    exports: Array<{
        id: number;
        status: 'pending' | 'ready' | 'failed';
        size: number | null;
        createdAt: string | null;
        expiresAt: string | null;
    }>;
}>();

// Follow an export that is being made.
const pending = computed(() =>
    props.exports.some((item) => item.status === 'pending'),
);
const { start, stop } = usePoll(
    4000,
    { only: ['exports'] },
    { autoStart: false },
);
watch(pending, (isPending) => (isPending ? start() : stop()), {
    immediate: true,
});

function megabytes(size: number | null): string {
    return size === null
        ? ''
        : `${(size / 1024 / 1024).toLocaleString('sv-SE', { maximumFractionDigits: 1 })} MB`;
}

const removal = useForm({ name: '', password: '' });

function removeFamily(): void {
    removal.delete(DataController.destroy.url(), {
        onFinish: () => removal.reset('password'),
    });
}
</script>

<template>
    <Head title="Familjens data" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-10 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Familjens data</h1>
            <p class="text-ink-muted">
                Ni äger er data. Läs mer i
                <Link :href="privacy()" class="underline"
                    >integritetspolicyn</Link
                >.
            </p>
        </header>

        <section class="flex flex-col gap-3" aria-labelledby="ladda-ner">
            <h2 id="ladda-ner" class="text-title">Ladda ner allt</h2>
            <p>
                En ZIP-fil med all familjens data och era egna bilder. Lösenord,
                barnens koder och kalenderlänkar är inte med. Filen finns kvar i
                7 dagar.
            </p>
            <div>
                <Button
                    :disabled="pending"
                    @click="
                        router.post(
                            DataController.export.url(),
                            {},
                            { preserveScroll: true },
                        )
                    "
                >
                    {{ pending ? 'Exporten görs…' : 'Skapa export' }}
                </Button>
            </div>
            <ul v-if="exports.length" class="flex flex-col gap-2">
                <li
                    v-for="item in exports"
                    :key="item.id"
                    class="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-surface-raised p-3"
                >
                    <div class="min-w-0 flex-1">
                        <p class="font-bold">
                            Export {{ formatDateTime(item.createdAt) }}
                        </p>
                        <p class="text-caption text-ink-muted">
                            <template v-if="item.status === 'ready'">
                                {{ megabytes(item.size) }} · finns till
                                {{ formatDateTime(item.expiresAt) }}
                            </template>
                            <template v-else-if="item.status === 'pending'">
                                Görs i ordning…
                            </template>
                            <template v-else>
                                Gick inte att skapa. Försök igen.
                            </template>
                        </p>
                    </div>
                    <a
                        v-if="item.status === 'ready'"
                        :href="DataController.download.url(item.id)"
                        class="flex min-h-tap-adult items-center gap-2 rounded-md px-3 font-bold hover:bg-surface-sunken"
                    >
                        <Download class="size-5" aria-hidden="true" />
                        Ladda ner
                    </a>
                </li>
            </ul>
        </section>

        <section
            class="flex flex-col gap-3 border-t border-line pt-8"
            aria-labelledby="radera"
        >
            <h2 id="radera" class="text-title">Ta bort hela familjen</h2>
            <p>
                All familjens data och alla filer tas bort. Barnens konton tas
                bort. Vuxna som inte är med i någon annan familj tas också bort,
                med sina inlägg i Community. Det går inte att ångra.
            </p>
            <p class="text-ink-muted">Ladda gärna ner allt först.</p>
            <form
                class="flex flex-col gap-3 rounded-lg border border-line p-4"
                @submit.prevent="removeFamily"
            >
                <div class="grid gap-2">
                    <Label for="family-name">
                        Skriv familjens namn: {{ familyName }}
                    </Label>
                    <Input
                        id="family-name"
                        v-model="removal.name"
                        autocomplete="off"
                        required
                    />
                    <InputError :message="removal.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="family-password">Ditt lösenord</Label>
                    <Input
                        id="family-password"
                        v-model="removal.password"
                        type="password"
                        autocomplete="current-password"
                        required
                    />
                    <InputError :message="removal.errors.password" />
                </div>
                <div>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="
                            removal.processing || removal.name !== familyName
                        "
                    >
                        Ta bort familjen
                    </Button>
                </div>
            </form>
        </section>
    </div>
</template>
