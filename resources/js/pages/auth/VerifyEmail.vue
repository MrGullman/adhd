<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Bekräfta e-postadressen',
        description:
            'Bekräfta din e-postadress genom att klicka på länken i mejlet vi skickade.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Bekräfta e-postadressen" />

    <div
        v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        En ny bekräftelselänk har skickats till e-postadressen du angav.
    </div>

    <Form
        v-bind="send.form()"
        class="space-y-6 text-center"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" variant="secondary">
            <Spinner v-if="processing" />
            Skicka bekräftelsemejlet igen
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm">
            Logga ut
        </TextLink>
    </Form>
</template>
