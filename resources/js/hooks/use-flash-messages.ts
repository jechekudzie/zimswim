import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

import type { Flash } from '@/types';

/**
 * Surfaces the success and error messages redirects set on the session.
 */
export function useFlashMessages(): void {
    const { flash } = usePage<{ flash: Flash | null }>().props;
    const previous = useRef<string | null>(null);

    useEffect(() => {
        const message = flash?.success ?? flash?.error ?? null;

        if (!message || message === previous.current) {
            previous.current = message;
            return;
        }

        previous.current = message;

        if (flash?.success) {
            toast.success(flash.success);
            return;
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);
}
