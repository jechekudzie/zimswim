<?php

namespace App\Http\Controllers\Public;

use App\Events\EventResultsPublished;
use App\Http\Controllers\Controller;
use App\Models\Gala;
use App\Models\GalaEvent;
use App\Services\ResultsBoard;
use App\Services\ResultService;
use Inertia\Inertia;
use Inertia\LiveProp;
use Inertia\Response;

/**
 * Published results, live as each event is confirmed.
 */
class ResultsController extends Controller
{
    public function __construct(
        private readonly ResultService $results,
        private readonly ResultsBoard $board,
    ) {}

    public function index(): Response
    {
        $galas = Gala::query()
            ->published()
            ->whereHas('events', fn ($query) => $query->whereNotNull('results_published_at'))
            ->with('venue:id,name,city')
            ->orderByDesc('starts_on')
            ->limit(25)
            ->get()
            ->map(fn (Gala $gala): array => [
                'name' => $gala->name,
                'slug' => $gala->slug,
                'level' => $gala->level->label(),
                'starts_on' => $gala->starts_on->toDateString(),
                'venue' => $gala->venueName(),
                'events_published' => $gala->events()->whereNotNull('results_published_at')->count(),
                'is_live' => $this->board->isLive($gala),
            ]);

        return Inertia::render('public/results', ['galas' => $galas]);
    }

    public function show(Gala $gala): Response
    {
        abort_unless($gala->is_published, 404);

        return Inertia::render('public/gala-results', [
            'gala' => $this->board->gala($gala),
            'events' => $this->board->publishedEvents($gala)
                ->map(fn (GalaEvent $event): array => $this->board->event($event)),
            'pointsTable' => $this->results->pointsTable($gala->getKey()),
        ]);
    }

    /**
     * The live board for a gala that is being swum.
     *
     * Everything that changes during a session is a live prop: the referee
     * signs an event off, the broadcast carries the new board down the open
     * socket, and the page redraws without asking for anything. The board
     * carries its own values rather than making each phone come back for them,
     * because a gala watched from the stand is a few hundred phones holding
     * the same page, and they would otherwise all ask at once.
     *
     * Only published events appear here, so nothing provisional is ever shown
     * to the public.
     */
    public function live(Gala $gala): Response
    {
        abort_unless($gala->is_published, 404);

        $board = $this->board->board($gala);

        return Inertia::render('public/live-results', [
            'gala' => $this->board->gala($gala),
            'isLive' => $this->liveProp($gala, $board['isLive']),
            'progress' => $this->liveProp($gala, $board['progress']),
            'latest' => $this->liveProp($gala, $board['latest']),
            'earlier' => $this->liveProp($gala, $board['earlier']),
            'pointsTable' => $this->liveProp($gala, $board['pointsTable']),
            'refreshedAt' => $this->liveProp($gala, $board['refreshedAt']),
        ]);
    }

    private function liveProp(Gala $gala, mixed $value): LiveProp
    {
        return Inertia::live($value, on: new EventResultsPublished($gala));
    }
}
