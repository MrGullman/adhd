import type { DayPart } from '@/types';

/**
 * "Nu" only when a task's time has come. A task for a part of the day, or
 * later today, is "Nästa": "Nu" makes it sound as if it must be done this
 * minute, which is stressful (docs/STYLEGUIDE.md, inga stressande budskap).
 */
export function isDueNow(time: string | null): boolean {
    if (!time) {
        return false;
    }

    const now = new Intl.DateTimeFormat('sv-SE', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Europe/Stockholm',
    }).format(new Date());

    return time <= now;
}

const parts: Record<DayPart, string> = {
    morning: 'På morgonen',
    forenoon: 'Under förmiddagen',
    afternoon: 'Under eftermiddagen',
    evening: 'På kvällen',
};

/**
 * When a task is to be done, calmly: "Klockan 16:00" or "Under eftermiddagen".
 */
export function whenText(
    time: string | null,
    dayPart: DayPart | null,
): string | null {
    if (time) {
        return `Klockan ${time}`;
    }

    return dayPart ? parts[dayPart] : null;
}
