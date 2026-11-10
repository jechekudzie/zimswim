<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\GalaEntry;
use App\Models\GalaEvent;
use App\Models\Member;
use App\Models\Organization;
use App\Models\RelayTeam;
use App\Models\RelayTeamMember;
use App\Services\RelayService;
use App\Support\OrganizationContext;
use App\Support\SwimTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Declaring relay teams for a gala.
 *
 * A relay is entered as a team with a named swimmer on each leg, so this screen
 * is a team builder rather than an entry form: pick the event, pick the club,
 * put four swimmers in order, and the rules about age, gender, borrowing and
 * how many teams a club may field are checked before it is saved.
 */
class RelayTeamController extends Controller
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly RelayService $relays,
    ) {}

    public function index(Request $request, Gala $gala): Response
    {
        $this->authorize('viewAny', GalaEntry::class);

        $scopeIds = $this->context->scopeIds();

        $events = $gala->events()->where('is_relay', true)->get();

        $teams = RelayTeam::query()
            ->where('gala_id', $gala->getKey())
            ->whereIn('organization_id', $scopeIds)
            ->with(['swimmers.member:id,first_name,last_name,gender', 'club:id,name,short_name', 'event'])
            ->get();

        return Inertia::render('admin/galas/relays', [
            'gala' => [
                'id' => $gala->getKey(),
                'slug' => $gala->slug,
                'name' => $gala->name,
                'accepting_entries' => $gala->isAcceptingEntries(),
                'age_up_date' => $gala->effectiveAgeUpDate()->toDateString(),
                'lanes' => $gala->laneCount(),
            ],
            'events' => $events->map(fn (GalaEvent $event): array => [
                'value' => $event->getKey(),
                'label' => sprintf('%d · %s %s', $event->event_number, $event->age_group, $event->title()),
                'legs' => $event->relay_legs ?? 4,
                'gender' => $event->genderRestriction(),
                'age_group' => $event->age_group,
                'medley' => $event->stroke->value === 'medley_relay',
                'seeded' => $event->seeded_at !== null,
            ]),
            'teams' => $teams
                ->sortBy([
                    fn (RelayTeam $a, RelayTeam $b): int => $a->event->event_number <=> $b->event->event_number,
                    fn (RelayTeam $a, RelayTeam $b): int => strcmp($a->club->name, $b->club->name),
                    fn (RelayTeam $a, RelayTeam $b): int => strcmp($a->letter, $b->letter),
                ])
                ->values()
                ->map(fn (RelayTeam $team): array => [
                    'id' => $team->getKey(),
                    'event_id' => $team->gala_event_id,
                    'event_number' => $team->event->event_number,
                    'event' => $team->event->title(),
                    'age_group' => $team->event->age_group,
                    'club' => $team->club->name,
                    'letter' => $team->letter,
                    'entry_time' => $team->entryTime(),
                    'heat' => $team->heat,
                    'lane' => $team->lane,
                    'status' => $team->status,
                    'scores_points' => $team->scoresPoints(),
                    'legs' => $team->swimmers->map(fn (RelayTeamMember $swimmer): array => [
                        'leg' => $swimmer->leg,
                        'swimmer' => $swimmer->member->fullName(),
                        'stroke' => $swimmer->stroke,
                        'is_borrowed' => $swimmer->is_borrowed,
                    ]),
                ]),
            'clubs' => Organization::query()
                ->whereKey($scopeIds)
                ->whereIn('type', [OrganizationType::Club, OrganizationType::Province])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Organization $club): array => [
                    'value' => $club->getKey(),
                    'label' => $club->name,
                ]),
            'swimmers' => Member::query()
                ->forOrganizations($scopeIds)
                ->active()
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'gender', 'date_of_birth', 'organization_id'])
                ->map(fn (Member $member): array => [
                    'value' => $member->getKey(),
                    'label' => $member->fullName(),
                    'gender' => $member->gender,
                    'club_id' => $member->organization_id,
                    'age' => $member->ageAt($gala->effectiveAgeUpDate()),
                ]),
            'can' => ['manage' => $request->user()->can('manage', GalaEntry::class)],
        ]);
    }

    public function store(Request $request, Gala $gala): RedirectResponse
    {
        $this->authorize('create', GalaEntry::class);

        $validated = $request->validate([
            'gala_event_id' => [
                'required', 'integer',
                Rule::exists('gala_events', 'id')->where('gala_id', $gala->getKey())->where('is_relay', true),
            ],
            'organization_id' => ['required', 'integer', Rule::in($this->context->scopeIds())],
            'letter' => ['required', 'string', 'max:2'],
            'member_ids' => ['required', 'array', 'min:1', 'max:8'],
            'member_ids.*' => ['required', 'integer', 'exists:members,id'],
            'borrowed' => ['array'],
            'entry_time' => ['nullable', 'string', 'max:16'],
        ]);

        $event = GalaEvent::query()->findOrFail((int) $validated['gala_event_id']);
        $memberIds = array_map('intval', $validated['member_ids']);
        $letter = strtoupper($validated['letter']);

        $borrowed = [];

        foreach ((array) ($validated['borrowed'] ?? []) as $memberId => $value) {
            $borrowed[(int) $memberId] = (bool) $value;
        }

        $problems = $this->relays->problems(
            $event,
            $memberIds,
            (int) $validated['organization_id'],
            $letter,
            $borrowed,
        );

        if ($problems !== []) {
            return back()->withErrors(['member_ids' => $problems[0]]);
        }

        $team = $this->relays->declare(
            $event,
            (int) $validated['organization_id'],
            $letter,
            $memberIds,
            SwimTime::tryParse($validated['entry_time'] ?? null),
            $borrowed,
        );

        $message = $team->name.' '.$letter.' entered in event '.$event->event_number.'.';

        if (! $team->scoresPoints()) {
            $message .= ' Borrowed swimmers mean the team can win medals but scores no points.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Gala $gala, RelayTeam $team): RedirectResponse
    {
        $this->authorize('create', GalaEntry::class);

        abort_unless(
            in_array($team->organization_id, $this->context->scopeIds(), true),
            403,
            'That team belongs to another club.',
        );

        $team->delete();

        return back()->with('success', 'Relay team withdrawn.');
    }
}
