<?php

namespace App\Notifications\Channels;

use App\Contracts\SmsGateway;
use Illuminate\Notifications\Notification;

/**
 * Delivers a notification's toSms() message through the configured gateway.
 *
 * A notifiable with no usable number is skipped silently; email will have
 * carried the same notice.
 */
class SmsChannel
{
    public function __construct(private readonly SmsGateway $gateway) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $to = $this->number($notifiable);

        if ($to === null) {
            return;
        }

        $message = $notification->toSms($notifiable);

        $this->gateway->send($to, $message->toSegment());
    }

    /**
     * Normalise a Zimbabwean number to international form. Numbers that are
     * too short to be a mobile are left alone for the gateway to reject.
     */
    private function number(object $notifiable): ?string
    {
        $raw = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('sms', null)
            : null;

        $digits = preg_replace('/[^\d+]/', '', (string) $raw);

        if ($digits === null || strlen($digits) < 9) {
            return null;
        }

        return match (true) {
            str_starts_with($digits, '+') => $digits,
            str_starts_with($digits, '00') => '+'.substr($digits, 2),
            str_starts_with($digits, '263') => '+'.$digits,
            str_starts_with($digits, '0') => '+263'.substr($digits, 1),
            default => '+'.$digits,
        };
    }
}
