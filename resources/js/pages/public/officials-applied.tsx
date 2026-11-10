import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, Mail } from 'lucide-react';

import { Section } from '@/components/public/section';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/public-layout';
import { officials } from '@/routes';
import officialsRoutes from '@/routes/officials';

export default function OfficialsApplied({
    application,
}: {
    application: {
        reference: string;
        category: string;
        email: string;
    } | null;
}) {
    return (
        <PublicLayout>
            <Head title="Application sent" />

            <Section innerClassName="py-20 lg:py-28">
                <div className="mx-auto max-w-xl text-center">
                    {application === null ? (
                        <>
                            <h1 className="text-3xl font-bold tracking-tight">
                                Nothing to show here
                            </h1>
                            <p className="mt-3 text-muted-foreground">
                                This page confirms an application the moment it
                                is sent, so there is nothing on it if you have
                                come back later. Check your email for your
                                reference.
                            </p>
                            <Button asChild className="mt-8">
                                <Link href={officialsRoutes.apply()}>
                                    Start an application
                                </Link>
                            </Button>
                        </>
                    ) : (
                        <>
                            <CheckCircle2
                                className="mx-auto size-10 text-brand-green"
                                aria-hidden="true"
                            />
                            <h1 className="mt-6 text-3xl font-bold tracking-tight text-balance">
                                That is with the technical committee
                            </h1>
                            <p className="mt-4 text-muted-foreground">
                                Your application to train as a{' '}
                                {application.category.toLowerCase()} has been
                                received.
                            </p>

                            <p className="mt-8 text-sm text-muted-foreground">
                                Your reference
                            </p>
                            <p className="mt-1 font-mono text-2xl font-bold tracking-tight tabular-nums">
                                {application.reference}
                            </p>

                            <div className="mt-10 rounded-xl border border-border bg-card p-6 text-left">
                                <h2 className="flex items-center gap-2 font-semibold">
                                    <Mail className="size-4 text-primary" />
                                    What happens next
                                </h2>
                                <ol className="mt-4 space-y-3 text-sm text-muted-foreground">
                                    <li>
                                        A confirmation is on its way to{' '}
                                        <span className="font-medium text-foreground">
                                            {application.email}
                                        </span>
                                        .
                                    </li>
                                    <li>
                                        The committee checks your age, your club
                                        and the category you asked for.
                                    </li>
                                    <li>
                                        If it is approved you get a link to set
                                        a password, and the course for your
                                        category is already waiting in the
                                        Academy.
                                    </li>
                                </ol>
                            </div>

                            <Button asChild variant="outline" className="mt-8">
                                <Link href={officials()}>
                                    Back to the pathway
                                </Link>
                            </Button>
                        </>
                    )}
                </div>
            </Section>
        </PublicLayout>
    );
}
