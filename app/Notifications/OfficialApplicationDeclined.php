<?php

namespace App\Notifications;

use App\Models\OfficialApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Turns an application down, with the reason and a way back in.
 *
 * Mail only: a declined applicant has no account, and somebody who is not
 * being admitted should not be left with a notice sitting in a bell.
 */
class OfficialApplicationDeclined extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly OfficialApplication $application) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('About your application to officiate — '.$this->application->reference)
            ->greeting('Thank you for applying')
            ->line(sprintf(
                'The technical committee has not taken your application to train as a %s forward this time.',
                $this->application->category->label(),
            ))
            ->line('Reason given: '.($this->application->review_notes ?? 'No reason was recorded.'))
            ->line('This is not the end of it. Most reasons are things that change — an age, a club affiliation, a season. You are welcome to apply again once they do.')
            ->action('Read the requirements', route('officials'))
            ->salutation('Same Waters. Brighter Futures.');
    }
}
