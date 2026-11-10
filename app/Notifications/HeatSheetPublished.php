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
 * Sent once a gala has been seeded, so coaches know their swimmers' heats and
 * lanes before they travel.
 */
class HeatSheetPublished extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    public function __construct(
        public readonly Gala $gala,
        public readonly int $events,
    ) {}

    protected function isUrgent(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Heat sheet published for '.$this->gala->name)
            ->greeting('The heat sheet is out')
            ->line(sprintf(
                '%d %s for %s have been seeded. Heats and lanes are now available.',
                $this->events,
                $this->events === 1 ? 'event' : 'events',
                $this->gala->name,
            ))
            ->action('View the heat sheet', route('admin.galas.heat-sheet', $this->gala))
            ->line('Check your swimmers before the team leaders\' meeting. Corrections after seeding are at the referee\'s discretion.');
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'heats',
            'title' => 'Heat sheet published for '.$this->gala->name,
            'message' => sprintf(
                '%d %s seeded. Check your swimmers before the team leaders\' meeting.',
                $this->events,
                $this->events === 1 ? 'event' : 'events',
            ),
            'url' => route('admin.galas.heat-sheet', $this->gala),
        ];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::make(sprintf(
            'ZIMSWIM: heat sheet for %s is published. Check heats and lanes at %s',
            $this->gala->name,
            config('app.url'),
        ));
    }
}
