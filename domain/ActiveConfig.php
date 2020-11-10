<?php

namespace SuperElf;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use League\Period\Period;
use Sports\Team;
use Sports\Sport\Formation\Line as FormationLine;

class ActiveConfig
{
    protected DateTimeImmutable $createAndJoinStart;
    protected DateTimeImmutable $createAndJoinEnd;
    protected DateTimeImmutable $joinAndChoosePlayersStart;
    protected DateTimeImmutable $joinAndChoosePlayersEnd;
    protected array $sourceCompetitions;

    public function __construct(
        Period $createAndJoinPeriod,
        Period $joinAndChoosePlayersPeriod,
        array $sourceCompetitions )
    {
        $this->createAndJoinStart = $createAndJoinPeriod->getStartDate();
        $this->createAndJoinEnd = $createAndJoinPeriod->getEndDate();
        $this->joinAndChoosePlayersStart = $joinAndChoosePlayersPeriod->getStartDate();
        $this->joinAndChoosePlayersEnd = $joinAndChoosePlayersPeriod->getEndDate();

        $this->sourceCompetitions = $sourceCompetitions;
    }

    public function getCreateAndJoinPeriod(): Period
    {
        return new Period( $this->createAndJoinStart, $this->createAndJoinEnd );
    }

    public function getJoinAndChoosePlayersPeriod(): Period
    {
        return new Period( $this->joinAndChoosePlayersStart, $this->joinAndChoosePlayersEnd );
    }

    public function getSourceCompetitions(): array
    {
        return $this->sourceCompetitions;
    }
}
