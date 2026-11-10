<?php

use App\Contracts\SmsGateway;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Notifications\Notification;

/**
 * A fake gateway that remembers what it was asked to send.
 */
class RecordingGateway implements SmsGateway
{
    /** @var array<int, array{to: string, message: string}> */
    public array $sent = [];

    public function send(string $to, string $message): void
    {
        $this->sent[] = ['to' => $to, 'message' => $message];
    }
}

class TestSmsNotification extends Notification
{
    public function __construct(private readonly string $body = 'Hello') {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::make($this->body);
    }
}

/**
 * A notifiable whose number is whatever the test says it is.
 */
class FakeNotifiable
{
    public function __construct(private readonly ?string $number) {}

    public function routeNotificationFor(string $channel, mixed $notification = null): ?string
    {
        return $channel === 'sms' ? $this->number : null;
    }
}

it('normalises Zimbabwean numbers to international form', function (string $given, string $expected): void {
    $gateway = new RecordingGateway;

    (new SmsChannel($gateway))->send(new FakeNotifiable($given), new TestSmsNotification);

    expect($gateway->sent[0]['to'])->toBe($expected);
})->with([
    'local leading zero' => ['0771234567', '+263771234567'],
    'already international' => ['+263771234567', '+263771234567'],
    'country code without plus' => ['263771234567', '+263771234567'],
    'double zero prefix' => ['00263771234567', '+263771234567'],
    'spaces and dashes' => ['077 123-4567', '+263771234567'],
]);

it('skips a notifiable with no usable number', function (?string $given): void {
    $gateway = new RecordingGateway;

    (new SmsChannel($gateway))->send(new FakeNotifiable($given), new TestSmsNotification);

    expect($gateway->sent)->toBeEmpty();
})->with([
    'null' => [null],
    'empty' => [''],
    'too short to be a mobile' => ['12345'],
]);

it('trims a long message to one segment', function (): void {
    $gateway = new RecordingGateway;
    $long = str_repeat('Zimbabwe Swimming entries close soon. ', 10);

    (new SmsChannel($gateway))->send(
        new FakeNotifiable('0771234567'),
        new TestSmsNotification($long),
    );

    expect(strlen($gateway->sent[0]['message']))->toBeLessThanOrEqual(160);
});

it('collapses whitespace so a wrapped message does not waste characters', function (): void {
    $gateway = new RecordingGateway;

    (new SmsChannel($gateway))->send(
        new FakeNotifiable('0771234567'),
        new TestSmsNotification("Entries\n\n  close   today"),
    );

    expect($gateway->sent[0]['message'])->toBe('Entries close today');
});
