<?php

namespace App\Console\Commands;

use App\Enums\OrganizationType;
use App\Models\Gala;
use App\Models\Organization;
use App\Notifications\EntryDeadlineApproaching;
use App\Support\NoticeRecipients;
use App\Support\Permissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Reminds clubs that entries are about to close.
 *
 * Reminders go out at fixed distances from the deadline rather than every day,
 * so a club gets a useful nudge instead of a daily email it learns to ignore.
 */
class SendEntryDeadlineReminders extends Command
{
    /**
     * Days before the deadline a reminder is sent.
     *
     * @var array<int, int>
     */
    private const MILESTONES = [14, 7, 2];

    protected $signature = 'zimswim:entry-reminders
                            {--days= : Send only for this many days out}
                            {--dry-run : List who would be told, without sending}';

    protected $description = 'Remind clubs that gala entries are closing';

    public function handle(): int
    {
        $milestones = $this->option('days') !== null
            ? [(int) $this->option('days')]
            : self::MILESTONES;

        $clubs = Organization::query()
            ->whereIn('type', [OrganizationType::Club, OrganizationType::Province])
            ->active()
            ->get();

        $sent = 0;

        foreach ($milestones as $days) {
            $galas = Gala::query()
                ->where('status', 'entries_open')
                ->whereNotNull('entries_close_at')
                ->whereDate('entries_close_at', now()->addDays($days)->toDateString())
                ->with('venue')
                ->get();

            foreach ($galas as $gala) {
                foreach ($clubs as $club) {
                    $recipients = NoticeRecipients::at($club, Permissions::MANAGE_ENTRIES);

                    if ($recipients->isEmpty()) {
                        continue;
                    }

                    $this->line(sprintf(
                        '%s · %s · %d %s · %d %s',
                        $gala->name,
                        $club->name,
                        $days,
                        $days === 1 ? 'day' : 'days',
                        $recipients->count(),
                        $recipients->count() === 1 ? 'recipient' : 'recipients',
                    ));

                    if (! $this->option('dry-run')) {
                        Notification::send($recipients, new EntryDeadlineApproaching($gala, $days));
                    }

                    $sent += $recipients->count();
                }
            }
        }

        $this->info(($this->option('dry-run') ? 'Would notify ' : 'Notified ').$sent.'.');

        return self::SUCCESS;
    }
}
