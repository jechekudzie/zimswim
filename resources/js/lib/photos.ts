/**
 * The federation's own photographs.
 *
 * Every public page opens on one of these rather than on stock or on a
 * placeholder service: they were taken at Zimbabwean galas, so the site shows
 * the pools, the clubs and the swimmers it keeps records for. Each is served
 * at two widths and the browser picks by viewport.
 */
export type Photo = {
    src: string;
    srcSet: string;
    alt: string;
    /**
     * Where the subject sits in the frame, as an `object-position` value.
     *
     * A hero band is far wider than it is tall, so a 3:2 photograph loses
     * most of its height to the crop. Each picture carries the point that
     * must survive it — the swimmers, not the sky.
     */
    position: string;
};

function photo(name: string, alt: string, position = '50% 50%'): Photo {
    return {
        src: `/images/${name}-1600.webp`,
        srcSet: `/images/${name}-800.webp 800w, /images/${name}-1600.webp 1600w`,
        alt,
        position,
    };
}

/**
 * One of the federation's designed page banners.
 *
 * The artwork supplied carries its page title and rule baked into the left
 * third. Only the photographic part is used here: a title drawn into a
 * picture cannot resize on a phone, cannot be read by a screen reader and
 * would sit under the live heading. The photograph, the wash and the flag
 * wave are the design; the words are set in the page.
 */
function banner(name: string, alt: string, position = '55% 50%'): Photo {
    return {
        src: `/images/page-${name}-1800.webp`,
        srcSet: `/images/page-${name}-900.webp 900w, /images/page-${name}-1800.webp 1800w`,
        alt,
        position,
    };
}

export const photos = {
    /**
     * The federation's own hero banner: three to one, swimmer on the right,
     * open water on the left where the heading goes, and the flag wave
     * already drawn across the foot of it. It is served at its own widths
     * because it is not a 3:2 photograph like the rest.
     */
    heroBanner: {
        src: '/images/hero-banner-2200.webp',
        srcSet: '/images/hero-banner-1100.webp 1100w, /images/hero-banner-2200.webp 2200w',
        alt: 'A Zimbabwe Swimming swimmer racing butterfly, head out of the water',
        position: '72% 45%',
    } as Photo,
    pages: {
        about: banner(
            'about',
            'A Zimbabwe Swimming shirt on the pool deck beneath club bunting',
        ),
        calendar: banner(
            'calendar',
            'Goggles resting on the poolside beside a desk calendar',
        ),
        clubs: banner(
            'clubs',
            'Starting block four standing over an empty competition pool',
        ),
        news: banner(
            'news',
            'A Zimbabwe Swimming microphone at the side of the pool',
        ),
        officials: banner(
            'officials',
            'An official in a marked shirt watching the lanes from the deck',
        ),
        records: banner(
            'records',
            'A gold swimming medal on a Zimbabwe ribbon resting in the water',
        ),
        results: banner(
            'results',
            'A scoreboard showing the finishing times for an event',
        ),
        venues: banner(
            'venues',
            'A fifty-metre competition pool beside its covered grandstand',
        ),
    },

    start: photo(
        'gala-start',
        'Swimmers diving from the blocks at the start of a race while officials watch from the deck',
        '50% 58%',
    ),
    field: photo(
        'gala-field',
        'An outdoor fifty-metre pool set up for a gala, club banners along the far side and hills beyond',
        '50% 62%',
    ),
    freestyle: photo(
        'gala-freestyle',
        'Swimmers racing freestyle down neighbouring lanes',
        '50% 55%',
    ),
    lanes: photo(
        'gala-lanes',
        'Swimmers mid-race in adjacent lanes, seen from the side of the pool',
        '50% 60%',
    ),
    marquees: photo(
        'venue-marquees',
        'Club marquees and team banners lining the deck of an outdoor competition pool',
        '50% 45%',
    ),
    palms: photo(
        'venue-palms',
        'An outdoor competition pool with lane ropes in place and team tents behind',
        '50% 58%',
    ),
    dusk: photo(
        'venue-dusk',
        'An outdoor pool under floodlights at dusk with hills behind the deck',
        '50% 55%',
    ),
    squad: photo(
        'squad',
        'A club squad photographed together in team tracksuits',
        '50% 40%',
    ),
    podiumChampions: photo(
        'podium-champions',
        'Young swimmers on the podium with their medals',
        '50% 35%',
    ),
    podiumYouth: photo(
        'podium-youth',
        'Swimmers receiving medals on the podium at a national gala',
        '50% 35%',
    ),
    podiumTeens: photo(
        'podium-teens',
        'Teenage swimmers standing on the medal podium',
        '50% 35%',
    ),
} as const;

export type PhotoName = keyof typeof photos;
