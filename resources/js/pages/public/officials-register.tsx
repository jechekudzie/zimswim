import { Head, Link, router } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

import { FlagRule } from '@/components/flag-rule';
import { Section } from '@/components/public/section';
import { Badge } from '@/components/ui/badge';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import { officials as pathway } from '@/routes';
import officialRoutes from '@/routes/officials';

type Accreditation = { category: string; grade: string };

type Official = {
    registration_number: string;
    name: string;
    club: string;
    province: string | null;
    accreditations: Accreditation[];
};

type Choice = { value: string; label: string };

export default function OfficialsRegister({
    officials,
    filters,
    categories,
    grades,
    provinces,
}: {
    officials: Official[];
    filters: {
        category: string | null;
        grade: string | null;
        province: string | null;
    };
    categories: Choice[];
    grades: Choice[];
    provinces: Choice[];
}) {
    function apply(key: string, value: string) {
        const next: Record<string, string> = {
            ...(filters.category ? { category: filters.category } : {}),
            ...(filters.grade ? { grade: filters.grade } : {}),
            ...(filters.province ? { province: filters.province } : {}),
        };

        if (value) {
            next[key] = value;
        } else {
            delete next[key];
        }

        router.get(officialRoutes.register().url, next, {
            preserveScroll: true,
        });
    }

    const filtering =
        filters.category !== null ||
        filters.grade !== null ||
        filters.province !== null;

    return (
        <PublicLayout
            heroImage={photos.pages.officials}
            figures={[{ label: 'On the register', value: officials.length }]}
            title="The officials register"
            description="Every technical official accredited by Zimbabwe Swimming, with the categories they hold and the grade they hold them at."
        >
            <Head title="Officials register" />

            <Section>
                <div className="grid gap-4 sm:grid-cols-3">
                    <label className="block">
                        <span className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                            Category
                        </span>
                        <select
                            value={filters.category ?? ''}
                            onChange={(event) =>
                                apply('category', event.target.value)
                            }
                            className="mt-2 h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="">Every category</option>
                            {categories.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="block">
                        <span className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                            Grade
                        </span>
                        <select
                            value={filters.grade ?? ''}
                            onChange={(event) =>
                                apply('grade', event.target.value)
                            }
                            className="mt-2 h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="">Every grade</option>
                            {grades.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="block">
                        <span className="text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase">
                            Province
                        </span>
                        <select
                            value={filters.province ?? ''}
                            onChange={(event) =>
                                apply('province', event.target.value)
                            }
                            className="mt-2 h-10 w-full rounded-md border border-input bg-transparent px-3 text-sm focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                        >
                            <option value="">Every province</option>
                            {provinces.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>
                    </label>
                </div>

                {officials.length === 0 ? (
                    <p className="mt-10 rounded-lg border border-dashed border-border p-12 text-center text-muted-foreground">
                        {filtering
                            ? 'Nobody on the register holds that combination yet.'
                            : 'No accreditations have been ratified yet. The register fills as the technical committee ratifies them.'}
                    </p>
                ) : (
                    <ul className="mt-10 divide-y divide-border overflow-hidden rounded-xl border border-border">
                        {officials.map((official) => (
                            <li
                                key={official.registration_number}
                                className="bg-card p-5"
                            >
                                <div className="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1">
                                    <div className="flex items-center gap-2.5">
                                        <ShieldCheck className="size-4 shrink-0 text-primary" />
                                        <p className="font-semibold">
                                            {official.name}
                                        </p>
                                    </div>
                                    <p className="font-mono text-xs text-muted-foreground">
                                        {official.registration_number}
                                    </p>
                                </div>

                                <p className="mt-1 ml-[1.625rem] text-sm text-muted-foreground">
                                    {[official.club, official.province]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </p>

                                <div className="mt-3 ml-[1.625rem] flex flex-wrap gap-2">
                                    {official.accreditations.map(
                                        (accreditation) => (
                                            <Badge
                                                key={`${accreditation.category}-${accreditation.grade}`}
                                                variant="secondary"
                                                className="text-xs font-normal"
                                            >
                                                {accreditation.category}
                                                <span className="ml-1.5 font-semibold">
                                                    {accreditation.grade}
                                                </span>
                                            </Badge>
                                        ),
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <div className="mt-14">
                    <FlagRule />
                    <h2 className="mt-4 text-2xl font-bold tracking-tight">
                        What is on this register
                    </h2>
                    <p className="mt-3 max-w-2xl text-muted-foreground">
                        An accreditation appears once the technical committee
                        has ratified it, and drops off when it lapses. Officials
                        still working through a category are not listed — they
                        are learners until the exam is passed and the sessions
                        are signed off.
                    </p>
                    <p className="mt-3 max-w-2xl text-muted-foreground">
                        Contact details are not published here. Reach an
                        official through their club or their provincial board,
                        both of which are in the{' '}
                        <Link
                            href={pathway().url}
                            className="font-semibold text-primary hover:underline"
                        >
                            officials pathway
                        </Link>{' '}
                        pages.
                    </p>
                </div>
            </Section>
        </PublicLayout>
    );
}
