<?php

use App\Events\ResultsRecorded;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, string $id): bool {
    return (int) $user->getKey() === (int) $id;
});

/**
 * Times being typed in on the day, before the referee has released them.
 *
 * The public board needs no authorisation because published results are
 * public, but a swim that has not been signed off is not, so this channel is
 * open only to the people who may already open the gala's results screens.
 */
Broadcast::channel('galas.{slug}.recording', function (User $user, string $slug): bool {
    return ResultsRecorded::canListen($user, $slug);
});
