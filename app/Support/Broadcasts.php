<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Broadcasting that cannot take an action down with it.
 *
 * A gala is run on whatever network the pool has. If the websocket server is
 * unreachable when the referee publishes an event, the right outcome is that
 * the watching phones fall back to their refresh a minute later, not that the
 * referee sees a 500 and wonders whether the results saved.
 */
final class Broadcasts
{
    /**
     * Dispatch an event, logging rather than raising a broadcast failure.
     *
     * Only use this for events whose entire purpose is to push an update that
     * a reader can get another way. Anything the application depends on
     * should be dispatched normally so a failure is visible.
     */
    public static function quietly(object $event): void
    {
        try {
            event($event);
        } catch (Throwable $exception) {
            self::report($event, $exception);
        }
    }

    /**
     * Dispatch an event to every listener except the one that caused it.
     *
     * The screen that typed the times is already getting the answer back in
     * its own response, so sending it a refresh as well would only make it ask
     * the server the same question twice.
     */
    public static function quietlyToOthers(object $event): void
    {
        // This is what broadcast()->toOthers() does, done here so the event is
        // dispatched by the same path as every other one and a failure is
        // caught rather than being raised from a destructor.
        if (method_exists($event, 'dontBroadcastToCurrentUser')) {
            $event->dontBroadcastToCurrentUser();
        }

        self::quietly($event);
    }

    private static function report(object $event, Throwable $exception): void
    {
        Log::warning('A live update could not be broadcast.', [
            'event' => $event::class,
            'reason' => $exception->getMessage(),
        ]);
    }
}
