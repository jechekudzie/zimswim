<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\SmsChannel;

/**
 * Shared delivery rules for the notices the platform sends.
 *
 * Email always goes. The database copy always goes too: it is what the bell
 * in the backend reads, so a notice is still there for somebody who deleted
 * the email or never saw it. SMS goes as well for the notices that carry a
 * deadline, because a missed deadline costs a swimmer their swim, and a text
 * is the one thing most families read the same day.
 */
trait DeliversByEmailAndSms
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($this->isUrgent() && config('sms.enabled')) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    /**
     * Whether this notice is worth the cost of a text message.
     */
    protected function isUrgent(): bool
    {
        return false;
    }
}
