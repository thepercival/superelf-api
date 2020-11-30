<?php


namespace SuperElf\ScoreUnit;


use Doctrine\Common\Collections\ArrayCollection;
use SuperElf\ScoreUnit;

class Category
{
    public const POINTS = 1;
    public const GOALS = 2;
    public const ASSISTS = 4;
    public const GOALS_PENALTY = 8;
    public const GOALS_OWN = 16;
    public const SHEET_CLEAN = 32;
    public const SHEET_SPOTTY = 64;
    public const CARDS = 128;
    public const SUBSTITUTE = 256;

    public const FILTER = 255;
    public const ALL = 511;

    // bijhouden stats
    // en

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