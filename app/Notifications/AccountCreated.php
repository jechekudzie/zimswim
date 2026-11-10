<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * Sent when an administrator opens an account for somebody.
 *
 * The account is created with a password nobody knows, so without this the
 * person is never told it exists. It carries a reset link rather than a
 * password: one click sets their own, and nothing secret travels by email.
 */
class AccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $organizationName,
        public readonly string $roleLabel,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $token = Password::broker()->createToken($notifiable);

        return (new MailMessage)
            ->subject('Your Zimbabwe Swimming account is ready')
            ->greeting('Welcome to Zimbabwe Swimming')
            ->line(sprintf(
                'An account has been opened for you at %s as %s.',
                $this->organizationName,
                $this->roleLabel,
            ))
            ->line('Set a password to get in. The link below is good for sixty minutes; after that, use "Forgot your password?" on the sign-in page and we will send another.')
            ->action('Set your password', route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]))
            ->line('You will see what your role allows and the swimmers, galas and records that belong to your organisation and the ones beneath it.')
            ->salutation('Same Waters. Brighter Futures.');
    }
}
