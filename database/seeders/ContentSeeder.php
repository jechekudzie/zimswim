<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Post;
use App\Models\Sponsor;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $national = Organization::where('slug', 'zimbabwe-swimming')->firstOrFail();

        $sponsors = [
            ['name' => 'Hammer & Tongues', 'slug' => 'hammer-and-tongues', 'tier' => 'principal', 'position' => 0],
            ['name' => 'EcoCash', 'slug' => 'ecocash', 'tier' => 'principal', 'position' => 1],
        ];

        foreach ($sponsors as $sponsor) {
            Sponsor::updateOrCreate(
                ['slug' => $sponsor['slug']],
                [
                    'name' => $sponsor['name'],
                    'organization_id' => $national->getKey(),
                    'tier' => $sponsor['tier'],
                    'position' => $sponsor['position'],
                    'is_active' => true,
                ],
            );
        }

        $posts = [
            [
                'title' => 'The 2026/2027 season is open',
                'slug' => 'the-2026-2027-season-is-open',
                'category' => 'news',
                'excerpt' => 'The National Handbook for 2026/2027 is out, and with it the provincial and national calendar that carries us from the Manicaland Championships in September through to National Development in March.',
                'body' => "The National Handbook for the 2026/2027 season is now available, setting out the calendar, the capitation fees, the officials pathway and the championship rules for the year ahead.\n\nOnly meets appearing on the official provincial or national calendars are recognised by Zimbabwe Swimming. Clubs should check their province's dates early and make sure capitation fees are settled before entries open: a swimmer who is not fully capitated cannot be entered.\n\nThe season opens with the Manicaland Championships on 25 September 2026 and runs through to the National Development gala in March 2027, with the National Sprint Gala in Mutare in October, Junior Nationals at Les Brown in January and Senior Nationals in Bulawayo in February.",
                'is_featured' => true,
                'published_at' => '2026-09-01 08:00:00',
            ],
            [
                'title' => 'National Sprint Gala returns to Mutare',
                'slug' => 'national-sprint-gala-returns-to-mutare',
                'category' => 'news',
                'excerpt' => 'The Mutare Municipal City Pool hosts the National Sprint Gala on 16 and 17 October 2026, open to swimmers 8 years and above.',
                'body' => "The Zimbabwe Swimming National Sprint Gala will be held at the Mutare Municipal City Pool on 16 and 17 October 2026.\n\nThe meet is swum under World Aquatics rules as separate gender, timed finals. Sprint Gala age groups are 9-10, 11/12, 13/14 and 15 & Over for the 50m events, and 12/U, 13/14 and 15 & Over for the 100m events. Age is determined as at 31 December 2026.\n\nNo qualifying times apply. Entries are submitted through clubs and close on Monday 5 October 2026, with the corrections deadline on 12 October. Capitation fees must be paid by 30 September 2026.\n\nThis is an interclub competition, and all clubs must encourage their technical officials to volunteer by 5 October or their entries will not be accepted.",
                'published_at' => '2026-09-15 08:00:00',
            ],
            [
                'title' => 'New to officiating? Start as a timekeeper',
                'slug' => 'new-to-officiating-start-as-a-timekeeper',
                'category' => 'officials',
                'excerpt' => 'Every qualified official started as a learner. Here is how a parent goes from the poolside to a national accreditation.',
                'body' => "You do not need to know everything on day one. Timekeeping is where almost every official in Zimbabwe starts: you get involved at galas, learn the rules, and become part of the action.\n\nThe pathway runs in six steps. Start as a timekeeper, attend a clinic, complete your learner sessions on the pool deck, write the exam, become qualified, and then progress further if you wish into chief timekeeper, inspector of turns, judge of stroke, starter or referee.\n\nAll technical officials must be registered members of a registered swimming club and the province in their place of domicile. Accreditation is the same in all provinces and is recognised nationally, but it lapses if you do not officiate at your highest level for two years. Only one new officiating category or examination may normally be undertaken per season.",
                'published_at' => '2026-09-20 08:00:00',
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(
                ['slug' => $post['slug']],
                [
                    'organization_id' => $national->getKey(),
                    'title' => $post['title'],
                    'category' => $post['category'],
                    'excerpt' => $post['excerpt'],
                    'body' => $post['body'],
                    'is_featured' => $post['is_featured'] ?? false,
                    'published_at' => $post['published_at'],
                ],
            );
        }
    }
}
