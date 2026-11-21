<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\OfficialApplication;
use App\Models\TechnicalOfficial;
use App\Models\User;
use App\Notifications\Concerns\DeliversByEmailAndSms;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Password;

/**
 * The one email a newly admitted official gets.
 *
 * It replaces what used to be two notices — an account invitation and a
 * separate "you are approved" — because the second one's button could not be
 * used until the first had been read, and a person who opened them in the
 * wrong order was told to start a course by a link that bounced them to a
 * sign-in page they had no password for.
 *
 * So everything an official needs to begin travels in one message: who they
 * are on the register, what they are training towards, the exam they will
 * sit, how many sessions a referee has to sign, and a single button that
 * does the only thing they can usefully do next.
 */
class OfficialWelcome extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    public function __construct(
        public readonly OfficialApplication $application,
        public readonly TechnicalOfficial $official,
        public readonly ?Course $course,
        public readonly bool $accountIsNew,
    ) {}

    /**
     * Worth a text message: without the password link this person cannot get
     * in at all, and an email that lands in a promotions folder leaves an
     * approved official thinking nothing happened.
     */
    protected function isUrgent(): bool
    {
        return true;
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf(
                'Welcome to the officials register — %s',
                $this->official->registration_number,
            ))
            ->markdown('mail.officials.welcome', [
                'firstName' => $this->application->first_name,
                'registrationNumber' => $this->official->registration_number,
                'category' => $this->application->category->label(),
                'club' => $this->application->clubLabel(),
                'province' => $this->application->province?->name,
                'reference' => $this->application->reference,
                'course' => $this->course,
                'steps' => $this->steps(),
                'exam' => $this->exam(),
                'accountIsNew' => $this->accountIsNew,
                'actionUrl' => $this->actionUrl($notifiable),
                'actionLabel' => $this->accountIsNew ? 'Set your password' : 'Open your portal',
                'portalUrl' => route('portal'),
                'academyUrl' => $this->course === null
                    ? route('academy.index')
                    : route('academy.show', $this->course),
                'pathwayUrl' => route('officials'),
            ]);
    }

    /**
     * What has to happen, in the order it happens.
     *
     * Built here rather than in the view because the order is a rule of the
     * pathway, not a layout choice: a category with no published course is
     * taught at a clinic instead, and the Announcer sits no exam at all.
     *
     * @return array<int, array{title: string, body: string}>
     */
    public function steps(): array
    {
        $sessions = $this->sessionsRequired();

        $steps = [];

        $steps[] = $this->course === null
            ? [
                'title' => 'Wait for the next clinic',
                'body' => 'There is no online course for this category yet. Your provincial convenor will tell you when the next clinic runs, and you can start your poolside sessions before then.',
            ]
            : [
                // The title stays out of the heading because a course can be
                // called anything from "Timekeeper" to "Timekeeping, Grade
                // One", and only one of those reads as a sentence.
                'title' => 'Work through your course',
                'body' => $this->course->title.' is waiting for you in the Academy. The lessons are short, you can stop and come back, and nothing is timed until the exam.',
            ];

        if ($this->hasExam()) {
            $steps[] = [
                'title' => 'Sit the exam',
                // The figures sit in their own strip below, so this says what
                // the exam is like rather than repeating them.
                'body' => 'You can start it as soon as you have worked through the lessons. It is taken online and timed from the moment you open it, and if it does not go your way first time you can take it again.',
            ];
        }

        $steps[] = [
            'title' => sprintf('Officiate %d sessions as a learner', $sessions),
            'body' => 'Any sanctioned gala counts. You work alongside a qualified official, and the referee signs each session off in your portal afterwards. Nothing counts until it is signed, so check your tally after every gala.',
        ];

        $steps[] = [
            'title' => 'Collect your accreditation',
            'body' => $this->hasExam()
                ? 'Once the exam is passed and the sessions are signed, the technical committee ratifies your grade and your certificate is issued. You can download it from your portal.'
                : 'Once your sessions are signed, the technical committee ratifies your grade and your certificate is issued. You can download it from your portal.',
        ];

        return $steps;
    }

    /**
     * The exam in figures, or null where the category has none.
     *
     * @return array<int, array{label: string, value: string}>|null
     */
    public function exam(): ?array
    {
        if (! $this->hasExam()) {
            return null;
        }

        return [
            ['label' => 'Questions', 'value' => (string) $this->course->exam_question_count],
            ['label' => 'Minutes', 'value' => (string) $this->course->exam_minutes],
            ['label' => 'To pass', 'value' => $this->course->pass_mark.'%'],
            ['label' => 'Attempts', 'value' => (string) $this->course->max_attempts],
        ];
    }

    /**
     * @phpstan-assert-if-true !null $this->course
     */
    private function hasExam(): bool
    {
        return $this->course !== null && $this->application->category->hasExam();
    }

    /**
     * The course sets this where there is one, because a convenor can ask
     * for more than the category's floor; otherwise the category decides.
     */
    private function sessionsRequired(): int
    {
        return $this->course->practical_sessions_required
            ?? $this->application->category->learnerSessionsRequired();
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::make(sprintf(
            'ZIMSWIM: you are on the officials register as %s, training as a %s. Check your email for your welcome pack and sign-in link.',
            $this->official->registration_number,
            $this->application->category->label(),
        ));
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'accreditation',
            'title' => 'You are on the officials register as '.$this->official->registration_number,
            'message' => $this->course === null
                ? 'Training as a '.$this->application->category->label().'. Your provincial convenor will tell you when the next clinic runs.'
                : 'Training as a '.$this->application->category->label().'. Work through '.$this->course->title.', then sit the exam.',
            // The bell copy is read by somebody already signed in, so it
            // points at the work rather than at a password link.
            'url' => $this->course === null ? route('portal') : route('academy.show', $this->course),
        ];
    }

    /**
     * The one thing this person can usefully do next.
     *
     * A brand-new account has a password nobody knows, so the button sets
     * one. Somebody who was already a parent or a coach here keeps the
     * password they have, and is sent straight to the portal instead.
     */
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
