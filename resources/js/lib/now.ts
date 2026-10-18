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

/**
 * "Läxa · idag", "Läxa · till imorgon", "Läxa · till fredag" or, further on,
 * "Läxa · till 24/10".
 */
export function homeworkText(dueDate: string): string {
    const today = new Intl.DateTimeFormat('sv-SE', {
        timeZone: 'Europe/Stockholm',
    }).format(new Date());
    const days = Math.round(
        (Date.parse(`${dueDate}T12:00:00Z`) -
            Date.parse(`${today}T12:00:00Z`)) /
            86_400_000,
    );

    if (days <= 0) {
        return 'Läxa · idag';
    }

    if (days === 1) {
        return 'Läxa · till imorgon';
    }

    const date = new Date(`${dueDate}T12:00:00Z`);

    return days < 7
        ? `Läxa · till ${new Intl.DateTimeFormat('sv-SE', { weekday: 'long', timeZone: 'UTC' }).format(date)}`
        : `Läxa · till ${date.getUTCDate()}/${date.getUTCMonth() + 1}`;
}
