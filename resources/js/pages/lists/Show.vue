<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CompletedListItemsController from '@/actions/App/Http/Controllers/Lists/CompletedListItemsController';
import ListController from '@/actions/App/Http/Controllers/Lists/ListController';
import ListItemCompletionController from '@/actions/App/Http/Controllers/Lists/ListItemCompletionController';
import ListItemController from '@/actions/App/Http/Controllers/Lists/ListItemController';
import CompletionToggle from '@/components/CompletionToggle.vue';
import DoneChip from '@/components/DoneChip.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as listsIndex } from '@/routes/lists';

const props = defineProps<{
    list: { id: number; name: string; isShopping: boolean };
    items: Array<{
        id: number;
        name: string;
        addedBy: string | null;
        completed: boolean;
    }>;
    can: { addItems: boolean; update: boolean; delete: boolean };
}>();

defineOptions({
    layout: (props: { list: { id: number; name: string } }) => ({
        breadcrumbs: [
            { title: 'Listor', href: listsIndex() },
            {
                title: props.list.name,
                href: ListController.show(props.list.id),
            },
        ],
    }),
});

const open = computed(() => props.items.filter((item) => !item.completed));
const done = computed(() => props.items.filter((item) => item.completed));
</script>

<template>
    <Head :title="list.name" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">{{ list.name }}</h1>
            <p v-if="list.isShopping" class="text-ink-muted">
                Barnen kan också lägga till och bocka av här.
            </p>
        </header>

        <Form
            v-if="can.addItems"
            v-bind="ListItemController.store.form(list.id)"
            :options="{ preserveScroll: true }"
            reset-on-success
            class="flex flex-wrap items-end gap-3"
            v-slot="{ errors, processing }"
        >
            <div class="grid min-w-60 flex-1 gap-2">
                <Label for="item-name" class="sr-only">Vad behövs?</Label>
                <Input
                    id="item-name"
                    name="name"
                    required
                    maxlength="100"
                    autocomplete="off"
                    placeholder="Vad behövs?"
                />
                <InputError :message="errors.name" />
            </div>
            <Button type="submit" :disabled="processing">Lägg till</Button>
        </Form>

        <section class="flex flex-col gap-2" aria-label="Kvar att göra">
            <p v-if="!open.length" class="text-ink-muted">
                Inget kvar på listan.
            </p>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="item in open"
                    :key="item.id"
                    class="flex items-center gap-2 rounded-lg border border-line bg-surface-raised py-1 pr-2 pl-1"
                >
                    <CompletionToggle
                        v-if="can.addItems"
                        :completed="false"
                        :href="
                            ListItemCompletionController.store([
                                list.id,
                                item.id,
                            ])
                        "
                        :label="item.name"
                    />
                    <div
                        class="min-w-0 flex-1 py-2"
                        :class="!can.addItems && 'pl-3'"
                    >
                        <p class="font-bold break-words">{{ item.name }}</p>
                        <p
                            v-if="item.addedBy"
                            class="text-caption text-ink-muted"
                        >
                            {{ item.addedBy }}
                        </p>
                    </div>
                    <Link
                        v-if="can.update"
                        :href="ListItemController.destroy([list.id, item.id])"
                        as="button"
                        preserve-scroll
                        class="min-h-tap-adult rounded-md px-3 text-caption font-bold text-danger hover:bg-surface-sunken"
                    >
                        Ta bort
                    </Link>
                </li>
            </ul>
        </section>

        <section
            v-if="done.length"
            class="flex flex-col gap-3"
            aria-labelledby="klart"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 id="klart" class="text-title">Klart</h2>
                <Link
                    v-if="can.update"
                    :href="CompletedListItemsController.destroy(list.id)"
                    as="button"
                    class="min-h-tap-adult rounded-md px-3 font-bold hover:bg-surface-sunken"
                >
                    Ta bort det som är klart
                </Link>
            </div>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="item in done"
                    :key="item.id"
                    class="flex items-center gap-2 rounded-lg border border-line bg-surface-raised py-1 pr-3 pl-1"
                >
                    <CompletionToggle
                        v-if="can.addItems"
                        :completed="true"
                        :href="
                            ListItemCompletionController.destroy([
                                list.id,
                                item.id,
                            ])
                        "
                        :label="item.name"
                    />
                    <p
                        class="min-w-0 flex-1 py-2 break-words text-ink-muted"
                        :class="!can.addItems && 'pl-3'"
                    >
                        {{ item.name }}
                    </p>
                    <DoneChip />
                </li>
            </ul>
        </section>

        <section
            v-if="can.update"
            class="flex flex-col gap-3 border-t border-line pt-8"
            aria-labelledby="installningar"
        >
            <h2 id="installningar" class="text-title">Listans namn</h2>
            <Form
                v-bind="ListController.update.form(list.id)"
                class="flex flex-wrap items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid min-w-60 flex-1 gap-2">
                    <Label for="list-name" class="sr-only">Listans namn</Label>
                    <Input
                        id="list-name"
                        name="name"
                        :default-value="list.name"
                        required
                        maxlength="100"
                    />
                    <InputError :message="errors.name" />
                </div>
                <Button variant="outline" type="submit" :disabled="processing">
                    Spara
                </Button>
            </Form>
            <Form
                v-if="can.delete"
                v-bind="ListController.destroy.form(list.id)"
                v-slot="{ processing }"
            >
                <Button
                    variant="destructive"
                    type="submit"
                    :disabled="processing"
                >
                    Ta bort listan
                </Button>
            </Form>
        </section>
    </div>
</template>
