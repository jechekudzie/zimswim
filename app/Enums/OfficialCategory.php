<?php

namespace App\Enums;

enum OfficialCategory: string
{
    case Timekeeper = 'timekeeper';
    case ChiefTimekeeper = 'chief_timekeeper';
    case InspectorOfTurns = 'inspector_of_turns';
    case JudgeOfStroke = 'judge_of_stroke';
    case Starter = 'starter';
    case Referee = 'referee';
    case Announcer = 'announcer';
    case ControlRoomSupervisor = 'control_room_supervisor';

    public function label(): string
    {
        return match ($this) {
            self::Timekeeper => 'Timekeeper',
            self::ChiefTimekeeper => 'Chief Timekeeper',
            self::InspectorOfTurns => 'Inspector of Turns',
            self::JudgeOfStroke => 'Judge of Stroke',
            self::Starter => 'Starter',
            self::Referee => 'Referee',
            self::Announcer => 'Announcer',
            self::ControlRoomSupervisor => 'Control Room Supervisor',
        };
    }

    /**
     * Minimum age set by the Zimbabwe Swimming certification and grading standards.
     */
    public function minimumAge(): int
    {
        return match ($this) {
            self::Timekeeper => 13,
            self::ChiefTimekeeper, self::InspectorOfTurns, self::JudgeOfStroke => 16,
            self::Starter, self::Referee, self::Announcer, self::ControlRoomSupervisor => 18,
        };
    }

    /**
     * Learner pool-deck sessions required before the category exam may be taken.
     */
    public function learnerSessionsRequired(): int
    {
        return 6;
    }

    /**
     * Categories that must already be held before this one may be started.
     *
     * @return array<int, self>
     */
    public function prerequisites(): array
    {
        return match ($this) {
            self::Timekeeper, self::Announcer, self::ControlRoomSupervisor => [],
            self::ChiefTimekeeper, self::InspectorOfTurns => [self::Timekeeper],
            self::JudgeOfStroke => [self::InspectorOfTurns],
            self::Starter => [self::ChiefTimekeeper, self::JudgeOfStroke],
            self::Referee => [self::JudgeOfStroke, self::Starter],
        };
    }

    public function hasExam(): bool
    {
        return $this !== self::Announcer;
    }

    /**
     * @return array<int, array{value: string, label: string, minimum_age: int}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
                'minimum_age' => $case->minimumAge(),
            ],
            self::cases(),
        );
    }
}
