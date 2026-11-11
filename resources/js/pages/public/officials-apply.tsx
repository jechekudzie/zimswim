import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Check, Lock } from 'lucide-react';
import { useMemo, useState } from 'react';

import InputError from '@/components/input-error';
import { Section } from '@/components/public/section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/public-layout';
import { cn } from '@/lib/utils';
import { photos } from '@/lib/photos';
import { officials } from '@/routes';
import officialsRoutes from '@/routes/officials';

type Category = {
    value: string;
    label: string;
    minimum_age: number;
    learner_sessions: number;
    has_exam: boolean;
    prerequisites: string[];
    is_entry: boolean;
};

type Province = {
    id: number;
    name: string;
    clubs: { id: number; name: string }[];
};

type Course = {
    title: string;
    category: string | null;
    lessons_count: number;
    exam_minutes: number;
    pass_mark: number;
    practical_sessions_required: number;
    exam_ready: boolean;
};

const steps = [
    {
        title: 'What you want to do',
        hint: 'The role you are applying to train in',
    },
    { title: 'About you', hint: 'So the committee knows who is asking' },
    { title: 'Your club', hint: 'Officials are registered through a club' },
    { title: 'Check and send', hint: 'One last read before it goes' },
];

/**
 * Whole years between a date of birth and today, read from the parts rather
 * than from a timestamp so a birthday is not off by one west of Greenwich.
 */
function ageFrom(iso: string): number | null {
    const [year, month, day] = iso.split('-').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    const today = new Date();
    let age = today.getFullYear() - year;
    const beforeBirthday =
        today.getMonth() + 1 < month ||
        (today.getMonth() + 1 === month && today.getDate() < day);

    if (beforeBirthday) {
        age -= 1;
    }

    return age;
}

/** Which step a server-side error belongs to, so the form can go back to it. */
const fieldStep: Record<string, number> = {
    category: 0,
    first_name: 1,
    last_name: 1,
    email: 1,
    phone: 1,
    date_of_birth: 1,
    province_organization_id: 2,
    organization_id: 2,
    club_name_given: 2,
    membership_number: 2,
    motivation: 3,
};

