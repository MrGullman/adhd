<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { RefreshCw, ShieldCheck, Unplug } from '@lucide/vue';
import IcaConnectionController from '@/actions/App/Http/Controllers/Lists/IcaConnectionController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/format';
import { index as listsIndex } from '@/routes/lists';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Listor', href: listsIndex() },
            { title: 'ICA', href: IcaConnectionController.show() },
        ],
    },
});

defineProps<{
    /** The server has what it needs to talk to ICA. */
    available: boolean;
    connection: {
        connectedBy: string | null;
        lists: Array<{ id: string; title: string }>;
        listId: string | null;
        listTitle: string | null;
        lastSyncedAt: string | null;
        lastError: string | null;
    } | null;
}>();
</script>

<!--
    Handla → ICA (test): familjens handlingslista skickas till en av ICA-
    listorna. ICA:s API är inofficiellt, så listan här fungerar alltid själv.
-->
<template>
    <Head title="ICA" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-col gap-2">
            <h1 class="text-heading font-semibold">Handlingslistan i ICA</h1>
            <p class="text-ink-muted">
                Handla och en av era listor i ICA-appen hålls lika: det ni
                lägger till, bockar av och tar bort på det ena stället händer
                också på det andra.
            </p>
            <p class="rounded-lg bg-surface-sunken p-3 text-caption">
                Det här är ett test. ICA har inget öppet API, så kopplingen
                använder samma väg som ICA:s egen app och kan sluta fungera om
                ICA ändrar något. Handlingslistan här fungerar alltid som
                vanligt.
            </p>
        </header>

        <p v-if="!available" class="text-ink-muted">
            ICA-kopplingen är inte påslagen på servern än.
        </p>

        <!-- Logga in en gång -->
        <section
            v-else-if="!connection"
            class="flex flex-col gap-4"
            aria-labelledby="logga-in"
        >
            <h2 id="logga-in" class="text-title">Logga in på ICA</h2>
            <p class="flex gap-2 text-caption text-ink-muted">
                <ShieldCheck class="size-5 shrink-0" aria-hidden="true" />
                Personnummer och lösenord skickas till ICA och glöms sedan. De
                sparas aldrig i NPF-familjen. Det som sparas är ICA:s nyckel för
                inloggningen, krypterad, så att ni inte behöver logga in igen.
            </p>
            <Form
                v-bind="IcaConnectionController.store.form()"
                class="flex flex-col gap-4"
                autocomplete="off"
                v-slot="{ errors, processing }"
            >
                <div class="grid max-w-sm gap-2">
                    <Label for="ica-personal-id">Personnummer</Label>
                    <Input
                        id="ica-personal-id"
                        name="personal_id"
                        inputmode="numeric"
                        placeholder="ÅÅMMDD-NNNN"
                        required
                    />
                    <InputError :message="errors.personal_id" />
                </div>
                <div class="grid max-w-sm gap-2">
                    <Label for="ica-password">Lösenord till ICA</Label>
                    <Input
                        id="ica-password"
                        name="ica_password"
                        type="password"
                        autocomplete="off"
                        required
                    />
                    <InputError :message="errors.ica_password" />
                </div>
                <div>
                    <Button type="submit" :disabled="processing">
                        {{ processing ? 'Loggar in…' : 'Koppla till ICA' }}
                    </Button>
                </div>
            </Form>
        </section>

        <!-- Kopplad -->
        <template v-else>
            <section class="flex flex-col gap-4" aria-labelledby="lista">
                <h2 id="lista" class="text-title">Vilken ICA-lista?</h2>
                <p
                    v-if="connection.connectedBy"
                    class="text-caption text-ink-muted"
                >
                    Kopplad av {{ connection.connectedBy }}.
                </p>
                <Form
                    v-if="connection.lists.length"
                    v-bind="IcaConnectionController.update.form()"
                    class="flex flex-col gap-3"
                    v-slot="{ errors, processing }"
                >
                    <fieldset class="flex flex-col gap-2">
                        <legend class="sr-only">ICA-lista</legend>
                        <label
                            v-for="list in connection.lists"
                            :key="list.id"
                            class="flex min-h-tap-adult items-center gap-3 rounded-lg border border-line bg-surface-raised px-3"
                        >
                            <input
                                type="radio"
                                name="list_id"
                                :value="list.id"
                                :checked="list.id === connection.listId"
                                class="size-5"
                            />
                            {{ list.title }}
                        </label>
                    </fieldset>
                    <InputError :message="errors.list_id" />
                    <div>
                        <Button type="submit" :disabled="processing">
                            Använd den här listan
                        </Button>
                    </div>
                    <p class="text-caption text-ink-muted">
                        Det som står på handlingslistan nu skickas dit.
                        Ändringar i ICA-appen kommer hit inom fem minuter.
                    </p>
                </Form>
                <p v-else class="text-ink-muted">
                    Hittade inga listor i ICA. Skapa en i ICA-appen och hämta
                    listorna igen.
                </p>
                <Link
                    :href="IcaConnectionController.lists()"
                    as="button"
                    preserve-scroll
                    class="flex min-h-tap-adult w-fit items-center gap-2 font-bold underline"
                >
                    <RefreshCw class="size-4" aria-hidden="true" />
                    Hämta listorna igen
                </Link>
            </section>

            <section class="flex flex-col gap-2" aria-labelledby="status">
                <h2 id="status" class="text-title">Status</h2>
                <p v-if="connection.lastError" class="font-bold">
                    {{ connection.lastError }} Handlingslistan här fungerar som
                    vanligt, och appen försöker igen.
                </p>
                <p v-else-if="connection.lastSyncedAt" class="text-ink-muted">
                    Senast skickat till ICA
                    {{ formatDateTime(connection.lastSyncedAt) }}.
                </p>
                <p v-else class="text-ink-muted">Inget skickat än.</p>
            </section>

            <section
                class="flex flex-col gap-2 border-t border-line pt-6"
                aria-labelledby="koppla-bort"
            >
                <h2 id="koppla-bort" class="sr-only">Koppla bort</h2>
                <Link
                    :href="IcaConnectionController.destroy()"
                    as="button"
                    method="delete"
                    class="flex min-h-tap-adult w-fit items-center gap-2 rounded-full border-2 border-line px-4 font-bold"
                >
                    <Unplug class="size-5" aria-hidden="true" />
                    Koppla bort ICA
                </Link>
                <p class="text-caption text-ink-muted">
                    ICA:s nyckel tas bort. Varorna som redan finns i ICA-listan
                    ligger kvar där.
                </p>
            </section>
        </template>
    </div>
</template>
