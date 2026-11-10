import type { SVGAttributes } from 'react';

/**
 * A swimmer over three waves, echoing the Zimbabwe Swimming wave mark.
 */
export default function BrandMark(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
            {...props}
        >
            <circle cx="33" cy="12" r="4.5" fill="currentColor" />
            <path
                d="M6 21.5c3.4-3.6 7.1-3.6 10.5 0 2.5 2.6 5 3.5 7.6 2.6l9.4-3.2"
                stroke="currentColor"
                strokeWidth="3.2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
            <path
                d="M4 31c4-4.2 8-4.2 12 0s8 4.2 12 0 8-4.2 12 0"
                stroke="currentColor"
                strokeWidth="3"
                strokeLinecap="round"
                strokeLinejoin="round"
                opacity="0.75"
            />
            <path
                d="M4 39.5c4-4.2 8-4.2 12 0s8 4.2 12 0 8-4.2 12 0"
                stroke="currentColor"
                strokeWidth="3"
                strokeLinecap="round"
                strokeLinejoin="round"
                opacity="0.4"
            />
        </svg>
    );
}
