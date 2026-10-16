import type { InjectionKey, Ref } from 'vue';
import type { Density } from '@/types';

/** The display's density, for widgets that space themselves after it. */
export const DISPLAY_DENSITY: InjectionKey<Ref<Density>> =
    Symbol('displayDensity');
