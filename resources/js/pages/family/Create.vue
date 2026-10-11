<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import FamilyController from '@/actions/App/Http/Controllers/Family/FamilyController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Skapa er familj',
        description: 'Familjen samlar barnens dag, rutiner och listor.',
    },
});
</script>

<template>
    <Head title="Skapa familj" />

    <Form
        v-bind="FamilyController.store.form()"
        class="flex flex-col gap-6"
        v-slot="{ errors, processing }"
    >
        <div class="grid gap-2">
            <Label for="name">Familjens namn</Label>
            <Input
                id="name"
                name="name"
                required
                autofocus
                maxlength="100"
                placeholder="t.ex. Familjen Andersson"
            />
            <InputError :message="errors.name" />
        </div>

        <Button type="submit" class="w-full" :disabled="processing">
            <Spinner v-if="processing" />
            Skapa familj
        </Button>
    </Form>
</template>
