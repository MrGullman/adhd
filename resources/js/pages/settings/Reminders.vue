<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import ChildRemindersForm from '@/components/ChildRemindersForm.vue';
import Heading from '@/components/Heading.vue';
import PushToggle from '@/components/PushToggle.vue';
import ReminderSettingsForm from '@/components/ReminderSettingsForm.vue';
import { Button } from '@/components/ui/button';
import { edit, test, update } from '@/routes/reminders';
import type { Member } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Påminnelser', href: edit() }],
    },
});

defineProps<{
    vapidPublicKey: string | null;
    devices: number;
    settings: {
        remindersEnabled: boolean;
        quietFrom: string | null;
        quietUntil: string | null;
    };
    childReminders: { enabled: boolean; childIds: number[] | null };
    children: Member[];
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
            <p class="text-caption text-ink-muted">
                {{
                    devices === 0
                        ? 'Ingen av dina enheter tar emot påminnelser än.'
                        : devices === 1
                          ? '1 av dina enheter tar emot påminnelser.'
                          : `${devices} av dina enheter tar emot påminnelser.`
                }}
            </p>
            <Button
                v-if="devices > 0"
                variant="outline"
                @click="router.post(test.url(), {}, { preserveScroll: true })"
            >
                Skicka en testnotis
            </Button>
        </section>

        <section class="space-y-4">
            <Heading
                variant="small"
                title="Vad och när"
                description="Dina uppgifter med klockslag, och händelser i din kalender 15 minuter innan."
            />
            <ReminderSettingsForm :action="update.url()" :settings="settings" />
        </section>

        <section v-if="children.length" class="space-y-4">
            <Heading
                variant="small"
                title="Barnens aktiviteter"
                description="Få samma påminnelser som barnen före deras aktiviteter."
            />
            <ChildRemindersForm
                :settings="childReminders"
                :children="children"
            />
        </section>
    </div>
</template>
