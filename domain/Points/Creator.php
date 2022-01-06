<?php

declare(strict_types=1);

namespace SuperElf\Points;

use Sports\Season;
use SuperElf\Defaults;
use SuperElf\Points;
use SuperElf\Points\Repository as PointsRepository;

class Creator
{
    public function __construct(protected PointsRepository $pointsRepos)
    {
    }

    public function get(Season $season): Points
    {
        $points = $this->pointsRepos->findOneBy(["season" => $season ]);
        if ($points !== null) {
            return $points;
        }
        $points = new Points(
            $season,
            Defaults::POINTS_WIN,
            Defaults::POINTS_DRAW,
            Defaults::GOAL_GOALKEEPER,
            Defaults::GOAL_DEFENDER,
            Defaults::GOAL_MIDFIELDER,
            Defaults::GOAL_FORWARD,
            Defaults::ASSIST_GOALKEEPER,
            Defaults::ASSIST_DEFENDER,
            Defaults::ASSIST_MIDFIELDER,
            Defaults::ASSIST_FORWARD,
            Defaults::GOAL_PENALTY,
            Defaults::GOAL_OWN,
            Defaults::CLEAN_SHEET_GOALKEEPER,
            Defaults::CLEAN_SHEET_DEFENDER,
            Defaults::SPOTTY_SHEET_GOALKEEPER,
            Defaults::SPOTTY_SHEET_DEFENDER,
            Defaults::CARD_YELLOW,
            Defaults::CARD_RED
        );
        $this->pointsRepos->save($points);
        return $points;
    }
}
