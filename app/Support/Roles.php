<?php

namespace App\Support;

/**
 * The access levels from the ZimSwim Online proposal. Roles are configurable
 * by the system administrator; this is the starting set the platform ships with.
 */
final class Roles
{
    public const SYSTEM_ADMINISTRATOR = 'system-administrator';

    public const BOARD_MEMBER = 'board-member';

    public const TECHNICAL_COMMITTEE = 'technical-committee';

    public const FINANCE = 'finance';

    public const PROVINCIAL_ADMINISTRATOR = 'provincial-administrator';

    public const CLUB_ADMINISTRATOR = 'club-administrator';

    public const COACH = 'coach';

    public const GALA_OFFICIAL = 'gala-official';

    public const PARENT_GUARDIAN = 'parent-guardian';

    public const SWIMMER = 'swimmer';

    /**
     * Role definitions: the label and description shown in the admin, and the
     * permissions granted.
     *
     * @return array<string, array{label: string, description: string, permissions: array<int, string>}>
     */
    public static function definitions(): array
    {
        return [
            self::SYSTEM_ADMINISTRATOR => [
                'label' => 'System administrator',
                'description' => 'ZimSwim secretariat lead and Leading Digital support. Sees everything and manages users, roles, settings, age-group rules and the audit log.',
                'permissions' => Permissions::all(),
            ],
            self::BOARD_MEMBER => [
                'label' => 'Board member',
                'description' => 'Chair, vice-chair, secretary, treasurer and conveners. Approves gala sanctions, ratifies records and approves affiliations.',
                'permissions' => [
                    Permissions::VIEW_ORGANIZATIONS,
                    Permissions::APPROVE_AFFILIATIONS,
                    Permissions::VIEW_MEMBERS,
                    Permissions::APPROVE_MEMBERS,
                    Permissions::TRANSFER_MEMBERS,
                    Permissions::VIEW_COACHES,
                    Permissions::VIEW_OFFICIALS,
                    Permissions::VIEW_VENUES,
                    Permissions::VIEW_GALAS,
                    Permissions::SANCTION_GALAS,
                    Permissions::VIEW_ENTRIES,
                    Permissions::VIEW_RESULTS,
                    Permissions::VIEW_RECORDS,
                    Permissions::RATIFY_RECORDS,
                    Permissions::VIEW_FINANCE,
                    Permissions::VIEW_USERS,
                    Permissions::VIEW_AUDIT_LOG,
                    Permissions::VIEW_BOARD_DASHBOARD,
                    Permissions::MANAGE_CONTENT,
                ],
            ],
            self::TECHNICAL_COMMITTEE => [
                'label' => 'Technical committee',
                'description' => 'Technical and officials conveners and senior referees. Approves gala programmes, appoints referees and signs off officials levels.',
                'permissions' => [
                    Permissions::VIEW_ORGANIZATIONS,
                    Permissions::VIEW_MEMBERS,
                    Permissions::VIEW_OFFICIALS,
                    Permissions::MANAGE_OFFICIALS,
                    Permissions::RATIFY_OFFICIAL_GRADES,
                    Permissions::SIGN_OFF_OFFICIAL_SESSIONS,
                    Permissions::VIEW_VENUES,
                    Permissions::VIEW_GALAS,
                    Permissions::MANAGE_GALAS,
                    Permissions::APPOINT_GALA_OFFICIALS,
                    Permissions::VIEW_ENTRIES,
                    Permissions::VIEW_RESULTS,
                    Permissions::RECORD_RESULTS,
                    Permissions::PUBLISH_RESULTS,
                    Permissions::VIEW_RECORDS,
                ],
            ],
            self::FINANCE => [
                'label' => 'Finance',
                'description' => 'Treasurer and finance officer. Reconciles payments, issues refunds and exports to accounting.',
                'permissions' => [
                    Permissions::VIEW_ORGANIZATIONS,
                    Permissions::VIEW_MEMBERS,
                    Permissions::VIEW_GALAS,
                    Permissions::VIEW_ENTRIES,
                    Permissions::VIEW_FINANCE,
                    Permissions::MANAGE_FINANCE,
                    Permissions::VIEW_AUDIT_LOG,
                ],
            ],
            self::PROVINCIAL_ADMINISTRATOR => [
                'label' => 'Provincial administrator',
                'description' => 'Provincial executive boards. Requests galas, runs provincial galas and manages venue records in their area.',
                'permissions' => [
                    Permissions::VIEW_ORGANIZATIONS,
                    Permissions::MANAGE_ORGANIZATIONS,
                    Permissions::VIEW_MEMBERS,
                    Permissions::MANAGE_MEMBERS,
                    Permissions::APPROVE_MEMBERS,
                    Permissions::VIEW_COACHES,
                    Permissions::MANAGE_COACHES,
                    Permissions::VIEW_OFFICIALS,
                    Permissions::MANAGE_OFFICIALS,
                    Permissions::VIEW_VENUES,
                    Permissions::MANAGE_VENUES,
                    Permissions::VIEW_GALAS,
                    Permissions::MANAGE_GALAS,
                    Permissions::VIEW_ENTRIES,
                    Permissions::MANAGE_ENTRIES,
                    Permissions::VIEW_RESULTS,
                    Permissions::RECORD_RESULTS,
                    Permissions::VIEW_RECORDS,
                    Permissions::VIEW_FINANCE,
                    Permissions::VIEW_USERS,
                    Permissions::MANAGE_CONTENT,
                ],
            ],
            self::CLUB_ADMINISTRATOR => [
                'label' => 'Club administrator',
                'description' => 'Club secretary or head coach. Registers and renews swimmers, submits entries and pays club invoices.',
                'permissions' => [
                    Permissions::VIEW_ORGANIZATIONS,
                    Permissions::VIEW_MEMBERS,
                    Permissions::MANAGE_MEMBERS,
                    Permissions::VIEW_COACHES,
                    Permissions::MANAGE_COACHES,
                    Permissions::VIEW_OFFICIALS,
                    Permissions::VIEW_VENUES,
                    Permissions::VIEW_GALAS,
                    Permissions::VIEW_ENTRIES,
                    Permissions::MANAGE_ENTRIES,
                    Permissions::VIEW_RESULTS,
                    Permissions::VIEW_RECORDS,
                    Permissions::VIEW_FINANCE,
                ],
            ],
            self::COACH => [
                'label' => 'Coach',
                'description' => 'Registered coaches. Sees their squad profiles and times and suggests entries.',
                'permissions' => [
                    Permissions::VIEW_MEMBERS,
                    Permissions::VIEW_GALAS,
                    Permissions::VIEW_ENTRIES,
                    Permissions::VIEW_RESULTS,
                    Permissions::VIEW_RECORDS,
                ],
            ],
            self::GALA_OFFICIAL => [
                'label' => 'Gala official',
                'description' => 'Officials appointed to a gala. Checks swimmers in, records times and disqualifications, and signs off trainee practicals.',
                'permissions' => [
                    Permissions::VIEW_GALAS,
                    Permissions::VIEW_ENTRIES,
                    Permissions::VIEW_RESULTS,
                    Permissions::RECORD_RESULTS,
                    Permissions::VIEW_OFFICIALS,
                    Permissions::SIGN_OFF_OFFICIAL_SESSIONS,
                    Permissions::VIEW_RECORDS,
                ],
            ],
            self::PARENT_GUARDIAN => [
                'label' => 'Parent or guardian',
                'description' => 'Parents of registered swimmers. Registers and consents, enters galas, pays, and takes Academy courses. Sees their own children only.',
                'permissions' => [
                    Permissions::VIEW_GALAS,
                    Permissions::VIEW_RESULTS,
                    Permissions::VIEW_RECORDS,
                ],
            ],
            self::SWIMMER => [
                'label' => 'Swimmer 18 and over',
                'description' => 'Senior swimmers managing their own profile and entries.',
                'permissions' => [
                    Permissions::VIEW_GALAS,
                    Permissions::VIEW_RESULTS,
                    Permissions::VIEW_RECORDS,
                ],
            ],
        ];
    }

    /**
     * Roles that act across the whole country rather than one organisation.
     *
     * @return array<int, string>
     */
    public static function national(): array
    {
        return [
            self::SYSTEM_ADMINISTRATOR,
            self::BOARD_MEMBER,
            self::TECHNICAL_COMMITTEE,
            self::FINANCE,
        ];
    }

    public static function label(string $role): string
    {
        return self::definitions()[$role]['label'] ?? ucfirst(str_replace('-', ' ', $role));
    }
}
