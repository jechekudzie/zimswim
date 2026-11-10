<?php

namespace App\Notifications;

use App\Models\OfficialApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Acknowledges an application to officiate.
 *
 * Mail only, and addressed to a bare email: the applicant has no account
 * until the committee approves them, so there is nothing to write a database
 * copy against.
 */
class OfficialApplicationReceived extends Notification implements ShouldQueue
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
            ->subject('We have your application to officiate — '.$this->application->reference)
            ->greeting('Thank you for putting your hand up')
            ->line(sprintf(
                'Your application to train as a %s is with the technical committee.',
                $this->application->category->label(),
            ))
            ->line('Your reference is '.$this->application->reference.'. Quote it if you need to ask where things stand.')
            ->line('We will write again once it has been looked at. If it is approved you will get a link to set a password, and the course for your category will be waiting for you.')
            ->action('Read the pathway again', route('officials'))
            ->salutation('Same Waters. Brighter Futures.');
    }
}
