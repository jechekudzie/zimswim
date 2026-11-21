<?php

namespace App\Notifications;

use App\Models\OfficialAccreditation;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Notifications\Concerns\DeliversByEmailAndSms;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * The welcome for an official a registrar put on the register by hand.
 *
 * This is the other door onto the register, and it opens on a different kind
 * of person: usually somebody who already officiates — a referee the
 * federation has had for fifteen years — being written into the system for
 * the first time, rather than a learner starting out.
 *
 * So it says nothing about lessons, exams or learner sessions. Those belong
 * to {@see OfficialWelcome}, and telling a sitting referee to go and do six
 * of them would be both wrong and insulting. What this one does is hand over
 * a login, show what the register currently says about them, and ask them to
 * check it — because the record was typed by somebody else, and the person it
 * describes is the one most likely to spot a mistake in it.
 */
class OfficialRegistered extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    public function __construct(
        public readonly TechnicalOfficial $official,
        public readonly bool $accountIsNew,
    ) {}

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf(
                'You are on the Zimbabwe Swimming officials register — %s',
                $this->official->registration_number,
            ))
            ->markdown('mail.officials.registered', [
                'firstName' => $this->official->first_name,
                'registrationNumber' => $this->official->registration_number,
                'club' => $this->official->club->name,
                'province' => $this->official->province?->name,
                'standing' => $this->standing(),
                'accreditations' => $this->accreditations(),
                'accountIsNew' => $this->accountIsNew,
                'actionUrl' => $this->actionUrl($notifiable),
                'actionLabel' => $this->accountIsNew ? 'Set your password' : 'Open your portal',
                'portalUrl' => route('portal'),
                'pathwayUrl' => route('officials'),
            ]);
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'accreditation',
            'title' => 'You are on the officials register as '.$this->official->registration_number,
            'message' => 'Your portal shows what the register holds for you. Tell your club administrator if anything is wrong.',
            'url' => route('portal'),
        ];
    }

    /**
     * What the register says they hold today, newest first.
     *
     * Shown in the email rather than left to the portal because this is the
     * part most likely to be wrong: it was transcribed from paper, and a
     * grade or an expiry entered a year out is the kind of thing only the
     * holder notices.
     *
     * @return array<int, array{category: string, grade: string, expires: string|null}>
     */
    public function accreditations(): array
    {
        return $this->official->accreditations
            ->sortByDesc('attained_on')
            ->map(fn (OfficialAccreditation $accreditation): array => [
                'category' => $accreditation->category->label(),
                'grade' => $accreditation->grade->label(),
                'expires' => $accreditation->expires_on?->format('j F Y'),
            ])
            ->values()
            ->all();
    }

    private function standing(): string
    {
        return match ($this->official->status) {
            'learner' => 'Learner',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            default => 'Active',
        };
    }

    private function actionUrl(User $notifiable): string
    {
        if (! $this->accountIsNew) {
            return route('portal');
        }

        return route('password.reset', [
            'token' => Password::broker()->createToken($notifiable),
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
