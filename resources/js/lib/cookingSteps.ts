/**
 * The same rules as App\Food\CookingSteps, for the preview while a recipe is
 * written: a step per line, a line ending with ":" is a heading, and a time
 * in a step becomes a timer. Keep the two in step.
 */
export type CookingStep = {
    number: number;
    text: string;
    section: string | null;
    timers: Array<{ seconds: number; label: string }>;
};

const MAX_SECONDS = 18_000;

const WORDS: Record<string, number> = {
    'en kvart': 900,
    'en halvtimme': 1800,
    'en halv timme': 1800,
    'en timme': 3600,
};

export function cookingSteps(instructions: string | null): CookingStep[] {
    const steps: CookingStep[] = [];
    let section: string | null = null;

    for (const line of (instructions ?? '').split(/\r?\n/)) {
        const text = line.replace(/^\s*(\d+\s*[.)]|[-•*])\s*/u, '').trim();

        if (text === '') {
            continue;
        }

        if (text.endsWith(':')) {
            section = text.replace(/:+$/, '');
            continue;
        }

        steps.push({
            number: steps.length + 1,
            text,
            section,
            timers: timers(text),
        });
    }

    return steps;
}

export function timers(
    text: string,
): Array<{ seconds: number; label: string }> {
    const lower = text.toLowerCase();
    const found: Array<{ at: number; seconds: number }> = [];
    const pattern =
        /(\d+(?:[.,]\d+)?)(?:\s*(?:-|–|till)\s*\d+(?:[.,]\d+)?)?\s*(timmar|timme|tim|h|minuter|minut|min|sekunder|sek|s)(?![\p{L}])/gu;

    for (const match of lower.matchAll(pattern)) {
        const amount = Number(match[1].replace(',', '.'));
        const unit = match[2];
        const factor = ['timmar', 'timme', 'tim', 'h'].includes(unit)
            ? 3600
            : ['minuter', 'minut', 'min'].includes(unit)
              ? 60
              : 1;
        found.push({
            at: match.index ?? 0,
            seconds: Math.round(amount * factor),
        });
    }

    for (const [words, seconds] of Object.entries(WORDS)) {
        const at = lower.indexOf(words);

        if (at !== -1) {
            found.push({ at, seconds });
        }
    }

    found.sort((a, b) => a.at - b.at);
    const result: Array<{ seconds: number; label: string }> = [];

    for (const { seconds } of found) {
        if (
            seconds < 10 ||
            seconds > MAX_SECONDS ||
            result.some((timer) => timer.seconds === seconds)
        ) {
            continue;
        }

        result.push({ seconds, label: label(seconds) });
    }

    return result.slice(0, 2);
}

/** 600 as "10 min", 5400 as "1 h 30 min", 45 as "45 sek". */
export function label(seconds: number): string {
    if (seconds < 60) {
        return `${seconds} sek`;
    }

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const rest = seconds % 60;

    return [
        hours > 0 ? `${hours} h` : '',
        minutes > 0 ? `${minutes} min` : '',
        rest > 0 ? `${rest} sek` : '',
    ]
        .filter(Boolean)
        .join(' ');
}
