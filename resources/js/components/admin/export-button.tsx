import { Download } from 'lucide-react';

import { Button } from '@/components/ui/button';

/**
 * A spreadsheet of whatever the listing is currently showing.
 *
 * The filters in force are carried into the request, so the file matches the
 * page rather than the whole table - an export that quietly ignores a search
 * is worse than no export at all. It is a plain anchor, not an Inertia link,
 * because the response is a download rather than a page.
 */
export function ExportButton({
    url,
    params,
    label = 'Export CSV',
}: {
    url: string;
    params?: Record<string, string | number | null | undefined>;
    label?: string;
}) {
    const search = new URLSearchParams();

    for (const [key, value] of Object.entries(params ?? {})) {
        if (value !== null && value !== undefined && value !== '') {
            search.set(key, String(value));
        }
    }

    const query = search.toString();

    return (
        <Button asChild variant="outline" size="sm">
            <a href={query === '' ? url : `${url}?${query}`}>
                <Download className="size-4" />
                {label}
            </a>
        </Button>
    );
}
