import { Head } from '@inertiajs/react';
import { BadgeCheck, ShieldAlert, ShieldX } from 'lucide-react';

import BrandMark from '@/components/brand-mark';
import PublicLayout from '@/layouts/public-layout';

export default function CertificateVerification({
    certificate,
}: {
    certificate: {
        code: string;
        holder_name: string;
        title: string;
        grade: string | null;
        issued_on: string;
        expires_on: string | null;
        issued_by: string | null;
        status: string;
        valid: boolean;
        expired: boolean;
    };
}) {
    const Icon = certificate.valid
        ? BadgeCheck
        : certificate.expired
          ? ShieldAlert
          : ShieldX;

    return (
        <PublicLayout>
            <Head title={`Certificate ${certificate.code}`} />

            <div className="mx-auto w-full max-w-2xl px-4 py-16 sm:px-6 lg:px-8">
                <div
                    className={`overflow-hidden rounded-2xl border ${
                        certificate.valid
                            ? 'border-brand-green/40'
                            : 'border-brand-red/40'
                    }`}
                >
                    <div
                        className={`flex items-center gap-4 px-8 py-6 ${
                            certificate.valid
                                ? 'bg-brand-green/10'
                                : 'bg-brand-red/10'
                        }`}
                    >
                        <Icon
                            className={`size-10 shrink-0 ${certificate.valid ? 'text-brand-green' : 'text-brand-red'}`}
                        />
                        <div>
                            <p className="text-lg font-bold">
                                {certificate.valid
                                    ? 'Valid certificate'
                                    : certificate.expired
                                      ? 'Certificate expired'
                                      : 'Certificate not valid'}
                            </p>
                            <p className="font-mono text-sm text-muted-foreground">
                                {certificate.code}
                            </p>
                        </div>
                    </div>

                    <div className="bg-card px-8 py-8">
                        <dl className="space-y-5">
                            <div>
                                <dt className="text-xs tracking-[0.14em] text-muted-foreground uppercase">
                                    Awarded to
                                </dt>
                                <dd className="mt-1 text-2xl font-bold tracking-tight">
                                    {certificate.holder_name}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs tracking-[0.14em] text-muted-foreground uppercase">
                                    Qualification
                                </dt>
                                <dd className="mt-1 font-semibold">
                                    {certificate.title}
                                    {certificate.grade
                                        ? ` · ${certificate.grade}`
                                        : ''}
                                </dd>
                            </div>
                            <div className="grid grid-cols-2 gap-5">
                                <div>
                                    <dt className="text-xs tracking-[0.14em] text-muted-foreground uppercase">
                                        Issued
                                    </dt>
                                    <dd className="mt-1 font-medium tabular-nums">
                                        {certificate.issued_on}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-xs tracking-[0.14em] text-muted-foreground uppercase">
                                        Expires
                                    </dt>
                                    <dd className="mt-1 font-medium tabular-nums">
                                        {certificate.expires_on ?? '—'}
                                    </dd>
                                </div>
                            </div>
                        </dl>

                        <div className="mt-8 flex items-center gap-3 border-t border-border pt-6">
                            <BrandMark className="size-8 text-primary" />
                            <p className="text-sm text-muted-foreground">
                                Issued by{' '}
                                {certificate.issued_by ?? 'Zimbabwe Swimming'}.
                                Accreditation lapses if an official does not
                                work at their highest level for two years.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </PublicLayout>
    );
}
