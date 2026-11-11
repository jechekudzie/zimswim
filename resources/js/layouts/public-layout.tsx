import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import {
    type PropsWithChildren,
    type ReactNode,
    useEffect,
    useState,
} from 'react';

import { BrandLogo } from '@/components/brand-logo';
import { FlagRule } from '@/components/flag-rule';
import { PageHero, type HeroFigure } from '@/components/public/page-hero';
import { WhatsAppButton } from '@/components/public/whatsapp-button';
import { Button } from '@/components/ui/button';
import type { Photo } from '@/lib/photos';
import {
    about,
    calendar,
    clubs,
    dashboard,
    home,
    login,
    officials,
    records,
    venues,
} from '@/routes';
import news from '@/routes/news';
import results from '@/routes/results';
import type { Auth } from '@/types';

const navigation = [
    { title: 'Home', href: home() },
    { title: 'About', href: about() },
    { title: 'Calendar', href: calendar() },
    { title: 'Clubs', href: clubs() },
    { title: 'Venues', href: venues() },
    { title: 'Results', href: results.index() },
    { title: 'Records', href: records() },
    { title: 'Officials Academy', href: officials() },
    { title: 'News', href: news.index() },
];

export default function PublicLayout({
    children,
    title,
    description,
    figures,
    heroActions,
    heroImage,
    heroSize,
    /**
     * Set by a page that draws its own full-bleed opening band, so the
     * navigation floats over it rather than sitting on a bar above it.
     */
    overHero = false,
}: PropsWithChildren<{
    title?: string;
    description?: string;
    figures?: HeroFigure[];
    heroActions?: ReactNode;
    heroImage?: Photo;
    heroSize?: 'page' | 'tall';
    overHero?: boolean;
}>) {
    const page = usePage<{ auth: Auth | null }>();
    const { auth } = page.props;
    const [open, setOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    const hasHero = Boolean(title);
    const floating = hasHero || overHero;

    /*
     * Over a hero the bar is nothing but the logo and the links, so the
     * photograph runs to the top of the window. It takes its ground back as
     * soon as the hero has gone by, because white links on a white page
     * would be no links at all.
     */
    const solid = !open && (!floating || scrolled);

    useEffect(() => {
        if (!floating) {
            return;
        }

        const onScroll = () => setScrolled(window.scrollY > 64);

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, [floating]);

    /**
     * The nav marks where you are. Home matches only itself; every other
     * entry also matches the pages beneath it, so a gala keeps Calendar lit.
     */
    function isCurrent(href: { url: string } | string): boolean {
        const path = typeof href === 'string' ? href : href.url;
        const current = page.url.split('?')[0];

        return path === '/' ? current === '/' : current.startsWith(path);
    }

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <header
                className={`fixed inset-x-0 top-0 z-50 transition-[background-color,border-color,box-shadow] duration-300 motion-reduce:transition-none ${
                    open
                        ? 'on-dark bottom-0 overflow-y-auto border-b border-transparent bg-brand-deeper lg:bottom-auto'
                        : solid
                          ? 'border-b border-border/60 bg-background/90 shadow-sm backdrop-blur-lg'
                          : 'border-b border-transparent bg-transparent'
                }`}
            >
                {/*
                 * A wash under the bar while it floats. A photograph can be
                 * bright sky behind one link and dark water behind the next,
                 * and white text alone does not survive that.
                 */}
                {!solid && !open && (
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-x-0 top-0 -z-10 h-32 bg-gradient-to-b from-black/55 via-black/25 to-transparent"
                    />
                )}
                <div className="mx-auto flex h-20 w-full max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
                    <Link
                        href={home()}
                        className="shrink-0 rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:outline-none"
                        aria-label="Zimbabwe Swimming home"
                    >
                        <BrandLogo
                            tone={solid ? 'dark' : 'light'}
                            className="h-11 sm:h-14"
                        />
                    </Link>

                    <nav className="ml-auto hidden items-center gap-0.5 lg:flex">
                        {navigation.map((item) => (
                            <Link
                                key={item.title}
                                href={item.href}
                                aria-current={
                                    isCurrent(item.href) ? 'page' : undefined
                                }
                                className={`relative rounded-md px-2.5 py-2 text-sm font-medium transition-colors ${
                                    solid
                                        ? isCurrent(item.href)
                                            ? 'text-foreground hover:bg-accent'
                                            : 'text-muted-foreground hover:bg-accent hover:text-foreground'
                                        : isCurrent(item.href)
                                          ? 'text-white drop-shadow-[0_1px_3px_rgba(0,0,0,0.6)]'
                                          : 'text-white/85 drop-shadow-[0_1px_3px_rgba(0,0,0,0.6)] hover:bg-white/15 hover:text-white'
                                }`}
                            >
                                {item.title}
                                {isCurrent(item.href) && (
                                    <span
                                        aria-hidden="true"
                                        className="absolute inset-x-2.5 -bottom-px h-0.5 rounded-full bg-brand-gold"
                                    />
                                )}
                            </Link>
                        ))}
                    </nav>

                    <div className="ml-auto flex items-center gap-2 lg:ml-0">
                        {auth?.user ? (
                            <Button asChild size="sm">
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <Button
                                asChild
                                size="sm"
                                className="hidden sm:flex"
                            >
                                <Link href={login()}>Log in</Link>
                            </Button>
                        )}
                        <button
                            type="button"
                            onClick={() => setOpen((value) => !value)}
                            className={`inline-flex size-10 items-center justify-center rounded-lg border transition-colors lg:hidden ${
                                solid
                                    ? 'border-border hover:bg-accent'
                                    : 'border-white/40 text-white hover:bg-white/15'
                            }`}
                            aria-label="Toggle navigation"
                            aria-expanded={open}
                        >
                            {open ? (
                                <X className="size-5" />
                            ) : (
                                <Menu className="size-5" />
                            )}
                        </button>
                    </div>
                </div>

                {open && (
                    <nav
                        className="flex min-h-[calc(100vh-5rem)] flex-col px-4 pb-10 sm:px-6 lg:hidden"
                        aria-label="Site"
                    >
                        <FlagRule className="h-1 w-24" />

                        <div className="mt-6 grid gap-1">
                            {navigation.map((item) => (
                                <Link
                                    key={item.title}
                                    href={item.href}
                                    onClick={() => setOpen(false)}
                                    aria-current={
                                        isCurrent(item.href)
                                            ? 'page'
                                            : undefined
                                    }
                                    className={`relative rounded-lg py-3 pr-3 pl-5 text-lg font-semibold transition-colors ${
                                        isCurrent(item.href)
                                            ? 'bg-white/10 text-white'
                                            : 'text-white/75 hover:bg-white/5 hover:text-white'
                                    }`}
                                >
                                    {isCurrent(item.href) && (
                                        <span
                                            aria-hidden="true"
                                            className="absolute top-2.5 bottom-2.5 left-0 w-1 rounded-full bg-brand-gold"
                                        />
                                    )}
                                    {item.title}
                                </Link>
                            ))}
                        </div>

                        <div className="mt-auto space-y-4 pt-10">
                            <Button
                                asChild
                                size="lg"
                                className="w-full"
                                onClick={() => setOpen(false)}
                            >
                                <Link href={auth?.user ? dashboard() : login()}>
                                    {auth?.user
                                        ? 'Go to the dashboard'
                                        : 'Log in'}
                                </Link>
                            </Button>
                            <p className="text-center text-xs font-semibold tracking-[0.18em] text-white/55 uppercase">
                                Same Waters · Brighter Futures
                            </p>
                        </div>
                    </nav>
                )}
            </header>

            {title && (
                <PageHero
                    title={title}
                    description={description}
                    figures={figures}
                    actions={heroActions}
                    image={heroImage}
                    size={heroSize}
                />
            )}

            <main className={floating ? 'flex-1' : 'flex-1 pt-20'}>
                {children}
            </main>

            <WhatsAppButton />

            <footer className="on-dark border-t border-white/10 bg-brand-deeper text-white/80">
                <div className="mx-auto w-full max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <div className="grid gap-10 md:grid-cols-4">
                        <div className="md:col-span-2">
                            <BrandLogo tone="light" className="h-14 sm:h-16" />
                            <p className="mt-4 max-w-sm text-sm leading-relaxed">
                                The national governing body for swimming in
                                Zimbabwe, affiliated to Zimbabwe Aquatics,
                                Africa Aquatics and World Aquatics.
                            </p>
                            <p className="mt-6 text-sm font-semibold text-brand-gold italic">
                                Same Waters. Brighter Futures.
                            </p>
                        </div>

                        <div>
                            <h2 className="text-xs font-semibold tracking-[0.16em] text-white uppercase">
                                The season
                            </h2>
                            <ul className="mt-4 space-y-2.5 text-sm">
                                <li>
                                    <Link
                                        href={calendar()}
                                        className="hover:text-white"
                                    >
                                        Calendar
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={results.index()}
                                        className="hover:text-white"
                                    >
                                        Results
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={records()}
                                        className="hover:text-white"
                                    >
                                        National records
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={venues()}
                                        className="hover:text-white"
                                    >
                                        Venue register
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h2 className="text-xs font-semibold tracking-[0.16em] text-white uppercase">
                                Get involved
                            </h2>
                            <ul className="mt-4 space-y-2.5 text-sm">
                                <li>
                                    <Link
                                        href={clubs()}
                                        className="hover:text-white"
                                    >
                                        Find a club
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={officials()}
                                        className="hover:text-white"
                                    >
                                        Become an official
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={about()}
                                        className="hover:text-white"
                                    >
                                        Our structure
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div className="mt-12 flex flex-col gap-3 border-t border-white/10 pt-6 text-xs sm:flex-row sm:items-center sm:justify-between">
                        <p>
                            © {new Date().getFullYear()} Zimbabwe Swimming. All
                            rights reserved.
                        </p>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
                            <p className="tracking-[0.14em] uppercase">
                                One Sport · One Nation · One Team
                            </p>
                            <p>
                                Designed and developed by{' '}
                                <a
                                    href="https://leadingdigital.africa"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="font-semibold text-white underline-offset-4 hover:underline"
                                >
                                    Leading Digital
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    );
}
