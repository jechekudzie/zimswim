<?php

namespace App\Notifications;

use App\Models\OfficialAccreditation;
use App\Notifications\Concerns\DeliversByEmailAndSms;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a technical official whose accreditation is close to lapsing.
 *
 * An official whose grade lapses cannot be appointed, and a gala that cannot
 * field a full panel cannot be run, so this is sent well ahead.
 */
class AccreditationExpiring extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    public function __construct(
        public readonly OfficialAccreditation $accreditation,
        public readonly int $daysLeft,
    ) {}

    protected function isUrgent(): bool
    {
        return $this->daysLeft <= 30;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $category = $this->accreditation->category->label();

        return (new MailMessage)
            ->subject(sprintf('Your %s accreditation lapses in %d days', $category, $this->daysLeft))
            ->greeting('Time to keep your accreditation current')
            ->line(sprintf(
                'Your %s accreditation at %s grade lapses on %s.',
                $category,
                $this->accreditation->grade->label(),
                $this->accreditation->expires_on?->format('j F Y') ?? 'its recorded date',
            ))
            ->line('Working the required sessions in the season keeps it current. Once it lapses you cannot be appointed to a sanctioned gala until it is renewed.')
            ->action('View your record', route('admin.officials.show', $this->accreditation->technical_official_id));
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::make(sprintf(
            'ZIMSWIM: your %s accreditation lapses in %d days. Work a session to keep it current: %s',
            $this->accreditation->category->label(),
            $this->daysLeft,
            config('app.url'),
        ));
    }
}
