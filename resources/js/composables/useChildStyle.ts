import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { ComputedRef } from 'vue';

export type ChildStyle = {
    /** Läget Ungdom: calmer and more grown-up (docs/STYLEGUIDE.md). */
    youth: boolean;
    /** Bildstöd is shown. Parents can turn it off in läget Ungdom. */
    pictures: boolean;
    /** Text the child reads: tasks, steps and labels. */
    text: string;
    /** Height of buttons and rows the child taps. */
    tap: string;
    /** Icons next to words. */
    icon: string;
    /** Bildstöd in lists. */
    picture: 'xs' | 'md';
    /** Tick boxes. */
    toggle: 'adult' | 'child';
    /** A rounded link or button that is not the primary one. */
    pill: string;
};

/**
 * Sizes for the child's own views in the child's mode, Barn or Ungdom.
 */
export function useChildStyle(): ComputedRef<ChildStyle> {
    const page = usePage();

    return computed(() => {
        const youth = page.props.childView?.youth ?? false;
        const pictures = page.props.childView?.pictures ?? true;

        return youth
            ? {
                  youth,
                  pictures,
                  text: 'text-youth-label',
                  tap: 'min-h-tap-adult',
                  icon: 'size-5',
                  picture: 'xs',
                  toggle: 'adult',
                  pill: 'flex min-h-tap-adult items-center gap-2 rounded-full border border-line bg-surface-raised px-4 font-bold',
              }
            : {
                  youth,
                  pictures,
                  text: 'font-display text-child-label',
                  tap: 'h-tap-child',
                  icon: 'size-8',
                  picture: 'md',
                  toggle: 'child',
                  pill: 'flex h-tap-child items-center gap-3 rounded-full border-2 border-line bg-surface-raised px-8 font-display text-child-label font-semibold',
              };
    });
}
