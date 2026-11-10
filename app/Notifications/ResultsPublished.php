<?php

namespace App\Notifications;

use App\Models\Gala;
use App\Notifications\Concerns\DeliversByEmailAndSms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when an event's results go up on the public site.
 */
class ResultsPublished extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    public function __construct(
        public readonly Gala $gala,
        public readonly string $eventTitle,
        public readonly int $eventNumber,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf('Results published: event %d, %s', $this->eventNumber, $this->eventTitle))
            ->greeting('Results are up')
            ->line(sprintf(
                'Event %d, %s, at %s has been published.',
                $this->eventNumber,
                $this->eventTitle,
                $this->gala->name,
            ))
            ->action('View the results', route('results.show', $this->gala))
            ->line('A protest against a disqualification must reach the referee within 30 minutes of the result being announced.');
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'results',
            'title' => sprintf('Results up: event %d, %s', $this->eventNumber, $this->eventTitle),
            'message' => $this->gala->name.'. A protest must reach the referee within 30 minutes.',
            'url' => route('results.show', $this->gala),
        ];
    }
}