export default function OfficialsApply({
    categories,
    provinces,
    courses,
    preselected,
}: {
    categories: Category[];
    provinces: Province[];
    courses: Course[];
    preselected: string | null;
}) {
    // Arriving with the choice already made starts at the details instead,
    // rather than showing somebody a question they have just answered.
    const [step, setStep] = useState(preselected === null ? 0 : 1);

    const form = useForm({
        category: preselected ?? '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        province_organization_id: '',
        organization_id: '',
        club_name_given: '',
        membership_number: '',
        motivation: '',
    });

    const { data, setData, errors, processing } = form;

    const entry = categories.filter((category) => category.is_entry);
    const later = categories.filter((category) => !category.is_entry);

    const chosen = categories.find(
        (category) => category.value === data.category,
    );
    const course = courses.find((item) => item.category === data.category);
    const age = data.date_of_birth ? ageFrom(data.date_of_birth) : null;
    const tooYoung =
        chosen !== undefined && age !== null && age < chosen.minimum_age;

    const province = provinces.find(
        (item) => String(item.id) === data.province_organization_id,
    );

    const complete = useMemo(
        () => [
            data.category !== '',
            data.first_name !== '' &&
                data.last_name !== '' &&
                data.email !== '' &&
                data.phone !== '' &&
                data.date_of_birth !== '' &&
                !tooYoung,
            data.organization_id !== '' || data.club_name_given.trim() !== '',
            true,
        ],
        [data, tooYoung],
    );

    function submit() {
        // An untouched select sends an empty string, which `integer` would
        // reject; the column is nullable, so it is sent as one.
        form.transform((payload) => ({
            ...payload,
            organization_id: payload.organization_id || null,
            province_organization_id: payload.province_organization_id || null,
        }));

        form.post(officialsRoutes.apply.store().url, {
            onError: (failed: Record<string, string>) => {
                const first = Object.keys(failed)[0];
                const target = first ? fieldStep[first] : undefined;

                if (target !== undefined) {
                    setStep(target);
                }
            },
        });
    }

    return (
        <PublicLayout
            heroImage={photos.pages.officials}
            title="Apply to officiate"
            description="Four short steps. You do not need an account — one is opened for you if the technical committee takes your application forward."
        >
            <Head title="Apply to officiate" />

            <Section>
                <div className="grid gap-10 lg:grid-cols-[16rem_1fr] lg:gap-16">
                    {/*
                     * A wizard is one of the few places a numbered list earns
                     * its keep: the steps really are a sequence, and a reader
                     * part-way through needs to know how much is left.
                     */}
                    <ol className="flex gap-4 overflow-x-auto pb-2 lg:sticky lg:top-24 lg:h-fit lg:flex-col lg:overflow-visible lg:pb-0">
                        {steps.map((item, index) => {
                            const done = index < step && complete[index];
                            const current = index === step;

                            return (
                                <li
                                    key={item.title}
                                    className="flex min-w-[12rem] gap-3 lg:min-w-0"
                                >
                                    <span
                                        className={cn(
                                            'mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full border text-xs font-semibold tabular-nums',
                                            current &&
                                                'border-primary bg-primary text-primary-foreground',
                                            done &&
                                                !current &&
                                                'border-primary/40 bg-primary/10 text-primary',
                                            !current &&
                                                !done &&
                                                'border-border text-muted-foreground',
                                        )}
                                    >
                                        {done && !current ? (
                                            <Check className="size-3.5" />
                                        ) : (
                                            index + 1
                                        )}
                                    </span>
                                    <span className="min-w-0">
                                        <span
                                            className={cn(
                                                'block text-sm font-medium',
                                                !current &&
                                                    'text-muted-foreground',
                                            )}
                                        >
                                            {item.title}
                                        </span>
                                        <span className="mt-0.5 block text-xs text-muted-foreground">
                                            {item.hint}
                                        </span>
                                    </span>
                                </li>
                            );
                        })}
                    </ol>

                    <div className="min-w-0">
                        {step === 0 && (
                            <section>
                                <h2 className="text-2xl font-bold tracking-tight">
                                    Which role are you starting in?
                                </h2>
                                <p className="mt-2 max-w-2xl text-muted-foreground">
                                    These are the categories you can hold first.
                                    Everything else on the pathway is earned
                                    from one of them, so almost everybody begins
                                    with a stopwatch.
                                </p>

                                <div className="mt-7 grid gap-4 sm:grid-cols-2">
                                    {entry.map((category) => {
                                        const picked =
                                            data.category === category.value;
                                        const forCategory = courses.find(
                                            (item) =>
                                                item.category ===
                                                category.value,
                                        );

                                        return (
                                            <button
                                                key={category.value}
                                                type="button"
                                                onClick={() =>
                                                    setData(
                                                        'category',
                                                        category.value,
                                                    )
                                                }
                                                aria-pressed={picked}
                                                className={cn(
                                                    'rounded-xl border p-5 text-left transition-colors',
                                                    picked
                                                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                                        : 'border-border bg-card hover:border-primary/40',
                                                )}
                                            >
                                                <span className="flex items-start justify-between gap-3">
                                                    <span className="font-semibold">
                                                        {category.label}
                                                    </span>
                                                    {picked && (
                                                        <Check className="mt-0.5 size-4 shrink-0 text-primary" />
                                                    )}
                                                </span>
                                                <span className="mt-2 block text-sm text-muted-foreground">
                                                    From age{' '}
                                                    {category.minimum_age} ·{' '}
                                                    {category.learner_sessions}{' '}
                                                    learner sessions ·{' '}
                                                    {category.has_exam
                                                        ? 'written exam'
                                                        : 'no exam'}
                                                </span>
                                                {forCategory && (
                                                    <span className="mt-3 block text-xs text-muted-foreground">
                                                        Course:{' '}
                                                        {forCategory.title} ·{' '}
                                                        {
                                                            forCategory.lessons_count
                                                        }{' '}
                                                        lessons ·{' '}
                                                        {forCategory.pass_mark}%
                                                        to pass
                                                    </span>
                                                )}
                                            </button>
                                        );
                                    })}
                                </div>

                                <InputError
                                    message={errors.category}
                                    className="mt-3"
                                />

                                <div className="mt-8 rounded-xl border border-border bg-muted/40 p-5">
                                    <h3 className="flex items-center gap-2 text-sm font-semibold">
                                        <Lock className="size-4 text-muted-foreground" />
                                        Earned later
                                    </h3>
                                    <ul className="mt-3 space-y-1.5 text-sm text-muted-foreground">
                                        {later.map((category) => (
                                            <li key={category.value}>
                                                <span className="font-medium text-foreground">
                                                    {category.label}
                                                </span>{' '}
                                                — hold{' '}
                                                {category.prerequisites.join(
                                                    ' and ',
                                                )}{' '}
                                                first, from age{' '}
                                                {category.minimum_age}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </section>
                        )}

                        {step === 1 && (
                            <section>
                                <h2 className="text-2xl font-bold tracking-tight">
                                    About you
                                </h2>
                                <p className="mt-2 max-w-2xl text-muted-foreground">
                                    Your date of birth is here because every
                                    category has a minimum age, not for any
                                    other reason.
                                </p>

                                <div className="mt-7 grid gap-5 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="first_name">
                                            First name
                                        </Label>
                                        <Input
                                            id="first_name"
                                            value={data.first_name}
                                            onChange={(event) =>
                                                setData(
                                                    'first_name',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="given-name"
                                        />
                                        <InputError
                                            message={errors.first_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="last_name">
                                            Last name
                                        </Label>
                                        <Input
                                            id="last_name"
                                            value={data.last_name}
                                            onChange={(event) =>
                                                setData(
                                                    'last_name',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="family-name"
                                        />
                                        <InputError
                                            message={errors.last_name}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(event) =>
                                                setData(
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="email"
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            This becomes your sign-in if you are
                                            approved.
                                        </p>
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="phone">Phone</Label>
                                        <Input
                                            id="phone"
                                            value={data.phone}
                                            onChange={(event) =>
                                                setData(
                                                    'phone',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="+263 77 000 0000"
                                            autoComplete="tel"
                                        />
                                        <InputError message={errors.phone} />
                                    </div>

                                    <div className="grid gap-2 sm:col-span-2 sm:max-w-xs">
                                        <Label htmlFor="date_of_birth">
                                            Date of birth
                                        </Label>
                                        <Input
                                            id="date_of_birth"
                                            type="date"
                                            value={data.date_of_birth}
                                            onChange={(event) =>
                                                setData(
                                                    'date_of_birth',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        {chosen && age !== null && (
                                            <p
                                                className={cn(
                                                    'text-xs',
                                                    tooYoung
                                                        ? 'font-medium text-brand-red'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {tooYoung
                                                    ? `A ${chosen.label.toLowerCase()} must be at least ${chosen.minimum_age}. You are welcome to apply once you are.`
                                                    : `${age} — old enough for ${chosen.label.toLowerCase()}.`}
                                            </p>
                                        )}
                                        <InputError
                                            message={errors.date_of_birth}
                                        />
                                    </div>
                                </div>
                            </section>
                        )}

                        {step === 2 && (
                            <section>
                                <h2 className="text-2xl font-bold tracking-tight">
                                    Your club
                                </h2>
                                <p className="mt-2 max-w-2xl text-muted-foreground">
                                    Every technical official is registered
                                    through an affiliated club in the province
                                    they live in. If you are not a member yet,
                                    say so and the provincial convenor will put
                                    you in touch with one.
                                </p>

                                <div className="mt-7 grid gap-5 sm:max-w-md">
                                    <div className="grid gap-2">
                                        <Label htmlFor="province_organization_id">
                                            Province
                                        </Label>
                                        <select
                                            id="province_organization_id"
                                            value={
                                                data.province_organization_id
                                            }
                                            onChange={(event) => {
                                                setData((previous) => ({
                                                    ...previous,
                                                    province_organization_id:
                                                        event.target.value,
                                                    organization_id: '',
                                                }));
                                            }}
                                            className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        >
                                            <option value="">
                                                Choose a province
                                            </option>
                                            {provinces.map((item) => (
                                                <option
                                                    key={item.id}
                                                    value={item.id}
                                                >
                                                    {item.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={
                                                errors.province_organization_id
                                            }
                                        />
                                    </div>

                                    {province && province.clubs.length > 0 && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="organization_id">
                                                Club
                                            </Label>
                                            <select
                                                id="organization_id"
                                                value={data.organization_id}
                                                onChange={(event) =>
                                                    setData(
                                                        'organization_id',
                                                        event.target.value,
                                                    )
                                                }
                                                className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                <option value="">
                                                    My club is not listed
                                                </option>
                                                {province.clubs.map((club) => (
                                                    <option
                                                        key={club.id}
                                                        value={club.id}
                                                    >
                                                        {club.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.organization_id}
                                            />
                                        </div>
                                    )}

                                    {province &&
                                        province.clubs.length === 0 && (
                                            <p className="text-sm text-muted-foreground">
                                                No club in {province.name} is on
                                                the register yet. Tell us where
                                                you swim and the convenor will
                                                take it from there.
                                            </p>
                                        )}

                                    {data.organization_id === '' && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="club_name_given">
                                                Where do you swim?
                                            </Label>
                                            <Input
                                                id="club_name_given"
                                                value={data.club_name_given}
                                                onChange={(event) =>
                                                    setData(
                                                        'club_name_given',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Club, school or “not a member yet”"
                                            />
                                            <InputError
                                                message={errors.club_name_given}
                                            />
                                        </div>
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor="membership_number">
                                            ZimSwim number{' '}
                                            <span className="font-normal text-muted-foreground">
                                                (if you have one)
                                            </span>
                                        </Label>
                                        <Input
                                            id="membership_number"
                                            value={data.membership_number}
                                            onChange={(event) =>
                                                setData(
                                                    'membership_number',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                        <InputError
                                            message={errors.membership_number}
                                        />
                                    </div>
                                </div>
                            </section>
                        )}

                        {step === 3 && (
                            <section>
                                <h2 className="text-2xl font-bold tracking-tight">
                                    Check and send
                                </h2>

                                <dl className="mt-7 divide-y divide-border overflow-hidden rounded-xl border border-border">
                                    {[
                                        ['Role', chosen?.label ?? '—'],
                                        [
                                            'Name',
                                            `${data.first_name} ${data.last_name}`.trim() ||
                                                '—',
                                        ],
                                        ['Email', data.email || '—'],
                                        ['Phone', data.phone || '—'],
                                        [
                                            'Date of birth',
                                            data.date_of_birth || '—',
                                        ],
                                        [
                                            'Club',
                                            province?.clubs.find(
                                                (club) =>
                                                    String(club.id) ===
                                                    data.organization_id,
                                            )?.name ||
                                                data.club_name_given ||
                                                '—',
                                        ],
                                        ['Province', province?.name ?? '—'],
                                    ].map(([label, value]) => (
                                        <div
                                            key={label}
                                            className="flex gap-4 bg-card px-5 py-3 text-sm"
                                        >
                                            <dt className="w-36 shrink-0 text-muted-foreground">
                                                {label}
                                            </dt>
                                            <dd className="min-w-0 font-medium">
                                                {value}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>

                                <div className="mt-6 grid gap-2">
                                    <Label htmlFor="motivation">
                                        Anything the committee should know?{' '}
                                        <span className="font-normal text-muted-foreground">
                                            (optional)
                                        </span>
                                    </Label>
                                    <textarea
                                        id="motivation"
                                        rows={4}
                                        maxLength={1000}
                                        value={data.motivation}
                                        onChange={(event) =>
                                            setData(
                                                'motivation',
                                                event.target.value,
                                            )
                                        }
                                        placeholder="Galas you already help at, a club that has asked you, time you can give."
                                        className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    />
                                    <InputError message={errors.motivation} />
                                </div>

                                {course && !course.exam_ready && (
                                    <p className="mt-5 text-sm text-muted-foreground">
                                        The written exam for{' '}
                                        {chosen?.label.toLowerCase()} is still
                                        being set. You can start the lessons and
                                        your poolside sessions as soon as you
                                        are approved.
                                    </p>
                                )}

                                <p className="mt-5 text-sm text-muted-foreground">
                                    We will email{' '}
                                    <span className="font-medium text-foreground">
                                        {data.email || 'you'}
                                    </span>{' '}
                                    with a reference, and again once the
                                    committee has looked at it.
                                </p>
                            </section>
                        )}

                        <div className="mt-10 flex flex-wrap items-center gap-3 border-t border-border pt-6">
                            {step > 0 && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setStep(step - 1)}
                                >
                                    <ArrowLeft className="size-4" />
                                    Back
                                </Button>
                            )}

                            {step < steps.length - 1 ? (
                                <Button
                                    type="button"
                                    disabled={!complete[step]}
                                    onClick={() => setStep(step + 1)}
                                >
                                    Continue
                                    <ArrowRight className="size-4" />
                                </Button>
                            ) : (
                                <Button
                                    type="button"
                                    disabled={
                                        processing ||
                                        complete.slice(0, 3).includes(false)
                                    }
                                    onClick={submit}
                                >
                                    {processing
                                        ? 'Sending…'
                                        : 'Send my application'}
                                </Button>
                            )}

                            {step === 0 && (
                                <Button asChild variant="ghost">
                                    <a href={officials().url}>
                                        Read the pathway first
                                    </a>
                                </Button>
                            )}
                        </div>

                        {chosen && (
                            <Badge variant="secondary" className="mt-6">
                                Applying as {chosen.label}
                            </Badge>
                        )}
                    </div>
                </div>
            </Section>
        </PublicLayout>
    );
}
