<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import DisplayController from '@/actions/App/Http/Controllers/Family/DisplayController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type ScreenSettings = {
    weekday: { on: string; off: string };
    weekend: { on: string; off: string };
    reboot: { enabled: boolean; time: string };
    cec: boolean;
};

const props = defineProps<{ deviceId: number; screen: ScreenSettings }>();

const form = useForm({
    weekday_on: props.screen.weekday.on,
    weekday_off: props.screen.weekday.off,
    weekend_on: props.screen.weekend.on,
    weekend_off: props.screen.weekend.off,
    reboot_enabled: props.screen.reboot.enabled,
    reboot_time: props.screen.reboot.time,
    cec: props.screen.cec,
});

function save(): void {
    form.put(DisplayController.schedule.url(props.deviceId), {
        preserveScroll: true,
    });
}

const spans = [
    { key: 'weekday', label: 'Vardagar' },
    { key: 'weekend', label: 'Lördag och söndag' },
] as const;
</script>

<!--
    Skärmen på och av: Raspberry Pi:n frågar varje minut och släcker eller
    tänder skärmen (docs/DRIFT.md). Utan Pi:n blir sidan mörk i stället.
-->
<template>
    <form class="flex flex-col gap-5" @submit.prevent="save">
        <fieldset
            v-for="span in spans"
            :key="span.key"
            class="flex flex-col gap-2"
        >
            <legend class="mb-2 font-bold">{{ span.label }}</legend>
            <div class="flex flex-wrap items-end gap-3">
                <div class="grid gap-2">
                    <Label :for="`${span.key}-on`">Tänds</Label>
                    <Input
                        :id="`${span.key}-on`"
                        v-model="form[`${span.key}_on`]"
                        type="time"
                        class="w-32"
                        required
                    />
                </div>
                <div class="grid gap-2">
                    <Label :for="`${span.key}-off`">Släcks</Label>
                    <Input
                        :id="`${span.key}-off`"
                        v-model="form[`${span.key}_off`]"
                        type="time"
                        class="w-32"
                        required
                    />
                </div>
            </div>
            <InputError
                :message="
                    form.errors[`${span.key}_on`] ??
                    form.errors[`${span.key}_off`]
                "
            />
        </fieldset>

        <div class="flex flex-col gap-2">
            <label class="flex min-h-tap-adult items-center gap-3">
                <input
                    v-model="form.reboot_enabled"
                    type="checkbox"
                    class="size-5"
                />
                Starta om Raspberry Pi:n varje morgon
            </label>
            <div v-if="form.reboot_enabled" class="grid w-fit gap-2">
                <Label for="reboot-time">Klockan</Label>
                <Input
                    id="reboot-time"
                    v-model="form.reboot_time"
                    type="time"
                    class="w-32"
                    required
                />
                <InputError :message="form.errors.reboot_time" />
            </div>
            <p class="text-caption text-ink-muted">
                En omstart varje dag håller skärmen pigg. Den tar ett par
                minuter, så lägg den innan skärmen tänds.
            </p>
        </div>

        <div class="flex flex-col gap-1">
            <label class="flex min-h-tap-adult items-center gap-3">
                <input v-model="form.cec" type="checkbox" class="size-5" />
                Styr tv:n via HDMI (viloläge och på)
            </label>
            <p class="text-caption text-ink-muted">
                För en tv: Pi:n säger åt tv:n att gå i viloläge när skärmen
                släcks och att slå på när den tänds. En datorskärm går i
                viloläge av sig själv. Fungerar med de flesta tv-apparater (CEC,
                t.ex. Anynet+, Bravia Sync, SimpLink).
            </p>
        </div>

        <div>
            <Button type="submit" variant="outline" :disabled="form.processing">
                Spara tiderna
            </Button>
        </div>
    </form>
</template>
