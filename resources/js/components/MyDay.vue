<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, Check, ChevronDown, House, ShoppingCart } from '@lucide/vue';
import { computed, ref } from 'vue';
import TaskCompletionController from '@/actions/App/Http/Controllers/Tasks/TaskCompletionController';
import FamilyAvatar from '@/components/FamilyAvatar.vue';
import PictureThumb from '@/components/PictureThumb.vue';
import TaskRow from '@/components/TaskRow.vue';
import { packingStatuses, packingStatusText } from '@/lib/activities';
import { homeworkText, isDueNow, whenText } from '@/lib/now';
import { index as listsIndex } from '@/routes/lists';
import type {
    ActivityEntry,
    DayGroup,
    Member,
    RoutineSummary,
    TaskRowData,
} from '@/types';

const props = defineProps<{
    myDay: {
        userId: number;
        now: TaskRowData | null;
        shopping: { count: number; items: string[] };
        dinnerMissing: string[];
    };
    members: Array<
        Member & { isChild: boolean; total: number; completed: number }
    >;
    groups: DayGroup[];
    routines: RoutineSummary[];
    activities: ActivityEntry[];
    canEdit: boolean;
}>();

const tasks = computed(() => props.groups.flatMap((group) => group.tasks));
const children = computed(() =>
    props.members.filter((member) => member.isChild),
);
const opened = ref<number | null>(null);

// Det gemensamma hemma: uppgifter som inte är någons, som inte är klara.
const household = computed(() =>
    tasks.value.filter((task) => !task.assignee && !task.completed),
);

// Mina egna aktiviteter idag.
const myActivities = computed(() =>
    props.activities.filter((activity) =>
        activity.participants.some(
            (member) => member.id === props.myDay.userId,
        ),
    ),
);

/** Ett barns dag på några rader: uppgifter, rutiner, aktiviteter och läxor. */
function childDay(child: Member & { total: number; completed: number }) {
    const own = tasks.value.filter((task) => task.assignee?.id === child.id);

    return {
        left: own.filter((task) => !task.completed && !task.homework),
        homework: own.filter((task) => task.homework && !task.completed),
        routines: props.routines.filter(
            (routine) => routine.child.id === child.id,
        ),
        activities: props.activities
            .map((activity) => ({
                activity,
                packing: packingStatuses(activity).find(
                    (status) => status.child === child.name,
                ),
            }))
            .filter(({ activity }) =>
                activity.participants.some((member) => member.id === child.id),
            ),
    };
}

const card =
    'flex flex-col gap-3 rounded-lg border border-line bg-surface-raised p-4';
</script>

<!--
    Min dag för vuxna: vad som är nästa för mig, hur barnens dag går, läxor,
    det som ska göras hemma och det som ska handlas. Lugnt och i korthet.
