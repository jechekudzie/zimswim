<?php

namespace App\Notifications;

use App\Models\SwimRecord;
use App\Notifications\Concerns\DeliversByEmailAndSms;
use App\Notifications\Messages\SmsMessage;
use App\Support\SwimTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a proposed record is ratified, which is the moment it becomes the
 * standing national record.
 */
class RecordRatified extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    public function __construct(public readonly SwimRecord $record) {}

    protected function isUrgent(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('National record ratified: '.$this->describe())
            ->greeting('A record has been ratified')
            ->line($this->describe().' is now the standing record.')
            ->action('View the records', route('records'))
            ->line('Congratulations to the swimmer, the coach and the club.');
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'record',
            'title' => 'National record ratified',
            'message' => $this->describe().' is now the standing record.',
            'url' => route('records'),
        ];
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::make('ZIMSWIM: record ratified — '.$this->describe());
    }

    private function describe(): string
    {
        return sprintf(
            '%s, %dm %s %s, %s',
            $this->record->holder_name,
            $this->record->distance,
            $this->record->stroke->label(),
            $this->record->course->value,
            SwimTime::format($this->record->time_ms),
        );
    }
}
