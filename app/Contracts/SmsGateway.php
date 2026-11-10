<?php

namespace App\Contracts;

/**
 * The seam between the platform and whichever bulk SMS provider is in use.
 *
 * Swimming in Zimbabwe reaches a lot of families more reliably by text than by
 * email, so SMS is treated as a first-class channel rather than an add-on. The
 * provider is deliberately not named here: a gateway is a class and three
 * config lines, so one can be swapped without touching a notification.
 */
interface SmsGateway
{
    /**
     * Send one message. Implementations should throw on a hard failure so the
     * queued notification is retried.
     *
     * @param  string  $to  a number in international form, e.g. +263771234567
     */
    public function send(string $to, string $message): void;
}
