<?php

namespace App\Http\Controllers\Public;

use App\Enums\TrainingKind;
use App\Http\Controllers\Controller;
use App\Models\OfficialTraining;
use App\Models\OfficialTrainingRegistration;
use App\Models\TechnicalOfficial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Clinics and exam sittings, and putting your name down for one.
 *
 * The federation's complaint was that the last timekeeping clinic was
 * advertised behind a link that turned people away. Nothing here restricts
 * who may attend: no account is needed, no accreditation is needed, and a
 * session is open unless its host has deliberately capped it or closed it.
 */
class OfficialTrainingController extends Controller
{
    public function index(): Response
    {
        $upcoming = OfficialTraining::query()
            ->published()
            ->upcoming()
            ->with(['host:id,name', 'venue:id,name,city'])
            ->withCount('registrations')
            ->get()
            ->map(fn (OfficialTraining $training): array => $this->summary($training));

        /*
         * A short tail of what has just been run. Somebody who missed a
         * clinic needs to see that it happened rather than conclude the
         * federation has not held one.
         */
        $recent = OfficialTraining::query()
            ->published()
            ->whereDate('held_on', '<', today())
            ->with(['host:id,name', 'venue:id,name,city'])
            ->withCount('registrations')
            ->orderByDesc('held_on')
            ->limit(4)
            ->get()
            ->map(fn (OfficialTraining $training): array => $this->summary($training));

        return Inertia::render('public/officials-training', [
            'upcoming' => $upcoming,
            'recent' => $recent,
            'kinds' => array_map(
                fn (TrainingKind $kind): array => [
                    'value' => $kind->value,
                    'label' => $kind->label(),
                    'description' => $kind->description(),
                ],
                TrainingKind::cases(),
            ),
        ]);
    }

    public function show(OfficialTraining $training): Response
    {
        abort_unless($training->is_published, 404);

        $training->load(['host:id,name', 'venue:id,name,city,address']);

        return Inertia::render('public/officials-training-show', [
            'training' => [
                ...$this->summary($training),
                'description' => $training->description,
                'contact_name' => $training->contact_name,
                'contact_email' => $training->contact_email,
                'contact_phone' => $training->contact_phone,
                'venue_address' => $training->venue?->address,
                'kind_description' => $training->kind->description(),
            ],
        ]);
    }

    /**
     * Put somebody's name down.
     *
     * Registering twice with the same address updates the first entry rather
     * than taking a second seat, because somebody correcting a typed phone
     * number should not cost a place.
     */
    public function register(Request $request, OfficialTraining $training): RedirectResponse
    {
        abort_unless($training->is_published, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'club_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = OfficialTrainingRegistration::query()
            ->where('official_training_id', $training->getKey())
            ->where('email', $validated['email'])
            ->first();

        if ($existing === null && ! $training->isOpenForRegistration()) {
            throw ValidationException::withMessages([
                'email' => $this->closedReason($training),
            ]);
        }

        OfficialTrainingRegistration::updateOrCreate(
            ['official_training_id' => $training->getKey(), 'email' => $validated['email']],
            [
                ...$validated,
                'user_id' => $request->user()?->getKey(),
                // Recorded where the person is already on the register, so
                // the host can see who is renewing and who is starting out.
                'technical_official_id' => TechnicalOfficial::where('email', $validated['email'])->value('id'),
                // Kept from the first time, so correcting a typed phone
                // number does not move somebody down the list.
                'registered_at' => $existing === null ? now() : $existing->registered_at,
            ],
        );

        return back()->with('success', 'Your name is down for '.$training->title.'. The host will be in touch with the details.');
    }

    private function closedReason(OfficialTraining $training): string
    {
        if ($training->hasFinished()) {
            return 'This session has already been held.';
        }

        if ($training->isFull()) {
            return 'Every place has been taken. Contact the host — they may run another.';
        }

        return 'Registration has closed for this session.';
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(OfficialTraining $training): array
    {
        return [
            'title' => $training->title,
            'slug' => $training->slug,
            'kind' => $training->kind->value,
            'kind_label' => $training->kind->label(),
            'category' => $training->category?->label(),
            'host' => $training->host->name,
            'venue' => $training->venue?->name,
            'city' => $training->venue->city ?? $training->location,
            'is_online' => $training->is_online,
            'held_on' => $training->held_on->toDateString(),
            'ends_on' => $training->ends_on?->toDateString(),
            'starts_at' => $training->starts_at,
            'ends_at' => $training->ends_at,
            'registration_closes_at' => $training->registration_closes_at?->toDateString(),
            'places_left' => $training->placesLeft(),
            'is_open' => $training->isOpenForRegistration(),
            'has_finished' => $training->hasFinished(),
            'attending' => $training->registrations_count,
        ];
    }
}
