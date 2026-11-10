<?php

namespace App\Http\Controllers\Public;

use App\Enums\GalaLevel;
use App\Enums\OfficialCategory;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Post;
use App\Models\Season;
use App\Models\Sponsor;
use App\Models\SwimRecord;
use App\Models\TechnicalOfficial;
use App\Models\Venue;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $season = Season::current();

        return Inertia::render('public/home', [
            'season' => $season === null ? null : [
                'name' => $season->name,
                'starts_on' => $season->starts_on->toDateString(),
                'ends_on' => $season->ends_on->toDateString(),
                'age_up_date' => $season->age_up_date->toDateString(),
            ],
            'featured' => $this->featuredGala(),
            'upcomingGalas' => Gala::query()
                ->published()
                ->upcoming()
                ->with(['venue:id,name,city', 'host:id,name'])
                ->limit(4)
                ->get()
                ->map(fn (Gala $gala): array => [
                    'name' => $gala->name,
                    'slug' => $gala->slug,
                    'level' => $gala->level->label(),
                    'starts_on' => $gala->starts_on->toDateString(),
                    'ends_on' => $gala->ends_on->toDateString(),
                    'venue' => $gala->venue?->name,
                    'city' => $gala->venue?->city,
                    'host' => $gala->host->name,
                ]),
            'news' => Post::query()
                ->published()
                ->orderByDesc('published_at')
                ->limit(3)
                ->get(['title', 'slug', 'excerpt', 'category', 'published_at'])
                ->map(fn (Post $post): array => [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'excerpt' => $post->excerpt,
                    'category' => $post->category,
                    'published_at' => $post->published_at?->toDateString(),
                ]),
            'provinces' => Organization::query()
                ->ofType(OrganizationType::Province)
                ->active()
                ->orderBy('name')
                ->get(['name', 'slug', 'city'])
                ->map(fn (Organization $province): array => [
                    'name' => $province->short_name ?? $province->name,
                    'slug' => $province->slug,
                    'city' => $province->city,
                ]),
            'venues' => Venue::query()
                ->active()
                ->where('grade', 'national')
                ->orderBy('name')
                ->limit(3)
                ->get(['name', 'slug', 'city', 'pool_length', 'lanes'])
                ->map(fn (Venue $venue): array => [
                    'name' => $venue->name,
                    'slug' => $venue->slug,
                    'city' => $venue->city,
                    'pool_length' => $venue->pool_length,
                    'lanes' => $venue->lanes,
                ]),
            'officialsEntry' => $this->officialsEntry(),
            'sponsors' => Sponsor::query()->active()->get(['name', 'slug', 'website', 'tier']),
            'stats' => [
                'provinces' => Organization::query()->ofType(OrganizationType::Province)->active()->count(),
                'clubs' => Organization::query()->ofType(OrganizationType::Club)->active()->count(),
                'swimmers' => Member::query()->active()->count(),
                'officials' => TechnicalOfficial::query()->where('status', 'active')->count(),
                'records' => SwimRecord::query()->ratified()->count(),
                'galas' => Gala::query()->published()->upcoming()->count(),
            ],
        ]);
    }

    /**
     * What it actually takes to start officiating.
     *
     * Read from the certification standards rather than written into the
     * page, so the invitation on the home page cannot promise an age or a
     * number of learner sessions the register will not accept. Timekeeper is
     * the entry point: it is the only category with no prerequisite that a
     * school-age member can hold.
     *
     * @return array{category: string, minimum_age: int, learner_sessions: int}
     */
    private function officialsEntry(): array
    {
        $entry = OfficialCategory::Timekeeper;

        return [
            'category' => $entry->label(),
            'minimum_age' => $entry->minimumAge(),
            'learner_sessions' => $entry->learnerSessionsRequired(),
        ];
    }

    /**
     * The next national gala, which anchors the top of the page.
     *
     * @return array<string, mixed>|null
     */
    private function featuredGala(): ?array
    {
        $gala = Gala::query()
            ->published()
            ->upcoming()
            ->whereIn('level', [GalaLevel::National, GalaLevel::International])
            ->with(['venue:id,name,city', 'host:id,name'])
            ->first();

        if ($gala === null) {
            return null;
        }

        return [
            'name' => $gala->name,
            'slug' => $gala->slug,
            'level' => $gala->level->label(),
            'starts_on' => $gala->starts_on->toDateString(),
            'ends_on' => $gala->ends_on->toDateString(),
            'days_away' => (int) now()->startOfDay()->diffInDays($gala->starts_on, absolute: true),
            'venue' => $gala->venue?->name,
            'city' => $gala->venue?->city,
            'eligible_ages' => $gala->eligible_ages,
            'entries_close_at' => $gala->entries_close_at?->toDateString(),
            'description' => $gala->description,
        ];
    }
}
