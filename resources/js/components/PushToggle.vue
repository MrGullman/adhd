<script setup lang="ts">
import { Bell, BellOff, Share, SquarePlus } from '@lucide/vue';
import { onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { useChildStyle } from '@/composables/useChildStyle';
import { usePush } from '@/lib/push';

const props = withDefaults(
    defineProps<{ vapidPublicKey: string | null; child?: boolean }>(),
    { child: false },
);

const { state, busy, error, refresh, turnOn, turnOff } = usePush(
    props.vapidPublicKey,
);

const style = useChildStyle();

onMounted(refresh);
</script>

<!--
    Påminnelser på den här enheten. iPhone kräver att appen läggs på
    hemskärmen först, så då visas hur man gör.
-->
<template>
    <div v-if="child" class="contents">
        <button
            v-if="state === 'off'"
            type="button"
            :class="style.pill"
            :disabled="busy"
            @click="turnOn"
        >
            <Bell :class="style.icon" aria-hidden="true" />
            Slå på påminnelser
        </button>
    </div>

    <div v-else class="flex flex-col gap-3">
        <p v-if="error" class="font-bold" role="alert">{{ error }}</p>
        <template v-if="state === 'needs-home-screen'">
            <p>
                På iPhone och iPad kommer påminnelser bara när appen ligger på
                hemskärmen:
            </p>
            <ol class="flex list-decimal flex-col gap-2 pl-6">
                <li>
                    Öppna sidan i Safari och tryck på
                    <Share class="inline size-4" aria-label="Dela" />
                    Dela.
                </li>
                <li>
                    Välj
                    <SquarePlus class="inline size-4" aria-hidden="true" />
                    Lägg till på hemskärmen.
                </li>
                <li>Öppna appen från hemskärmen och kom tillbaka hit.</li>
            </ol>
        </template>
        <p v-else-if="state === 'unsupported'" class="text-ink-muted">
            Den här webbläsaren kan inte ta emot påminnelser.
        </p>
        <p v-else-if="state === 'denied'" class="text-ink-muted">
            Notiser är avstängda för appen i telefonens inställningar. Slå på
            dem där för att få påminnelser.
        </p>
        <template v-else>
            <p class="flex items-center gap-2 font-bold">
                <Bell v-if="state === 'on'" class="size-5" aria-hidden="true" />
                <BellOff v-else class="size-5" aria-hidden="true" />
                {{
                    state === 'on'
                        ? 'Påminnelser är på för den här enheten.'
                        : 'Påminnelser är av för den här enheten.'
                }}
            </p>
            <div>
                <Button v-if="state === 'off'" :disabled="busy" @click="turnOn">
                    Slå på på den här enheten
                </Button>
                <Button
                    v-else
                    variant="outline"
                    :disabled="busy"
                    @click="turnOff"
                >
                    Stäng av på den här enheten
                </Button>
            </div>
        </template>
    </div>
</template>
