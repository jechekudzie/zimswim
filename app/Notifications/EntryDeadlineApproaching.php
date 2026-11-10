<?php

namespace App\Notifications;

use App\Models\Gala;
use App\Notifications\Concerns\DeliversByEmailAndSms;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the people who lodge entries, a set number of days before entries
 * close.
 */
class EntryDeadlineApproaching extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    public function __construct(
        public readonly Gala $gala,
        public readonly int $daysLeft,
    ) {}

    protected function isUrgent(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $closes = $this->gala->entries_close_at;

        return (new MailMessage)
            ->subject(sprintf(
                'Entries for %s close in %d %s',
                $this->gala->name,
                $this->daysLeft,
                $this->daysLeft === 1 ? 'day' : 'days',
            ))
            ->greeting('Entries are closing')
            ->line(sprintf(
                'Entries for %s close on %s.',
                $this->gala->name,
                $closes?->format('l j F \a\t g:ia') ?? 'the published deadline',
            ))
            ->line(sprintf(
                'The gala is at %s from %s.',
                $this->gala->venueName() ?? 'a venue to be confirmed',
                $this->gala->starts_on->format('j F Y'),
            ))
            ->action('Review your entries', route('admin.galas.entries.index', $this->gala))
            ->line('Entries received after the deadline attract the late entry fee, and a swimmer entered but absent attracts the no-show fine.');
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'entries',
            'title' => sprintf(
                'Entries for %s close in %d %s',
                $this->gala->name,
                $this->daysLeft,
                $this->daysLeft === 1 ? 'day' : 'days',
            ),
            'message' => 'A late entry attracts the late fee, and a swimmer entered but absent attracts the no-show fine.',
            'url' => route('admin.galas.entries.index', $this->gala),
        ];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::make(sprintf(
            'ZIMSWIM: entries for %s close in %d %s. Lodge them at %s',
            $this->gala->name,
            $this->daysLeft,
            $this->daysLeft === 1 ? 'day' : 'days',
            config('app.url'),
        ));
    }
}
