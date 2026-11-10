<?php

namespace App\Events;

use App\Models\Gala;
use App\Models\GalaEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * An event's results have been released to the public.
 *
 * This is what makes the public board live: the referee signs an event off and
 * every phone watching that gala has the new times before the swimmers are out
 * of the water. It broadcasts now rather than through the queue, because a
 * result that arrives a worker-cycle late is no longer live, and the board is
 * public so there is no per-viewer work to push onto a worker.
 */
class EventResultsPublished implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Gala $gala,
        public ?GalaEvent $event = null,
    ) {}

    /**
     * The crowd is not signed in, so the board listens on a public channel
     * named after the gala. Nothing on it is private: it is the same data the
     * results page serves to anyone who asks.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel(self::channelFor($this->gala))];
    }

    public function broadcastAs(): string
    {
        return 'results.published';
    }

    /**
     * Say what happened, and let each page come and read the new board.
     *
     * Inertia can carry new values with a broadcast, which would spare every
     * watching page a request. It is not used here: the board grows with the
     * meet, and measured against this programme it passes the 10KB the
     * websocket protocol allows a message after about three published events.
     * An oversized message is rejected outright, so that route would work at
     * the start of a gala and then quietly stop updating the board.
     *
     * The burst of readers that arrives on this notice is answered from one
     * reading of the board instead, which is {@see ResultsBoard}.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['event_number' => $this->event?->event_number];
    }

    public static function channelFor(Gala $gala): string
    {
        return 'galas.'.$gala->slug;
    }
}
