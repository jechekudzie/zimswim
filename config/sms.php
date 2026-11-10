<?php

use App\Support\Sms\LogSmsGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Gateway
    |--------------------------------------------------------------------------
    |
    | Which SMS gateway to send through. The "log" gateway writes messages to
    | the log instead of sending them, which is the default so that nothing is
    | ever sent by accident from a development machine. Point this at your own
    | class implementing App\Contracts\SmsGateway to go live.
    |
    */

    'gateway' => env('SMS_GATEWAY', 'log'),

    'gateways' => [
        'log' => LogSmsGateway::class,
    ],

    'log_channel' => env('SMS_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Sender
    |--------------------------------------------------------------------------
    |
    | The sender id or number recipients see. Most Zimbabwean gateways require
    | this to be registered with them before it will be accepted.
    |
    */

    'from' => env('SMS_FROM', 'ZIMSWIM'),

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | A master switch. When false, notifications fall back to email only, which
    | is useful when an SMS budget runs out mid-season.
    |
    */

    'enabled' => env('SMS_ENABLED', true),

];
