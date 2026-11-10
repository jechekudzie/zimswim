import {
    useEffect,
    useState,
    type ElementType,
    type PropsWithChildren,
} from 'react';

import { useInView } from '@/hooks/use-in-view';
import { cn } from '@/lib/utils';

/**
 * Brings a block up as it arrives, once.
 *
 * The displacement is deliberately small. The point is to tell the reader a
 * new subject has started, not to perform; any further and every band on the
 * page competes for the same attention.
 */
export function Reveal({
    as: Tag = 'div',
    delay = 0,
    className,
    children,
}: PropsWithChildren<{
    as?: ElementType;
    delay?: number;
    className?: string;
}>) {
    const { ref, inView } = useInView<HTMLDivElement>();

    return (
        <Tag
            ref={ref}
            style={{ transitionDelay: `${delay}ms` }}
            className={cn(
                'transition-[opacity,transform] duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] motion-reduce:transition-none',
                inView
                    ? 'translate-y-0 opacity-100'
                    : 'translate-y-2 opacity-0',
                className,
            )}
        >
            {children}
        </Tag>
    );
}

/**
 * Runs a whole number up to its value when it comes into view.
 *
 * For the figures a federation is measured by: swimmers, clubs, ratified
 * records. A reader who has asked for less motion, or a figure of zero, is
 * given the number straight away.
 */
export function CountUp({
    value,
    className,
}: {
    value: number;
    className?: string;
}) {
    const { ref, inView } = useInView<HTMLSpanElement>();
    const [shown, setShown] = useState(0);

    useEffect(() => {
        if (!inView) {
            return;
        }

        if (
            value === 0 ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            setShown(value);

            return;
        }

        let frame = 0;
        const started = performance.now();
        const duration = 1000;

        const step = (now: number) => {
            const progress = Math.min(1, (now - started) / duration);

            setShown(Math.round(value * (1 - Math.pow(1 - progress, 3))));

            if (progress < 1) {
                frame = requestAnimationFrame(step);
            }
        };

        frame = requestAnimationFrame(step);

        return () => cancelAnimationFrame(frame);
    }, [value, inView]);

    return (
        <span ref={ref} className={cn('tabular', className)}>
            {shown}
        </span>
    );
}
