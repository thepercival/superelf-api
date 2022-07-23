<?php

declare(strict_types=1);

namespace SuperElf;

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