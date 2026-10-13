<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import PushToggle from '@/components/PushToggle.vue';
import ReminderSettingsForm from '@/components/ReminderSettingsForm.vue';
import { edit, update } from '@/routes/reminders';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Påminnelser', href: edit() }],
    },
});

defineProps<{
    vapidPublicKey: string | null;
    settings: {
        remindersEnabled: boolean;
        quietFrom: string | null;
        quietUntil: string | null;
    };
}>();
</script>

<template>
    <Head title="Påminnelser" />

    <h1 class="sr-only">Påminnelser</h1>

    <div class="space-y-10">
        <section class="space-y-4">
            <Heading
                variant="small"
                title="Den här enheten"
                description="Påminnelser kommer som notiser på de enheter där du slagit på dem."
            />
            <PushToggle :vapid-public-key="vapidPublicKey" />
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Vad och när"
                description="Dina uppgifter med klockslag, och händelser i din kalender 15 minuter innan."
            />
            <ReminderSettingsForm :action="update.url()" :settings="settings" />
        </section>
    </div>
</template>
