<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ClipboardList, ShoppingCart } from '@lucide/vue';
import ListController from '@/actions/App/Http/Controllers/Lists/ListController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as listsIndex } from '@/routes/lists';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Listor', href: listsIndex() }],
    },
});

defineProps<{
    lists: Array<{
        id: number;
        name: string;
        isShopping: boolean;
        openCount: number;
    }>;
    canCreate: boolean;
}>();
</script>

<template>
    <Head title="Listor" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-heading font-semibold">Listor</h1>
            <p class="text-ink-muted">
                Handlingslistan och familjens andra listor.
            </p>
        </header>

        <ul class="grid gap-3 sm:grid-cols-2">
            <li v-for="list in lists" :key="list.id">
                <Link
                    :href="ListController.show(list.id)"
                    class="flex min-h-tap-adult items-center gap-4 rounded-lg border border-line bg-surface-raised p-4 hover:border-primary"
                >
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-md bg-surface-sunken"
                    >
                        <ShoppingCart
                            v-if="list.isShopping"
                            class="size-6"
                            aria-hidden="true"
                        />
                        <ClipboardList
                            v-else
                            class="size-6"
                            aria-hidden="true"
                        />
                    </span>
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-title">{{ list.name }}</span>
                        <span class="text-caption text-ink-muted">
                            {{
                                list.openCount === 0
                                    ? 'Inget kvar'
                                    : list.openCount === 1
                                      ? '1 sak kvar'
                                      : `${list.openCount} saker kvar`
                            }}
                        </span>
                    </span>
                </Link>
            </li>
        </ul>

        <Form
            v-if="canCreate"
            v-bind="ListController.store.form()"
            class="flex flex-wrap items-end gap-3 rounded-lg bg-surface-sunken p-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid min-w-60 flex-1 gap-2">
                <Label for="list-name">Ny lista</Label>
                <Input
                    id="list-name"
                    name="name"
                    required
                    maxlength="100"
                    placeholder="Till exempel Att göra i helgen"
                />
                <InputError :message="errors.name" />
            </div>
            <Button type="submit" :disabled="processing">Skapa lista</Button>
        </Form>
    </div>
</template>
