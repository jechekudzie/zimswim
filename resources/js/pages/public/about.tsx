import { Head } from '@inertiajs/react';

import {
    CommunicationChain,
    Organogram,
    type ChainLink,
} from '@/components/public/org-chart';
import { Section, SectionHeading } from '@/components/public/section';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';

type Person = { name: string; role: string; email?: string | null };

type Committee = {
    name: string;
    kind: string;
    members: Person[];
};

type Province = {
    name: string;
    slug: string;
    city: string | null;
    officers: Person[];
};

const chain: ChainLink[] = [
    {
        scope: 'World',
        name: 'World Aquatics',
        note: 'Writes the rules the sport is swum under, everywhere.',
    },
    {
        scope: 'Continent',
        name: 'Africa Aquatics',
        note: 'The continental federation, and the route to African championships.',
    },
    {
        scope: 'Region',
        name: 'Africa Aquatics Zone IV',
        note: 'Southern Africa, and the zonal competitions Zimbabwe enters.',
    },
    {
        scope: 'Zimbabwe',
        name: 'Zimbabwe Aquatics',
        note: 'The national federation for every aquatic discipline, swimming among them.',
    },
    {
        scope: 'The sport',
        name: 'Zimbabwe Swimming',
        note: 'The national governing body for swimming: the calendar, the records, the officials.',
    },
    {
        scope: 'Province',
        name: 'Provinces',
        note: 'Eleven provincial bodies. A club affiliates here, not directly to the national body.',
    },
    {
        scope: 'Club',
        name: 'Clubs',
        note: 'Affiliated clubs, their coaches and their committees. Entries are lodged here.',
    },
    {
        scope: 'You',
        name: 'Swimmers and families',
        note: 'Start with your coach or club secretary. They carry it up the line.',
    },
];

export default function About({
    national,
    committees,
    provinces,
}: {
    national: {
        name: string;
        description: string | null;
        email: string | null;
    };
    committees: Committee[];
    provinces: Province[];
}) {
    const executives = committees.filter((c) => c.kind === 'executive');
    const standing = committees.filter((c) => c.kind !== 'executive');

    return (
        <PublicLayout
            heroImage={photos.pages.about}
            figures={[
                { label: 'Provinces', value: provinces.length },
                { label: 'Standing committees', value: committees.length },
            ]}
            title="People, structure, communication"
            description={
                national.description ??
                'Clear and respectful communication helps us support our swimmers at every level.'
            }
        >
            <Head title="About" />

            <Section>
                <SectionHeading
                    title="Lines of communication"
                    lead="Swimming runs on a chain, and a question answered at the wrong level is a question that waits. This is where yours belongs, and where it goes next."
                />

                <div className="max-w-4xl">
                    <CommunicationChain links={chain} />
                </div>
            </Section>

            {executives.length > 0 && (
                <Section tone="surface">
                    <SectionHeading
                        title="Zimbabwe Swimming executive"
                        lead="The board elected to run the sport nationally. A post shown as vacant is one the federation is still filling."
                    />

                    <div className="mt-10 space-y-8">
                        {executives.map((committee) => (
                            <Organogram
                                key={committee.name}
                                title={committee.name}
                                officers={committee.members}
                            />
                        ))}
                    </div>
                </Section>
            )}

            {standing.length > 0 && (
                <Section>
                    <SectionHeading
                        title="Standing committees"
                        lead="The committees that run each part of the sport, and who chairs them."
                    />

                    <div>
                        <div className="mt-10 overflow-hidden rounded-xl border border-border">
                            <table className="w-full text-sm">
                                <thead className="border-b border-border bg-brand-surface/60 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-5 py-3 font-medium">
                                            Committee
                                        </th>
                                        <th className="px-5 py-3 font-medium">
                                            Chairperson
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {standing.map((committee) => (
                                        <tr
                                            key={committee.name}
                                            className="border-b border-border/60 bg-card last:border-0"
                                        >
                                            <td className="px-5 py-3 font-medium">
                                                {committee.name}
                                            </td>
                                            <td className="px-5 py-3 text-muted-foreground">
                                                {committee.members
                                                    .map((m) => m.name)
                                                    .join(', ') || '—'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </Section>
            )}

            <Section tone="surface">
                <SectionHeading
                    title="Provincial executive boards"
                    lead="Your province is the body your club affiliates through, and the first place a club matter goes."
                />

                <div>
                    <div className="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        {provinces.map((province) => (
                            <div
                                key={province.slug}
                                className="rounded-xl border border-border bg-card p-6"
                            >
                                <h3 className="font-semibold text-balance">
                                    {province.name}
                                </h3>
                                {province.city && (
                                    <p className="text-sm text-muted-foreground">
                                        {province.city}
                                    </p>
                                )}
                                {province.officers.length === 0 ? (
                                    <p className="mt-4 text-sm text-muted-foreground">
                                        Board details to be confirmed.
                                    </p>
                                ) : (
                                    <ul className="mt-4 space-y-2 text-sm">
                                        {province.officers.map((officer) => (
                                            <li
                                                key={officer.name}
                                                className="flex flex-wrap items-baseline justify-between gap-2"
                                            >
                                                <span>{officer.name}</span>
                                                <span className="text-xs text-muted-foreground">
                                                    {officer.role}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </Section>

            {national.email && (
                <Section tone="ink">
                    <div className="max-w-2xl">
                        <SectionHeading
                            title="Get in touch"
                            lead="Follow the correct channel: individual queries go to your club, club matters to your province, and provincial matters to Zimbabwe Swimming."
                        />
                        <a
                            href={`mailto:${national.email}`}
                            className="mt-7 inline-block text-lg font-semibold text-brand-gold hover:underline"
                        >
                            {national.email}
                        </a>
                    </div>
                </Section>
            )}
        </PublicLayout>
    );
}
