import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Clock,
    Mail,
    MapPin,
    Monitor,
    Phone,
    Users,
} from 'lucide-react';

import InputError from '@/components/input-error';
import { Prose } from '@/components/prose';
import { FlagRule } from '@/components/flag-rule';
import { Section } from '@/components/public/section';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PublicLayout from '@/layouts/public-layout';
import { photos } from '@/lib/photos';
import officialRoutes from '@/routes/officials';
import {
    clockTime,
    held,
    type TrainingSummary,
} from '@/pages/public/officials-training';

type Training = TrainingSummary & {
    description: string | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    venue_address: string | null;
    kind_description: string;
};

export default function OfficialsTrainingShow({
    training,
}: {
    training: Training;
}) {
    const flash = usePage().props.flash as { success?: string } | undefined;

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        club_name: '',
        notes: '',
    });

    const { data, setData, errors, processing } = form;

    const start = clockTime(training.starts_at);
    const finish = clockTime(training.ends_at);

    return (
        <PublicLayout
            heroImage={photos.pages.officials}
            title={training.title}
            description={`${training.kind_label} run by ${training.host}.`}
        >
            <Head title={training.title} />

            <Section>
                <Link
                    href={officialRoutes.training().url}
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-primary"
                >
                    <ArrowLeft className="size-3.5" />
                    All clinics and exams
                </Link>

                <div className="mt-8 grid gap-10 lg:grid-cols-[1.15fr_1fr]">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">
                                {training.kind_label}
                            </Badge>
                            {training.category && (
                                <Badge
                                    variant="outline"
                                    className="font-normal"
                                >
                                    {training.category}
                                </Badge>
                            )}
                        </div>

                        <dl className="mt-6 space-y-4 rounded-xl border border-border bg-card p-6">
                            <div className="flex items-start gap-3">
                                <CalendarDays className="mt-0.5 size-4 shrink-0 text-primary" />
                                <div>
                                    <dt className="font-semibold">
                                        {held(training)}
                                    </dt>
                                    {start && (
                                        <dd className="text-sm text-muted-foreground">
                                            {finish
                                                ? `${start} to ${finish}`
                                                : `From ${start}`}
                                        </dd>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                {training.is_online ? (
                                    <Monitor className="mt-0.5 size-4 shrink-0 text-primary" />
                                ) : (
                                    <MapPin className="mt-0.5 size-4 shrink-0 text-primary" />
                                )}
                                <div>
                                    <dt className="font-semibold">
                                        {training.is_online
                                            ? 'Online'
                                            : (training.venue ??
                                              training.city ??
                                              'Venue to be confirmed')}
                                    </dt>
                                    {!training.is_online &&
                                        training.venue_address && (
                                            <dd className="text-sm text-muted-foreground">
                                                {training.venue_address}
                                            </dd>
                                        )}
                                </div>
                            </div>

                            {training.registration_closes_at && (
                                <div className="flex items-start gap-3">
                                    <Clock className="mt-0.5 size-4 shrink-0 text-primary" />
                                    <div>
                                        <dt className="font-semibold">
                                            Registration closes
                                        </dt>
                                        <dd className="text-sm text-muted-foreground">
                                            {new Date(
                                                `${training.registration_closes_at}T00:00:00`,
                                            ).toLocaleDateString('en-GB', {
                                                day: 'numeric',
                                                month: 'long',
                                                year: 'numeric',
                                            })}
                                        </dd>
                                    </div>
                                </div>
                            )}

                            <div className="flex items-start gap-3">
                                <Users className="mt-0.5 size-4 shrink-0 text-primary" />
                                <div>
                                    <dt className="font-semibold">
                                        {training.attending} registered
                                    </dt>
                                    <dd className="text-sm text-muted-foreground">
                                        {training.places_left === null
                                            ? 'No limit on numbers.'
                                            : `${training.places_left} places left.`}
                                    </dd>
                                </div>
                            </div>
                        </dl>

                        {training.description && (
                            <Prose
                                className="mt-6 text-muted-foreground"
                                html={training.description}
                            />
                        )}

                        <div className="mt-8">
                            <FlagRule />
                            <h2 className="mt-4 text-xl font-bold tracking-tight">
                                {training.kind_label}
                            </h2>
                            <p className="mt-2 text-muted-foreground">
                                {training.kind_description}
                            </p>
                        </div>

                        {(training.contact_name ||
                            training.contact_email ||
                            training.contact_phone) && (
                            <div className="mt-8 rounded-xl border border-border bg-card p-6">
                                <h2 className="font-semibold">
                                    Questions about this session
                                </h2>
                                {training.contact_name && (
                                    <p className="mt-2 text-sm">
                                        {training.contact_name}
                                    </p>
                                )}
                                <ul className="mt-2 space-y-1.5 text-sm">
                                    {training.contact_email && (
                                        <li className="flex items-center gap-2">
                                            <Mail className="size-3.5 text-primary" />
                                            <a
                                                className="break-all hover:text-primary"
                                                href={`mailto:${training.contact_email}`}
                                            >
                                                {training.contact_email}
                                            </a>
                                        </li>
                                    )}
                                    {training.contact_phone && (
                                        <li className="flex items-center gap-2">
                                            <Phone className="size-3.5 text-primary" />
                                            {training.contact_phone}
                                        </li>
                                    )}
                                </ul>
                            </div>
                        )}
                    </div>

                    <div>
                        <div className="rounded-xl border border-border bg-card p-6">
                            {flash?.success ? (
                                <div className="flex items-start gap-3">
                                    <CheckCircle2 className="mt-0.5 size-5 shrink-0 text-primary" />
                                    <div>
                                        <h2 className="font-semibold">
                                            You are on the list
                                        </h2>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {flash.success}
                                        </p>
                                    </div>
                                </div>
                            ) : training.has_finished ? (
                                <>
                                    <h2 className="font-semibold">
                                        This session has been held
                                    </h2>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        The same clinic is usually run again.
                                        Ask {training.host} when the next one
                                        is, or watch this page.
                                    </p>
                                </>
                            ) : !training.is_open ? (
                                <>
                                    <h2 className="font-semibold">
                                        Registration is closed
                                    </h2>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {training.places_left === 0
                                            ? 'Every place has been taken. Contact the host — they may run another.'
                                            : 'The closing date for this session has passed. Contact the host.'}
                                    </p>
                                </>
                            ) : (
                                <>
                                    <h2 className="font-semibold">
                                        Put your name down
                                    </h2>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        Open to anybody. You do not need an
                                        account, a club, or an existing
                                        accreditation to attend a clinic.
                                    </p>

                                    <form
                                        className="mt-6 grid gap-4"
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            form.post(
                                                officialRoutes.training.register(
                                                    training.slug,
                                                ).url,
                                                {
                                                    preserveScroll: true,
                                                    onSuccess: () =>
                                                        form.reset(),
                                                },
                                            );
                                        }}
                                    >
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                Your name
                                            </Label>
                                            <Input
                                                id="name"
                                                value={data.name}
                                                onChange={(event) =>
                                                    setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                autoComplete="name"
                                            />
                                            <InputError message={errors.name} />
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
                                            <InputError
                                                message={errors.email}
                                            />
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
                                            <InputError
                                                message={errors.phone}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="club_name">
                                                Club, if you have one
                                            </Label>
                                            <Input
                                                id="club_name"
                                                value={data.club_name}
                                                onChange={(event) =>
                                                    setData(
                                                        'club_name',
                                                        event.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={errors.club_name}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="notes">
                                                Anything the host should know
                                            </Label>
                                            <textarea
                                                id="notes"
                                                rows={3}
                                                value={data.notes}
                                                onChange={(event) =>
                                                    setData(
                                                        'notes',
                                                        event.target.value,
                                                    )
                                                }
                                                className="rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                            />
                                            <InputError
                                                message={errors.notes}
                                            />
                                        </div>

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? 'Sending'
                                                : 'Register'}
                                        </Button>
                                    </form>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </Section>
        </PublicLayout>
    );
}
