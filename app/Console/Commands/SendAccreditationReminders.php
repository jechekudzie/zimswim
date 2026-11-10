<?php

namespace App\Console\Commands;

use App\Enums\AccreditationStatus;
use App\Models\OfficialAccreditation;
use App\Notifications\AccreditationExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Warns technical officials whose accreditation is about to lapse.
 *
 * Officials are the limiting factor on how many galas can be run, so the
 * warnings start a long way out and are repeated as the date closes.
 */
class SendAccreditationReminders extends Command
{
    /**
     * Days before expiry a warning is sent.
     *
     * @var array<int, int>
     */
    private const MILESTONES = [90, 30, 7];

    protected $signature = 'zimswim:accreditation-reminders
                            {--days= : Send only for this many days out}
                            {--dry-run : List who would be told, without sending}';

    protected $description = 'Warn technical officials whose accreditation is lapsing';

    public function handle(): int
    {
        $milestones = $this->option('days') !== null
            ? [(int) $this->option('days')]
            : self::MILESTONES;

        $sent = 0;

        foreach ($milestones as $days) {
            $accreditations = OfficialAccreditation::query()
                ->where('status', AccreditationStatus::Active)
                ->whereNotNull('expires_on')
                ->whereDate('expires_on', now()->addDays($days)->toDateString())
                ->with('technicalOfficial.user')
                ->get();

            foreach ($accreditations as $accreditation) {
                $official = $accreditation->technicalOfficial;
                $notification = new AccreditationExpiring($accreditation, $days);

                $this->line(sprintf(
                    '%s · %s · %d days',
                    $official->fullName(),
                    $accreditation->category->label(),
                    $days,
                ));

                if ($this->option('dry-run')) {
                    $sent++;

                    continue;
                }

                // An official with a login is notified as a user; one without
                // is notified on the contact details in the register.
                if ($official->user !== null) {
                    $official->user->notify($notification);
                    $sent++;

                    continue;
                }

                if ($official->email === null) {
                    $this->warn($official->fullName().' has no email address on file.');

                    continue;
                }

                $route = Notification::route('mail', $official->email);

                if ($official->phone !== null) {
                    $route->route('sms', $official->phone);
                }

                $route->notify($notification);

                $sent++;
            }
        }

        $this->info(($this->option('dry-run') ? 'Would notify ' : 'Notified ').$sent.'.');

        return self::SUCCESS;
    }
}
