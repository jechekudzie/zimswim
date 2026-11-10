<?php

namespace App\Enums;

/**
 * Common infringements from the World Aquatics swimming rule quick reference
 * sheet, so a referee records a rule rather than free text.
 */
enum DisqualificationCode: string
{
    case FalseStart = '4.4';
    case FreestyleTouch = '5.2';
    case FreestyleSubmerged = '5.3';
    case BackstrokeLeftPosition = '6.2';
    case BackstrokeHeadSubmerged = '6.3';
    case BackstrokeTurn = '6.4';
    case BackstrokeFinishNotOnBack = '6.5';
    case BreaststrokeKick = '7.1';
    case BreaststrokeNotOnBreast = '7.2';
    case BreaststrokeStrokeCycle = '7.3';
    case BreaststrokeArms = '7.4';
    case BreaststrokeLegs = '7.5';
    case BreaststrokeDownwardKick = '7.6';
    case BreaststrokeTouch = '7.7';
    case ButterflyNotOnBreast = '8.1';
    case ButterflyArms = '8.3';
    case ButterflyLegs = '8.4';
    case ButterflyTouch = '8.5';
    case ButterflyUnderwater = '8.6';
    case MedleyStrokeInFreestyle = '9.1';
    case MedleyOrder = '9.2';
    case MedleyFinish = '9.4';
    case DidNotCompleteDistance = '10.2.1';
    case LeftOwnLane = '10.2.2';
    case TurnWithoutContact = '10.2.3';
    case StoodOnBottom = '10.2.4';
    case PulledOnLaneRope = '10.2.5';
    case ObstructedAnotherSwimmer = '10.2.6';
    case EnteredWaterNotEntered = '10.2.7';
    case PaceMaking = '10.2.9';
    case RelaySwamOutOfOrder = '10.4.2';
    case RelayExchange = '10.4.4';
    case RelayLeftEarly = '10.4.5';
    case RelayMemberEnteredWater = '10.4.6';
    case FailedToLeavePool = '10.4.7';
    case UnapprovedSwimwear = '14.2';
    case Taping = '14.4';

    public function label(): string
    {
        return match ($this) {
            self::FalseStart => 'Initiated a start before the signal',
            self::FreestyleTouch => 'Did not touch the wall at the turn or finish',
            self::FreestyleSubmerged => 'Head did not break the surface by the 15m mark',
            self::BackstrokeLeftPosition => 'Left position on the back except when executing a turn',
            self::BackstrokeHeadSubmerged => 'Head did not break the surface by the 15m mark',
            self::BackstrokeTurn => 'Turn not executed in accordance with the rule',
            self::BackstrokeFinishNotOnBack => 'Did not finish the race while on the back',
            self::BreaststrokeKick => 'More than one downward butterfly kick',
            self::BreaststrokeNotOnBreast => 'Body not on the breast during the stroke',
            self::BreaststrokeStrokeCycle => 'Stroke cycle not one arm stroke to one leg kick in that order',
            self::BreaststrokeArms => 'Arm movements not simultaneous',
            self::BreaststrokeLegs => 'Leg movements not simultaneous',
            self::BreaststrokeDownwardKick => 'Downward butterfly kick during the stroke',
            self::BreaststrokeTouch => 'Did not touch with both hands simultaneously',
            self::ButterflyNotOnBreast => 'Body not on the breast during the stroke',
            self::ButterflyArms => 'Arms not brought forward simultaneously over the water',
            self::ButterflyLegs => 'Leg movements not simultaneous',
            self::ButterflyTouch => 'Did not touch with both hands simultaneously',
            self::ButterflyUnderwater => 'More than one arm pull under water following the start',
            self::MedleyStrokeInFreestyle => 'Backstroke, breaststroke or butterfly swum in the freestyle section',
            self::MedleyOrder => 'Incorrect stroke order',
            self::MedleyFinish => 'Finish of section not in accordance with the stroke concerned',
            self::DidNotCompleteDistance => 'Did not complete the whole distance',
            self::LeftOwnLane => 'Did not remain in the lane in which they started',
            self::TurnWithoutContact => 'When turning did not make contact with the end of the pool',
            self::StoodOnBottom => 'Stood on the bottom of the pool',
            self::PulledOnLaneRope => 'Pulled on the lane rope',
            self::ObstructedAnotherSwimmer => 'Obstructed another swimmer',
            self::EnteredWaterNotEntered => 'Entered the water during a race not entered in',
            self::PaceMaking => 'Device or plan used for pace-making',
            self::RelaySwamOutOfOrder => 'Relay team did not swim in the order listed',
            self::RelayExchange => 'Relay exchange did not commence from the starting platform',
            self::RelayLeftEarly => 'Feet lost contact with the platform before the team-mate touched',
            self::RelayMemberEnteredWater => 'Relay team member entered the water before all teams finished',
            self::FailedToLeavePool => 'Failed to leave the pool as soon as possible',
            self::UnapprovedSwimwear => 'Use of a non-approved device, swimsuit or adhesive substance',
            self::Taping => 'Taping of more than two fingers or toes',
        };
    }

    public function stroke(): string
    {
        return match (true) {
            str_starts_with($this->value, '5.') => 'Freestyle',
            str_starts_with($this->value, '6.') => 'Backstroke',
            str_starts_with($this->value, '7.') => 'Breaststroke',
            str_starts_with($this->value, '8.') => 'Butterfly',
            str_starts_with($this->value, '9.') => 'Medley',
            str_starts_with($this->value, '10.4') => 'Relays',
            str_starts_with($this->value, '10.') => 'The race',
            str_starts_with($this->value, '4.') => 'The start',
            default => 'General',
        };
    }

    /**
     * @return array<int, array{value: string, label: string, group: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => [
                'value' => $case->value,
                'label' => $case->value.' — '.$case->label(),
                'group' => $case->stroke(),
            ],
            self::cases(),
        );
    }
}
