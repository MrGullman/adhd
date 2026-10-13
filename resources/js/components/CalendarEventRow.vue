<script setup lang="ts">
import { CalendarDays } from '@lucide/vue';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import type { CalendarEventEntry } from '@/types';

withDefaults(defineProps<{ event: CalendarEventEntry; child?: boolean }>(), {
    child: false,
});
</script>

<!-- En händelse från en kalender (Google, iPhone, Outlook). Barn ser bild och stor text. -->
<template>
    <div
        class="flex items-center gap-3 rounded-lg border border-line bg-surface-raised"
        :class="child ? 'rounded-xl border-2 p-4' : 'p-3'"
    >
        <PictureThumb
            v-if="event.picture"
            :picture="event.picture"
            :size="child ? 'md' : 'sm'"
        />
        <CalendarDays
            v-else
            class="shrink-0 text-ink-muted"
            :class="child ? 'size-10' : 'size-6'"
            aria-hidden="true"
        />
        <div class="min-w-0 flex-1">
            <p
                class="break-words"
                :class="
                    child
                        ? 'font-display text-child-label font-semibold'
                        : 'font-bold'
                "
            >
                {{ event.title }}
            </p>
            <p
                class="text-ink-muted tabular-nums"
                :class="
                    child ? 'font-display text-child-label' : 'text-caption'
                "
            >
                <template v-if="event.allDay">Hela dagen</template>
                <template v-else-if="event.time">
                    {{ event.time
                    }}<template v-if="event.endTime"
                        >–{{ event.endTime }}</template
                    >
                </template>
                <template v-else-if="event.endTime">
                    Till {{ event.endTime }}
                </template>
                <template v-if="!child && event.location">
                    · {{ event.location }}
                </template>
            </p>
        </div>
        <FamilyAvatar
            v-if="event.owner && !child"
            :name="event.owner.name"
            :color="event.owner.avatarColor"
            :picture-url="event.owner.avatarUrl"
            size="sm"
        />
    </div>
</template>
