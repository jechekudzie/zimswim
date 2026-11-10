<?php

namespace App\Console\Commands;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Season;
use App\Notifications\RegistrationRenewalDue;
use App\Support\NoticeRecipients;
use App\Support\Permissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Tells each club which of its swimmers are not yet registered for the current
 * season, in one message per club rather than one per swimmer.
 */
class SendRenewalReminders extends Command
{
    protected $signature = 'zimswim:renewal-reminders
                            {--dry-run : List who would be told, without sending}';

    protected $description = 'Remind clubs about swimmers not yet registered for the season';

    public function handle(): int
    {
        $season = Season::query()->current()->first();

        if ($season === null) {
            $this->warn('No season is marked as current, so nothing was sent.');

            return self::SUCCESS;
        }

        $outstanding = Member::query()
            ->where('status', MemberStatus::Active)
            ->whereDoesntHave('registrations', fn ($query) => $query->where('season_id', $season->getKey()))
            ->get()
            ->groupBy('organization_id');

        if ($outstanding->isEmpty()) {
            $this->info('Every active swimmer is registered for '.$season->name.'.');

            return self::SUCCESS;
        }

        $clubs = Organization::query()->whereKey($outstanding->keys())->get()->keyBy('id');
        $sent = 0;

        foreach ($outstanding as $organizationId => $members) {
            $club = $clubs->get($organizationId);

            if ($club === null) {
                continue;
            }

            // Fall back up the tree, because a club with nobody to act on this
            // is exactly the club that needs chasing.
            $recipients = NoticeRecipients::atOrAbove($club, Permissions::MANAGE_MEMBERS);

            if ($recipients->isEmpty()) {
                $this->warn($club->name.' has '.$members->count().' unregistered swimmers and nobody to notify.');

                continue;
            }

            $this->line(sprintf(
                '%s · %d unregistered · %d %s',
                $club->name,
                $members->count(),
                $recipients->count(),
                $recipients->count() === 1 ? 'recipient' : 'recipients',
            ));

            if (! $this->option('dry-run')) {
                Notification::send($recipients, new RegistrationRenewalDue(
                    $season,
                    $club->name,
                    $members->map(fn (Member $member): string => $member->fullName())->values()->all(),
                ));
            }

            $sent += $recipients->count();
        }

        $this->info(($this->option('dry-run') ? 'Would notify ' : 'Notified ').$sent.'.');

        return self::SUCCESS;
    }
}
