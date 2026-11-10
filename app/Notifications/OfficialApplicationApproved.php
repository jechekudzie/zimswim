<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\OfficialApplication;
use App\Notifications\Concerns\DeliversByEmailAndSms;
use App\Notifications\Messages\SmsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells an applicant they are on the pathway, and what to do next.
 */
class OfficialApplicationApproved extends Notification implements ShouldQueue
{
    use DeliversByEmailAndSms, Queueable;

    public function __construct(
        public readonly OfficialApplication $application,
        public readonly ?Course $course,
    ) {}

    protected function isUrgent(): bool
    {
        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('You are on the officials pathway')
            ->greeting('Welcome to the pool deck')
            ->line(sprintf(
                'The technical committee has approved your application to train as a %s. You are on the register at %s.',
                $this->application->category->label(),
                $this->application->clubLabel(),
            ));

        if ($this->course === null) {
            $message->line('There is no online course for this category yet. Your provincial convenor will tell you when the next clinic runs, and your learner sessions can start at any gala.');

            return $message->action('Open the Academy', route('academy.index'));
        }

        return $message
            ->line(sprintf(
                'You are enrolled in %s. Work through the lessons, then sit the exam — you need %d%% to pass, and you have %d attempts.',
                $this->course->title,
                $this->course->pass_mark,
                $this->course->max_attempts,
            ))
            ->line(sprintf(
                'After the exam come %d signed-off sessions poolside. Your certificate is issued once both are done.',
                $this->course->practical_sessions_required,
            ))
            ->action('Start the course', route('academy.show', $this->course));
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::make(sprintf(
            'ZIMSWIM: your application to train as a %s is approved. Sign in to start the course: %s',
            $this->application->category->label(),
            config('app.url'),
        ));
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'accreditation',
            'title' => 'You are approved to train as a '.$this->application->category->label(),
            'message' => $this->course === null
                ? 'Your provincial convenor will tell you when the next clinic runs.'
                : 'You are enrolled in '.$this->course->title.'. Work through the lessons, then sit the exam.',
            'url' => $this->course === null
                ? route('academy.index')
                : route('academy.show', $this->course),
        ];
    }
}
