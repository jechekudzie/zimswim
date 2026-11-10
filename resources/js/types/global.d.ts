import type { Auth } from '@/types/auth';
import type {
    Federation,
    NotificationFeed,
    OrganizationSummary,
} from '@/types/platform';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            federation: Federation;
            organization: OrganizationSummary | null;
            notifications: NotificationFeed | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
