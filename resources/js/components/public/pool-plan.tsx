/**
 * A competition pool drawn from above.
 *
 * This stands in for photography until the federation supplies its own. It is
 * drawn rather than fetched for two reasons: a header that waits on a picture
 * is a header that is blank on the pool's wifi, and a measured plan of the
 * thing the federation actually governs says more than a stock photograph of
 * someone swimming. The markings are the real ones — eight lanes, the stripe
 * each swimmer follows with its cross near the wall, backstroke flags set back
 * from each end, and the blocks along the top.
 */
export function PoolPlan({
    lanes = 8,
    className,
}: {
    lanes?: number;
    className?: string;
}) {
    const deck = 22;
    const width = 420;
    const height = 280;
    const water = {
        x: deck,
        y: deck,
        width: width - deck * 2,
        height: height - deck * 2,
    };
    const laneWidth = water.width / lanes;

    return (
        <svg
            viewBox={`0 0 ${width} ${height}`}
            className={className}
            preserveAspectRatio="xMidYMid slice"
            aria-hidden="true"
        >
            <defs>
                <linearGradient id="pool-water" x1="0" y1="0" x2="0.3" y2="1">
                    <stop
                        offset="0%"
                        stopColor="var(--brand-water)"
                        stopOpacity="0.85"
                    />
                    <stop
                        offset="60%"
                        stopColor="var(--brand-water)"
                        stopOpacity="0.5"
                    />
                    <stop offset="100%" stopColor="var(--brand-deep)" />
                </linearGradient>
            </defs>

            {/* The deck the pool sits in. */}
            <rect width={width} height={height} fill="var(--brand-deeper)" />
            <rect
                x={water.x}
                y={water.y}
                width={water.width}
                height={water.height}
                fill="url(#pool-water)"
            />

            {Array.from({ length: lanes }, (_, lane) => {
                const left = water.x + lane * laneWidth;
                const middle = left + laneWidth / 2;

                return (
                    <g key={lane}>
                        {/* The stripe on the floor a swimmer follows down. */}
                        <line
                            x1={middle}
                            x2={middle}
                            y1={water.y + 14}
                            y2={water.y + water.height - 14}
                            stroke="var(--brand-deeper)"
                            strokeWidth={laneWidth * 0.3}
                            strokeOpacity="0.8"
                        />
                        {/* The cross that warns the wall is coming. */}
                        {[water.y + 26, water.y + water.height - 26].map(
                            (y) => (
                                <line
                                    key={y}
                                    x1={middle - laneWidth * 0.26}
                                    x2={middle + laneWidth * 0.26}
                                    y1={y}
                                    y2={y}
                                    stroke="var(--brand-deeper)"
                                    strokeWidth={laneWidth * 0.22}
                                    strokeOpacity="0.8"
                                />
                            ),
                        )}
                        {/* The rope between this lane and the one before it. */}
                        {lane > 0 && (
                            <line
                                x1={left}
                                x2={left}
                                y1={water.y}
                                y2={water.y + water.height}
                                stroke="white"
                                strokeWidth="1.5"
                                strokeOpacity="0.55"
                                strokeDasharray="4 4"
                            />
                        )}
                        {/* A starting block for every lane. */}
                        <rect
                            x={middle - laneWidth * 0.3}
                            y={water.y - 13}
                            width={laneWidth * 0.6}
                            height="9"
                            rx="2"
                            fill="var(--brand-gold)"
                            fillOpacity="0.85"
                        />
                    </g>
                );
            })}

            {/* Backstroke flags, strung across five metres out from each wall. */}
            {[water.y + 42, water.y + water.height - 42].map((y) => (
                <line
                    key={y}
                    x1={water.x}
                    x2={water.x + water.width}
                    y1={y}
                    y2={y}
                    stroke="var(--brand-gold)"
                    strokeWidth="2"
                    strokeOpacity="0.75"
                    strokeDasharray="6 6"
                />
            ))}

            {/* The pool edge. */}
            <rect
                x={water.x}
                y={water.y}
                width={water.width}
                height={water.height}
                fill="none"
                stroke="white"
                strokeOpacity="0.5"
                strokeWidth="2.5"
            />
        </svg>
    );
}
