<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Sport;

enum FootballScore: string
{
    case WinResult = 'winresult';
    case DrawResult = 'drawresult';
    case Goal = 'goal';
    case Assist = 'assist';
    case PenaltyGoal = 'penaltygoal';
    case OwnGoal = 'owngoal';
    case CleanSheet = 'cleansheet';
    case SpottySheet = 'spottysheet';
    case YellowCard = 'yellowcard';
    case RedCard = 'redcard';

    public static function fromGoal(bool $penalty, bool $own): self
    {
        if( $penalty ) {
            return self::PenaltyGoal;
        } elseif( $own ){
            return self::OwnGoal;
        }
        return self::Goal;
    }

    public static function fromCard(int $type): self
    {
        if( $type === Sport::WARNING ) {
            return self::YellowCard;
        }
        elseif( $type === Sport::SENDOFF ) {
            return self::RedCard;
        }
        return throw new \Exception('unknown cardtype', E_ERROR);
    }


}

// onderste twee vanuit competitionConfig mee laten komen!

//export interface ScorePoints {
//score: FootballScore; /* | OtherSportScore */
//points: number;
//}
//
//export interface LineScorePoints extends ScorePoints {
//line: FootballLine
//}