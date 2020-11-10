<?php


namespace SuperElf\ScoreUnit;


use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\ScoreUnit;

class Category
{
    public const CATEGORY_POINTS = 1;
    public const CATEGORY_GOALS = 2;
    public const CATEGORY_ASSISTS = 4;
    public const CATEGORY_GOALS_PENALTY = 8;
    public const CATEGORY_GOALS_OWN = 16;
    public const CATEGORY_SHEET_CLEAN = 32;
    public const CATEGORY_SHEET_SPOTTY = 64;
    public const CATEGORY_CARDS = 128;

    public const CATEGORY_ALL = 255;

    protected int $id;
    protected int $scoreUnitIds;

    public function __construct( int $id, int $scoreUnitIds)
    {
        $this->id = $id;
        $this->scoreUnitIds = $scoreUnitIds;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getScoreUnitIds(): int {
        return $this->scoreUnitIds;
    }
}