import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { ComputedRef } from 'vue';
import type { DayGroup, RoutineSummary, TaskRowData } from '@/types';

/** How long a task just ticked off stays where it was, before it moves down. */
const LINGER_MS = 2500;

/**
 * Min dag shows what is left first: done tasks and routines move to "Klart
 * idag" at the bottom, so that the next thing is at the top. A task just
 * ticked off stays in its place for a moment with its tick, so that nothing
 * jumps away under the child's finger.
 */
export function useDoneLast(
    groups: () => DayGroup[],
    routines: () => RoutineSummary[],
): {
    openGroups: ComputedRef<DayGroup[]>;
    openRoutines: ComputedRef<RoutineSummary[]>;
    doneTasks: ComputedRef<TaskRowData[]>;
    doneRoutines: ComputedRef<RoutineSummary[]>;
} {
    const lingering = ref<number[]>([]);
    const timers: ReturnType<typeof setTimeout>[] = [];
    let completedBefore = new Set(
        groups()
            .flatMap((group) => group.tasks)
            .filter((task) => task.completed)
            .map((task) => task.id),
    );

    watch(
        () =>
            groups()
                .flatMap((group) => group.tasks)
                .filter((task) => task.completed)
                .map((task) => task.id),
        (completed) => {
            const justDone = completed.filter((id) => !completedBefore.has(id));
            completedBefore = new Set(completed);

            for (const id of justDone) {
                lingering.value = [...lingering.value, id];
                timers.push(
                    setTimeout(() => {
                        lingering.value = lingering.value.filter(
                            (waiting) => waiting !== id,
                        );
                    }, LINGER_MS),
                );
            }
        },
    );
    onBeforeUnmount(() => timers.forEach(clearTimeout));

    const shown = (task: TaskRowData) =>
        !task.completed || lingering.value.includes(task.id);

    return {
        openGroups: computed(() =>
            groups()
                .map((group) => ({
                    ...group,
                    tasks: group.tasks.filter(shown),
                }))
                .filter((group) => group.tasks.length > 0),
        ),
        openRoutines: computed(() =>
            routines().filter((routine) => !routine.completed),
        ),
        doneTasks: computed(() =>
            groups()
                .flatMap((group) => group.tasks)
                .filter((task) => !shown(task)),
        ),
        doneRoutines: computed(() =>
            routines().filter((routine) => routine.completed),
        ),
    };
}
