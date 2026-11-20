<?php

namespace App\Support\Content;

/**
 * Every piece of wording and every picture on the public pages that the
 * federation may change without a developer.
 *
 * The catalogue lives in code rather than in the database, and the database
 * holds only what has been changed. Three things follow from that, and they
 * are the whole reason for the arrangement:
 *
 * - A page always renders. A missing row, a cleared field or a dropped table
 *   falls back to the wording the page shipped with.
 * - Clearing a field is an undo. It restores the original rather than leaving
 *   an empty heading on a live page.
 * - Adding something editable is one entry here. It appears on the editing
 *   screen by itself, with its current wording already in the box.
 *
 * A block is one of three kinds. `text` is a line somebody types, like a
 * heading. `rich` is a description, written with emphasis, links and lists,
 * and stored as the markup that allows — see RichText for the whole of what
 * one may contain. `image` is a photograph uploaded to stand in for the one
 * the page ships with.
 *
 * What is deliberately not here: button labels, table headings, form hints,
 * and anything stating a rule. The rules — minimum ages, learner sessions,
 * pass marks — are read from the standards the register enforces, and must
 * not become free text that can drift from what the system actually does.
 */
class ContentCatalogue
{
    /**
     * @return array<string, array{label: string, groups: array<string, array{label: string, blocks: array<string, array{label: string, type: string, default: string, hint?: string}>}>}>
     */
    public static function pages(): array
    {
        return [
            'home' => [
                'label' => 'Home page',
                'groups' => [
                    'hero' => [
                        'label' => 'Opening band',
                        'blocks' => [
                            'home.hero.heading' => [
                                'label' => 'Heading, first line',
                                'type' => 'text',
                                'default' => 'From the first splash',
                            ],
                            'home.hero.heading_accent' => [
                                'label' => 'Heading, second line',
                                'type' => 'text',
                                'default' => 'to the Olympic stage',
                                'hint' => 'Shown in gold beneath the first line.',
                            ],
                            'home.hero.lede' => [
                                'label' => 'Opening sentence',
                                'type' => 'rich',
                                'default' => 'One national home for the calendar, clubs, pools, the officials pathway and every ratified Zimbabwean record.',
                            ],
                            'home.hero.primary_action' => [
                                'label' => 'First button',
                                'type' => 'text',
                                'default' => 'See the season calendar',
                            ],
                            'home.hero.tagline' => [
                                'label' => 'Tagline',
                                'type' => 'text',
                                'default' => 'Same Waters · Brighter Futures',
                            ],
                            'home.hero.image' => [
                                'label' => 'Opening photograph',
                                'type' => 'image',
                                'default' => '',
                                'hint' => 'Wide, about three to one. Leave empty to keep the photograph the site ships with.',
                            ],
                        ],
                    ],
                    'upcoming' => [
                        'label' => 'What is coming up',
                        'blocks' => [
                            'home.upcoming.featured_eyebrow' => [
                                'label' => 'Featured gala label',
                                'type' => 'text',
                                'default' => 'Next on the national calendar',
                            ],
                            'home.upcoming.heading' => [
                                'label' => 'Heading',
                                'type' => 'text',
                                'default' => 'What is coming up',
                            ],
                            'home.upcoming.lede' => [
                                'label' => 'Standfirst',
                                'type' => 'rich',
                                'default' => 'Only meets on the official provincial and national calendars are recognised by Zimbabwe Swimming.',
                            ],
                            'home.upcoming.empty' => [
                                'label' => 'Shown when nothing is scheduled',
                                'type' => 'text',
                                'default' => 'No galas are on the calendar yet.',
                            ],
                        ],
                    ],
                    'officials' => [
                        'label' => 'Asking people to officiate',
                        'blocks' => [
                            'home.officials.heading' => [
                                'label' => 'Heading',
                                'type' => 'text',
                                'default' => 'Somebody has to time the race',
                            ],
                            'home.officials.lede' => [
                                'label' => 'First paragraph',
                                'type' => 'rich',
                                'default' => "Every gala on this calendar is run by volunteers at the water's edge — timekeepers, inspectors of turns, judges of stroke, a starter and a referee. Short a crew and the swimmers wait behind the blocks.",
                            ],
                            'home.officials.second' => [
                                'label' => 'Second paragraph',
                                'type' => 'rich',
                                'default' => 'Almost every national referee in the country started with a stopwatch and one Saturday morning.',
                            ],
                            'home.officials.note' => [
                                'label' => 'Note under the buttons',
                                'type' => 'rich',
                                'default' => 'Officials are accredited through an affiliated club, so joining one is the first step if you are not already a member.',
                            ],
                        ],
                    ],
                    'pathways' => [
                        'label' => 'Ways in',
                        'blocks' => [
                            'home.pathways.heading' => [
                                'label' => 'Heading',
                                'type' => 'text',
                                'default' => 'There is a place for you on the pool deck',
                            ],
                            'home.pathways.lede' => [
                                'label' => 'Standfirst',
                                'type' => 'rich',
                                'default' => 'Different roles. Same team. Whether you swim, time, judge or drive to galas on a Saturday morning, the sport runs on people.',
                            ],
                        ],
                    ],
                    'records' => [
                        'label' => 'Records band',
                        'blocks' => [
                            'home.records.heading' => [
                                'label' => 'Heading, first line',
                                'type' => 'text',
                                'default' => 'Every record tells a story.',
                            ],
                            'home.records.heading_accent' => [
                                'label' => 'Heading, second line',
                                'type' => 'text',
                                'default' => 'What will yours be?',
                            ],
                            'home.records.lede' => [
                                'label' => 'Standfirst',
                                'type' => 'rich',
                                'default' => 'Records are proposed automatically when a time beats the standing mark, and only change once the authorised body ratifies them. Long course and short course, every age group, girls and boys.',
                            ],
                        ],
                    ],
                    'closing' => [
                        'label' => 'Venues, news and sponsors',
                        'blocks' => [
                            'home.venues.heading' => [
                                'label' => 'Venues heading',
                                'type' => 'text',
                                'default' => 'Where the nationals are swum',
                            ],
                            'home.venues.lede' => [
                                'label' => 'Venues standfirst',
                                'type' => 'rich',
                                'default' => 'Pools graded to carry senior nationals, the sprint gala and international meets.',
                            ],
                            'home.news.heading' => [
                                'label' => 'News heading',
                                'type' => 'text',
                                'default' => 'Latest from the pool deck',
                            ],
                            'home.provinces.heading' => [
                                'label' => 'Provinces heading',
                                'type' => 'text',
                                'default' => 'One sport. One nation. One team.',
                            ],
                            'home.sponsors.heading' => [
                                'label' => 'Sponsors heading',
                                'type' => 'text',
                                'default' => 'We thank our sponsors for their support',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Every block on one page, flattened to key => definition.
     *
     * @return array<string, array{label: string, type: string, default: string, hint?: string}>
     */
    public static function blocksFor(string $page): array
    {
        $blocks = [];

        foreach (self::pages()[$page]['groups'] ?? [] as $group) {
            foreach ($group['blocks'] as $key => $block) {
                $blocks[$key] = $block;
            }
        }

        return $blocks;
    }

    /**
     * @return array<string, array{label: string, type: string, default: string, hint?: string}>
     */
    public static function all(): array
    {
        $blocks = [];

        foreach (array_keys(self::pages()) as $page) {
            $blocks += self::blocksFor($page);
        }

        return $blocks;
    }

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::all());
    }
}
