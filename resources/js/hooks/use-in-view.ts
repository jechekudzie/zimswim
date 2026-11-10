import { useEffect, useRef, useState } from 'react';

/**
 * Tells you when an element has first come into view.
 *
 * It reports true once and then stops watching: a band that re-animates
 * every time it is scrolled past is a distraction, not a reveal. A browser
 * without IntersectionObserver, or a reader who has asked for less motion,
 * is told true immediately, so the content is simply there.
 */
export function useInView<T extends HTMLElement>() {
    const ref = useRef<T | null>(null);
    const [inView, setInView] = useState(false);

    useEffect(() => {
        const element = ref.current;

        if (element === null) {
            return;
        }

        if (
            typeof IntersectionObserver === 'undefined' ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            setInView(true);

            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    setInView(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.15, rootMargin: '0px 0px -8% 0px' },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    return { ref, inView };
}
