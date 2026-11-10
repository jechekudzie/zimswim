<?php

namespace App\Support;

/**
 * The single source of truth for what the platform can authorise.
 *
 * Permissions are global; the organisation a user holds a role in decides the
 * rows they reach, so a club administrator and a provincial administrator can
 * share a permission name and still see different swimmers.
 */
final class Permissions
{
    public const MANAGE_ORGANIZATIONS = 'organizations.manage';

    public const VIEW_ORGANIZATIONS = 'organizations.view';

    public const APPROVE_AFFILIATIONS = 'organizations.approve-affiliation';

    public const VIEW_MEMBERS = 'members.view';

    public const MANAGE_MEMBERS = 'members.manage';

    public const APPROVE_MEMBERS = 'members.approve';

    public const TRANSFER_MEMBERS = 'members.transfer';

    public const VIEW_COACHES = 'coaches.view';

    public const MANAGE_COACHES = 'coaches.manage';

    public const VIEW_OFFICIALS = 'officials.view';

    public const MANAGE_OFFICIALS = 'officials.manage';

    public const RATIFY_OFFICIAL_GRADES = 'officials.ratify';

    public const SIGN_OFF_OFFICIAL_SESSIONS = 'officials.sign-off';

    public const VIEW_VENUES = 'venues.view';

    public const MANAGE_VENUES = 'venues.manage';

    public const VIEW_GALAS = 'galas.view';

    public const MANAGE_GALAS = 'galas.manage';

    public const SANCTION_GALAS = 'galas.sanction';

    public const APPOINT_GALA_OFFICIALS = 'galas.appoint-officials';

    public const VIEW_ENTRIES = 'entries.view';

    public const MANAGE_ENTRIES = 'entries.manage';

    public const VIEW_RESULTS = 'results.view';

    public const RECORD_RESULTS = 'results.record';

    public const PUBLISH_RESULTS = 'results.publish';

    public const VIEW_RECORDS = 'records.view';

    public const RATIFY_RECORDS = 'records.ratify';

    public const VIEW_FINANCE = 'finance.view';

    public const MANAGE_FINANCE = 'finance.manage';

    public const VIEW_USERS = 'users.view';

    public const MANAGE_USERS = 'users.manage';

    public const MANAGE_ROLES = 'roles.manage';

    public const MANAGE_CONTENT = 'content.manage';

    public const VIEW_AUDIT_LOG = 'audit.view';

    public const VIEW_BOARD_DASHBOARD = 'dashboard.board';

    public const MANAGE_SETTINGS = 'settings.manage';

    /**
     * Every permission, grouped by the module it belongs to, for the roles screen.
     *
     * @return array<string, array<string, string>>
     */
    public static function grouped(): array
    {
        return [
            'Organisations' => [
                self::VIEW_ORGANIZATIONS => 'View clubs and provinces',
                self::MANAGE_ORGANIZATIONS => 'Create and edit organisations',
                self::APPROVE_AFFILIATIONS => 'Approve affiliations',
            ],
            'Membership' => [
                self::VIEW_MEMBERS => 'View swimmers',
                self::MANAGE_MEMBERS => 'Register and edit swimmers',
                self::APPROVE_MEMBERS => 'Approve registrations',
                self::TRANSFER_MEMBERS => 'Approve club transfers',
                self::VIEW_COACHES => 'View coaches',
                self::MANAGE_COACHES => 'Register and edit coaches',
            ],
            'Officials' => [
                self::VIEW_OFFICIALS => 'View the officials register',
                self::MANAGE_OFFICIALS => 'Register and edit officials',
                self::RATIFY_OFFICIAL_GRADES => 'Ratify accreditation grades',
                self::SIGN_OFF_OFFICIAL_SESSIONS => 'Sign off worked sessions',
            ],
            'Venues' => [
                self::VIEW_VENUES => 'View the venue register',
                self::MANAGE_VENUES => 'Add and edit venues',
            ],
            'Galas' => [
                self::VIEW_GALAS => 'View galas',
                self::MANAGE_GALAS => 'Request and build galas',
                self::SANCTION_GALAS => 'Sanction galas',
                self::APPOINT_GALA_OFFICIALS => 'Appoint the officials panel',
                self::VIEW_ENTRIES => 'View entries',
                self::MANAGE_ENTRIES => 'Submit and amend entries',
            ],
            'Results and records' => [
                self::VIEW_RESULTS => 'View results',
                self::RECORD_RESULTS => 'Record times and disqualifications',
                self::PUBLISH_RESULTS => 'Publish results',
                self::VIEW_RECORDS => 'View records',
                self::RATIFY_RECORDS => 'Ratify records',
            ],
            'Finance' => [
                self::VIEW_FINANCE => 'View payments and invoices',
                self::MANAGE_FINANCE => 'Reconcile payments and issue refunds',
            ],
            'Administration' => [
                self::VIEW_USERS => 'View users',
                self::MANAGE_USERS => 'Create and edit users',
                self::MANAGE_ROLES => 'Manage roles and permissions',
                self::MANAGE_CONTENT => 'Manage website content',
                self::VIEW_AUDIT_LOG => 'View the audit log',
                self::VIEW_BOARD_DASHBOARD => 'View the board dashboard',
                self::MANAGE_SETTINGS => 'Manage platform settings',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_merge(...array_map(
            fn (array $group): array => array_keys($group),
            array_values(self::grouped()),
        ));
    }
}
