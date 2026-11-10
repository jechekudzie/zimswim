<?php

namespace App\Notifications;

use App\Models\Season;
use App\Notifications\Concerns\DeliversByEmailAndSms;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a club when swimmers on its books are not yet registered for the
 * season, since an unregistered swimmer cannot be entered into anything.
 */
class RegistrationRenewalDue extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    /**
     * @param  array<int, string>  $swimmers
     */
    public function __construct(
        public readonly Season $season,
        public readonly string $clubName,
        public readonly array $swimmers,
    ) {}

    protected function isUrgent(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->swimmers);

        $message = (new MailMessage)
            ->subject(sprintf('%d %s not yet registered for %s', $count, $count === 1 ? 'swimmer' : 'swimmers', $this->season->name))
            ->greeting('Registrations outstanding')
            ->line(sprintf(
                '%s has %d %s on its books who are not registered for %s.',
                $this->clubName,
                $count,
                $count === 1 ? 'swimmer' : 'swimmers',
                $this->season->name,
            ))
            ->line('An unregistered swimmer cannot be entered into a sanctioned gala, and capitation must be paid before entries are accepted.');

        foreach (array_slice($this->swimmers, 0, 20) as $swimmer) {
            $message->line('• '.$swimmer);
        }

        if ($count > 20) {
            $message->line(sprintf('…and %d more.', $count - 20));
        }

        return $message->action('Register them', route('admin.members.index'));
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::make(sprintf(
            'ZIMSWIM: %d %s at %s are not registered for %s and cannot be entered. Renew at %s',
            count($this->swimmers),
            count($this->swimmers) === 1 ? 'swimmer' : 'swimmers',
            $this->clubName,
            $this->season->name,
            config('app.url'),
        ));
    }
}
