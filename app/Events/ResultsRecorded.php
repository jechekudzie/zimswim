<?php

namespace App\Events;

use App\Models\Gala;
use App\Models\GalaEvent;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Times have been entered for an event, but not yet released to the public.
 *
 * At a real gala two or three people type times into separate tablets while a
 * fourth watches the event list. This keeps those screens in step, so nobody
 * types a lane that has already been entered or publishes an event that is
 * still being corrected. It stays on a private channel because unpublished
 * times are not public: the referee has not signed them off yet.
 */
class ResultsRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Gala $gala,
        public GalaEvent $event,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel(self::channelFor($this->gala))];
    }

    public function broadcastAs(): string
    {
        return 'results.recorded';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->event->getKey(),
            'event_number' => $this->event->event_number,
        ];
    }

    public static function channelFor(Gala $gala): string
    {
        return 'galas.'.$gala->slug.'.recording';
    }

    /**
     * May this user listen in on times being entered for this gala?
     *
     * Only the people who enter times may, which is narrower than the set who
     * may open a results screen: a swimmer holds "view results" so they can
     * look up their own swims, and that must not become a way to read times
     * the referee has not signed off yet.
     */
    public static function canListen(User $user, string $slug): bool
    {
        $gala = Gala::query()->where('slug', $slug)->first();

        return $gala !== null && $user->can('recordResults', $gala);
    }
}
