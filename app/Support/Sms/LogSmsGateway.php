<?php

namespace App\Support\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Log\LogManager;

/**
 * The default gateway: writes the message to the log instead of sending it.
 *
 * This keeps every notification path exercised in development and in tests
 * without a provider account, and makes it obvious in production if nobody has
 * configured a real gateway yet.
 */
class LogSmsGateway implements SmsGateway
{
    public function __construct(private readonly LogManager $log) {}

    public function send(string $to, string $message): void
    {
        $this->log->channel(config('sms.log_channel'))->info('SMS not sent: no gateway configured.', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}
