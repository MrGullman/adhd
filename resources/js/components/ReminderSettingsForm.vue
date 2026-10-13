<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    action: string;
    settings: {
        remindersEnabled: boolean;
        quietFrom: string | null;
        quietUntil: string | null;
    };
}>();

const form = useForm({
    reminders_enabled: props.settings.remindersEnabled,
    quiet_from: props.settings.quietFrom ?? '',
    quiet_until: props.settings.quietUntil ?? '',
});

function save(): void {
    form.put(props.action, { preserveScroll: true });
}
</script>

<!-- På eller av, och tysta timmar då inga påminnelser kommer. -->
<template>
    <form class="flex flex-col gap-4" @submit.prevent="save">
        <label class="flex min-h-tap-adult items-center gap-3">
            <input
                v-model="form.reminders_enabled"
                type="checkbox"
                class="size-5"
            />
            Skicka påminnelser
        </label>
        <fieldset v-if="form.reminders_enabled" class="flex flex-col gap-2">
            <legend class="mb-2 font-bold">Tysta timmar</legend>
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-2">
                    <Label for="quiet-from">Från</Label>
                    <Input
                        id="quiet-from"
                        v-model="form.quiet_from"
                        type="time"
                        class="w-32"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="quiet-until">Till</Label>
                    <Input
                        id="quiet-until"
                        v-model="form.quiet_until"
                        type="time"
                        class="w-32"
                    />
                </div>
            </div>
            <InputError
                :message="form.errors.quiet_from ?? form.errors.quiet_until"
            />
            <p class="text-caption text-ink-muted">
                Inga påminnelser kommer under de tysta timmarna. Lämna tomt för
                inga tysta timmar.
            </p>
        </fieldset>
        <div>
            <Button type="submit" variant="outline" :disabled="form.processing">
                Spara
            </Button>
        </div>
    </form>
</template>
