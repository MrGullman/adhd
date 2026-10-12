import type { Directive } from 'vue';
import type { Auth, CurrentFamily, FamilyOption } from '@/types/auth';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            family: CurrentFamily | null;
            families: FamilyOption[];
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: {
                type: 'success' | 'info' | 'warning' | 'error';
                message: string;
            };
            pairing?: { url: string; qr: string; expiresAt: string };
            /** A home display's link, shown once. */
            displayLink?: string;
        };
    }
}

declare module 'vue' {
    interface GlobalDirectives {
        vFocus: Directive<HTMLElement, boolean | undefined>;
    }

    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