-->
<template>
    <div class="flex flex-col gap-6">
        <!-- Nu / Nästa för mig -->
        <section
            class="flex items-center gap-3 rounded-lg border-2 p-4"
            :class="
                myDay.now
                    ? 'border-primary bg-primary-soft'
                    : 'border-line bg-surface-raised'
            "
            aria-labelledby="mitt-nu"
        >
            <template v-if="myDay.now">
                <h2
                    id="mitt-nu"
                    class="shrink-0 rounded-full px-3 text-overline font-bold uppercase"
                    :class="
                        isDueNow(myDay.now.time)
                            ? 'bg-primary text-on-primary'
                            : 'bg-surface-raised'
                    "
                >
                    {{ isDueNow(myDay.now.time) ? 'Nu' : 'Nästa' }}
                </h2>
                <PictureThumb
                    v-if="myDay.now.picture"
                    :picture="myDay.now.picture"
                />
                <div class="flex min-w-0 flex-1 flex-col">
                    <span class="font-bold break-words">{{
                        myDay.now.title
                    }}</span>
                    <span
                        v-if="whenText(myDay.now.time, myDay.now.dayPart)"
                        class="text-caption text-ink-muted"
                    >
                        {{ whenText(myDay.now.time, myDay.now.dayPart) }}
                    </span>
                </div>
                <Link
                    :href="TaskCompletionController.store(myDay.now.id)"
                    as="button"
                    preserve-scroll
                    class="flex min-h-tap-adult shrink-0 items-center gap-2 rounded-full border-2 border-primary bg-surface-raised px-5 font-bold"
                >
                    <Check class="size-5" stroke-width="3" aria-hidden="true" />
                    Klar
                </Link>
            </template>
            <h2 v-else id="mitt-nu" class="font-bold">
                Inget mer för dig just nu.
            </h2>
        </section>

        <ul v-if="myActivities.length" class="flex flex-col gap-1 text-caption">
            <li v-for="activity in myActivities" :key="activity.id">
                <span class="font-bold">{{ activity.time ?? 'Idag' }}</span>
                {{ activity.name }}
            </li>
        </ul>

        <!-- Barnen -->
        <section
            v-if="children.length"
            class="flex flex-col gap-3"
            aria-labelledby="barnen"
        >
            <h2 id="barnen" class="text-title">Barnen</h2>
            <article v-for="child in children" :key="child.id" :class="card">
                <button
                    type="button"
                    class="flex w-full items-center gap-3 text-left"
                    :aria-expanded="opened === child.id"
                    @click="opened = opened === child.id ? null : child.id"
                >
                    <FamilyAvatar
                        :name="child.name"
                        :color="child.avatarColor"
                        :picture-url="child.avatarUrl"
                        size="sm"
                    />
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="font-bold">{{ child.name }}</span>
                        <span class="text-caption text-ink-muted">
                            {{
                                child.total
                                    ? child.completed === child.total
                                        ? 'Allt klart idag'
                                        : `${child.completed} av ${child.total} klara`
                                    : 'Inga uppgifter idag'
                            }}
                        </span>
                    </span>
                    <ChevronDown
                        class="size-5 shrink-0 text-ink-muted motion-safe:transition-transform motion-safe:duration-200"
                        :class="opened === child.id && 'rotate-180'"
                        aria-hidden="true"
                    />
                </button>

                <ul class="flex flex-col gap-1 text-caption">
                    <li
                        v-for="routine in childDay(child).routines"
                        :key="`r${routine.id}`"
                    >
                        <span class="font-bold">{{ routine.name }}:</span>
                        {{
                            routine.completed
                                ? 'klar'
                                : `${routine.progress.completed} av ${routine.progress.total}` +
                                  (routine.doneBy
                                      ? ` · klar ${routine.doneBy}`
                                      : '')
                        }}
                    </li>
                    <li
                        v-for="{ activity, packing } in childDay(child)
                            .activities"
                        :key="`a${activity.id}`"
                    >
                        <span class="font-bold">{{ activity.name }}</span>
                        <template v-if="activity.time">
                            {{ activity.time }}</template
                        >
                        <template v-if="packing">
                            · {{ packingStatusText(packing) }}</template
                        >
                    </li>
                    <li
                        v-for="task in childDay(child).homework"
                        :key="`h${task.id}`"
                        class="flex items-center gap-1"
                    >
                        <BookOpen class="size-4 shrink-0" aria-hidden="true" />
                        <span class="font-bold">{{ task.title }}</span>
                        <template v-if="task.dueDate">
                            ·
                            {{
                                homeworkText(task.dueDate).replace(
                                    'Läxa · ',
                                    '',
                                )
                            }}
                        </template>
                    </li>
                </ul>

                <!-- Det som är kvar för barnet -->
                <ul v-if="opened === child.id" class="flex flex-col gap-2">
                    <TaskRow
                        v-for="task in childDay(child).left"
                        :key="task.id"
                        :task="task"
                        :can-edit="canEdit"
                    />
                    <li
                        v-if="!childDay(child).left.length"
                        class="p-3 text-caption text-ink-muted"
                    >
                        Inget kvar att göra idag.
                    </li>
                </ul>
            </article>
        </section>

        <!-- Hemma -->
        <section :class="card" aria-labelledby="hemma">
            <h2 id="hemma" class="flex items-center gap-2 text-title">
                <House class="size-5" aria-hidden="true" />
                Hemma
            </h2>
            <ul v-if="household.length" class="flex flex-col gap-2">
                <TaskRow
                    v-for="task in household"
                    :key="task.id"
                    :task="task"
                    :can-edit="canEdit"
                />
            </ul>
            <p v-else class="text-caption text-ink-muted">
                Inget gemensamt att göra idag.
            </p>
        </section>

        <!-- Handla -->
        <section :class="card" aria-labelledby="handla-min-dag">
            <h2 id="handla-min-dag" class="flex items-center gap-2 text-title">
                <ShoppingCart class="size-5" aria-hidden="true" />
                Handla
            </h2>
            <p v-if="myDay.dinnerMissing.length" class="font-bold">
                Till middagen saknas: {{ myDay.dinnerMissing.join(', ') }}
            </p>
            <p v-if="myDay.shopping.count" class="text-caption">
                {{ myDay.shopping.items.join(', ')
                }}<template
                    v-if="myDay.shopping.count > myDay.shopping.items.length"
                >
                    och
                    {{ myDay.shopping.count - myDay.shopping.items.length }}
                    till</template
                >
            </p>
            <p
                v-if="!myDay.shopping.count && !myDay.dinnerMissing.length"
                class="text-caption text-ink-muted"
            >
                Inget att handla just nu.
            </p>
            <Link
                :href="listsIndex()"
                class="w-fit text-caption font-bold underline"
            >
                Till listorna
            </Link>
        </section>
    </div>
</template>
