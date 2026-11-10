<?php

namespace App\Notifications;

use App\Models\Gala;
use App\Notifications\Concerns\DeliversByEmailAndSms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the host once the board has sanctioned their gala, which is the
 * point at which it may appear on the calendar and take entries.
 */
class GalaSanctioned extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    public function __construct(public readonly Gala $gala) {}

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->gala->name.' has been sanctioned')
            ->greeting('Your gala is sanctioned')
            ->line(sprintf(
                '%s on %s at %s has been sanctioned by %s.',
                $this->gala->name,
                $this->gala->starts_on->format('j F Y'),
                $this->gala->venueName() ?? 'the venue you nominated',
                $this->gala->sanctioningBodyName() ?? 'Zimbabwe Swimming',
            ))
            ->line('It is now on the public calendar. Open entries when your programme is final.');

        if ($this->gala->sanction_notes !== null) {
            $message->line('Note from the board: '.$this->gala->sanction_notes);
        }

        return $message->action('Open the gala', route('admin.galas.show', $this->gala));
    }
}
