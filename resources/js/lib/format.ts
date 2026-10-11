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
