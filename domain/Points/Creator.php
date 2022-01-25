<?php

declare(strict_types=1);

namespace SuperElf\Points;

use Sports\Season;
use SuperElf\Defaults;
use SuperElf\Points;

class Creator
{
    public function __construct()
    {
    }

    public function createDefault(Season $season): Points
    {
        if (in_array($season->getName(), ['2014/2015', '2015/2016'])) {
            return new Points(
                3,
                1,
                9,
                6,
                4,
                3,
                0,
                0,
                0,
                0,
                1,
                -4,
                4,
                1,
                -4,
                -1,
                -1,
                -4
            );
        } elseif (in_array($season->getName(), ['2016/2017', '2017/2018', '2018/2019'])) {
            return new Points(
                3,
                1,
                10,
                6,
                4,
                3,
                0,
                0,
                0,
                0,
                1,
                -4,
                4,
                1,
                -4,
                -1,
                -1,
                -4
            );
        } elseif (in_array($season->getName(), ['2019/2020'])) {
            return new Points(
                3,
                1,
                10,
                6,
                4,
                3,
                4,
                3,
                2,
                1,
                1,
                -4,
                5,
                2,
                -5,
                -2,
                -1,
                -4
            );
        } elseif (in_array($season->getName(), ['2020/2021'])) {
            return new Points(
                3,
                1,
                10,
                6,
                4,
                3,
                4,
                3,
                2,
                1,
                1,
                -4,
                4,
                1,
                -4,
                -1,
                -1,
                -4
            );
        }
        return new Points(
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
    }
}
