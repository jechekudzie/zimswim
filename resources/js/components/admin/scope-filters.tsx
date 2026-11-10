import { X } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export type ClubOption = {
    value: number;
    label: string;
    province_id: number | null;
};

export type ProvinceOption = { value: number; label: string };

/**
 * Narrowing a register by where somebody swims.
 *
 * Picking a province narrows the club list in the browser rather than
 * fetching it again — a federation has a few dozen clubs, not thousands, and
 * a round trip to filter a short list reads as lag. Choosing a club clears
 * any province that no longer contains it, so the two controls can never
 * describe an empty set between them.
 */
export function ScopeFilters({
    provinces,
    clubs,
    province,
    club,
    onChange,
    className,
}: {
    provinces: ProvinceOption[];
    clubs: ClubOption[];
    province: string | null;
    club: string | null;
    onChange: (next: {
        province?: string | null;
        club?: string | null;
    }) => void;
    className?: string;
}) {
    const inProvince =
        province === null || province === ''
            ? clubs
            : clubs.filter((item) => String(item.province_id) === province);

    const hasFilter = Boolean(province) || Boolean(club);

    const select =
        'h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

    return (
        <div className={cn('flex flex-wrap items-center gap-2', className)}>
            {provinces.length > 0 && (
                <select
                    aria-label="Filter by province"
                    className={select}
                    value={province ?? ''}
                    onChange={(event) => {
                        const next = event.target.value;
                        const keepsClub = clubs.some(
                            (item) =>
                                String(item.value) === club &&
                                (next === '' ||
                                    String(item.province_id) === next),
                        );

                        onChange({
                            province: next || null,
                            club: keepsClub ? club : null,
                        });
                    }}
                >
                    <option value="">All provinces</option>
                    {provinces.map((item) => (
                        <option key={item.value} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </select>
            )}

            {clubs.length > 0 && (
                <select
                    aria-label="Filter by club"
                    className={select}
                    value={club ?? ''}
                    onChange={(event) => {
                        const next = event.target.value;
                        const parent = clubs.find(
                            (item) => String(item.value) === next,
                        )?.province_id;

                        onChange({
                            club: next || null,
                            // Jumping straight to a club from "all
                            // provinces" should move the province with it,
                            // so the pair always reads as one statement.
                            province:
                                next === ''
                                    ? province
                                    : parent === null || parent === undefined
                                      ? null
                                      : String(parent),
                        });
                    }}
                >
                    <option value="">
                        {province ? 'All clubs in province' : 'All clubs'}
                    </option>
                    {inProvince.map((item) => (
                        <option key={item.value} value={item.value}>
                            {item.label}
                        </option>
                    ))}
                </select>
            )}

            {hasFilter && (
                <Button
                    size="sm"
                    variant="ghost"
                    onClick={() => onChange({ province: null, club: null })}
                >
                    <X className="size-3.5" />
                    Clear
                </Button>
            )}
        </div>
    );
}
