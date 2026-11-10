import { cn } from '@/lib/utils';

/**
 * The federation's wordmark, drawn inline rather than loaded as an image.
 *
 * The supplied artwork has black ink and three flag colours. Inlining it
 * lets the ink follow `currentColor` while the green, gold and red stay
 * exactly as drawn, so the same mark sits on a white page and on deep water
 * without a knockout copy or a white panel behind it.
 */
export function BrandWordmark({ className }: { className?: string }) {
    return (
        <svg
            viewBox="0 0 760 310"
            role="img"
            aria-label="Zimbabwe Swimming"
            className={cn('w-auto', className)}
        >
            <g transform="translate(0 5)">
                {/* The diving swimmer, trailing the flag behind her. */}
                <path
                    d="M322 35 C374 42 427 75 473 113 C490 126 506 139 523 149 C485 119 447 89 410 70 C378 53 348 42 322 35Z"
                    fill="#00843D"
                />
                <path
                    d="M401 74 C425 88 449 105 470 123 L488 133 C466 123 443 109 423 94Z"
                    fill="#F9C80E"
                />
                <path
                    d="M435 102 C455 115 471 127 487 136 L500 145 C482 138 462 125 447 113Z"
                    fill="#D62828"
                />
                <path
                    d="M479 121 C504 139 530 159 552 184 C568 202 580 218 591 230 C579 204 557 175 532 155 C512 139 496 129 479 121Z"
                    fill="currentColor"
                />
                <ellipse
                    cx="549"
                    cy="171"
                    rx="16"
                    ry="8"
                    transform="rotate(31 549 171)"
                    fill="currentColor"
                />

                <g
                    fill="currentColor"
                    fontFamily="Arial, Helvetica, sans-serif"
                    fontSize="66"
                    fontWeight="400"
                    letterSpacing="0.5"
                >
                    <text x="24" y="174">
                        ZIMBABWE
                    </text>
                    <text x="24" y="242">
                        SWIMMING
                    </text>
                </g>

                {/* The water below the wordmark, in the flag's three colours. */}
                <path
                    d="M490 211 C511 190 526 190 547 211 S581 231 603 211 S636 191 658 211 S691 231 713 211"
                    fill="none"
                    stroke="#00843D"
                    strokeWidth="7"
                    strokeLinecap="round"
                />
                <path
                    d="M490 224 C511 203 526 203 547 224 S581 244 603 224 S636 204 658 224 S691 244 713 224"
                    fill="none"
                    stroke="#F9C80E"
                    strokeWidth="7"
                    strokeLinecap="round"
                />
                <path
                    d="M490 237 C511 216 526 216 547 237 S581 257 603 237 S636 217 658 237 S691 257 713 237"
                    fill="none"
                    stroke="#D62828"
                    strokeWidth="7"
                    strokeLinecap="round"
                />
            </g>
        </svg>
    );
}
