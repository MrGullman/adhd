<script setup lang="ts">
import { useHttp, usePage } from '@inertiajs/vue3';
import { MessageSquarePlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import FeedbackController from '@/actions/App/Http/Controllers/FeedbackController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const page = usePage();

// Shown where admins have chosen: on pages under development or everywhere.
const shown = computed(() => {
    const feedback = page.props.feedback;

    if (!feedback || feedback.mode === 'off') {
        return false;
    }

    return (
        feedback.mode === 'all' ||
        feedback.pages.some((pattern) =>
            pattern.endsWith('*')
                ? page.component.startsWith(pattern.slice(0, -1))
                : page.component === pattern,
        )
    );
});

// The page's name as in the browser tab, e.g. "Inventera kylskåp".
const pageTitle = ref('');
const open = ref(false);

const form = useHttp<
    { message: string; page: string; url: string },
    { id: number }
>({ message: '', page: '', url: '' });

function start(): void {
    pageTitle.value = document.title.replace(/\s+-\s+NPF-familjen$/, '');
    form.clearErrors();
    open.value = true;
}

async function send(): Promise<void> {
    form.page = page.component;
    form.url = page.url;

    const response = await form.post(FeedbackController.store.url());

    if (response) {
        form.reset();
        open.value = false;
        toast.success('Tack! Vi läser allt som skickas in.');
    }
}
</script>

<!-- "Tyck till": en knapp nere till höger som öppnar en ruta. Sidan skickas med. -->
<template>
    <div v-if="shown" class="contents">
        <!-- Room below the page, so that the button never hides the last thing on it. -->
        <div class="h-20 shrink-0" aria-hidden="true" />
        <button
            type="button"
            class="fixed right-4 z-40 flex min-h-tap-adult items-center gap-2 rounded-full border-2 border-line bg-surface-raised px-4 font-bold shadow-raised hover:border-primary"
            style="bottom: calc(1rem + env(safe-area-inset-bottom, 0px))"
            @click="start"
        >
            <MessageSquarePlus class="size-5" aria-hidden="true" />
            Tyck till
        </button>

        <Dialog v-model:open="open">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Tyck till</DialogTitle>
                    <DialogDescription>
                        Om sidan <span class="font-bold">{{ pageTitle }}</span
                        >. Vad saknas, vad krånglar eller vad önskar du?
                    </DialogDescription>
                </DialogHeader>
                <form
                    id="feedback-form"
                    class="grid gap-2"
                    @submit.prevent="send"
                >
                    <Label for="feedback-message" class="sr-only">
                        Din feedback
                    </Label>
                    <textarea
                        id="feedback-message"
                        v-model="form.message"
                        v-focus
                        required
                        maxlength="2000"
                        rows="5"
                        placeholder="T.ex. Jag vill kunna sortera efter datum"
                        class="w-full rounded-md border-2 border-input bg-surface-raised p-3"
                    />
                    <InputError :message="form.errors.message" />
                    <p class="text-caption text-ink-muted">
                        Vilken sida du är på skickas med. Skriv inte barnens
                        namn eller annat känsligt.
                    </p>
                </form>
                <DialogFooter class="gap-2">
                    <Button type="button" variant="ghost" @click="open = false">
                        Avbryt
                    </Button>
                    <Button
                        type="submit"
                        form="feedback-form"
                        :disabled="form.processing || !form.message.trim()"
                    >
                        Skicka
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
