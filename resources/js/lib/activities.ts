import type { ActivityEntry, ChildPacking, ChildPackingStatus } from '@/types';

/**
 * "2 sömnar kvar tills Mamma kommer hem" for someone away
 * (docs/STYLEGUIDE.md, Tid som syns).
 */
export function awayText(activity: ActivityEntry): string | null {
    if (activity.type !== 'away' || activity.nightsLeft === null) {
        return null;
    }

    const who = activity.participants.length
        ? activity.participants.map((person) => person.name).join(' och ')
        : activity.name;

    if (activity.nightsLeft === 0) {
        return `${who} kommer hem idag`;
    }

    const nights = activity.nightsLeft === 1 ? 'sömn' : 'sömnar';

    return `${activity.nightsLeft} ${nights} kvar tills ${who} kommer hem`;
}

export function childPacking(activity: ActivityEntry): ChildPacking | null {
    return activity.packing && !Array.isArray(activity.packing)
        ? activity.packing
        : null;
}

export function packingStatuses(activity: ActivityEntry): ChildPackingStatus[] {
    return Array.isArray(activity.packing) ? activity.packing : [];
}

/**
 * "Alva har packat" or "Alva: 3 av 5 packade".
 */
export function packingStatusText(status: ChildPackingStatus): string {
    if (
        status.confirmed ||
        (status.total > 0 && status.packed === status.total)
    ) {
        return `${status.child} har packat`;
    }

    return `${status.child}: ${status.packed} av ${status.total} packade`;
}
