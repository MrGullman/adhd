const dateTime = new Intl.DateTimeFormat('sv-SE', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: 'Europe/Stockholm',
});

const date = new Intl.DateTimeFormat('sv-SE', {
    dateStyle: 'medium',
    timeZone: 'Europe/Stockholm',
});

/**
 * "13 okt. 2026 07:12" in Swedish time.
 */
export function formatDateTime(iso: string | null): string {
    return iso ? dateTime.format(new Date(iso)) : '–';
}

/**
 * "13 okt. 2026" in Swedish time.
 */
export function formatDate(iso: string): string {
    return date.format(new Date(iso));
}

/**
 * Swedish genitive: "Ellas", but "Elias" and "Max".
 */
export function possessive(name: string): string {
    return /[sxz]$/i.test(name) ? name : `${name}s`;
}

// Calendar days (YYYY-MM-DD) have no time of day, so they are formatted as UTC
// to avoid moving to the day before or after.
const day = new Intl.DateTimeFormat('sv-SE', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    timeZone: 'UTC',
});

/**
 * "tisdag 13 oktober" for a calendar day.
 */
export function formatDay(date: string): string {
    return day.format(new Date(`${date}T00:00:00Z`));
}
