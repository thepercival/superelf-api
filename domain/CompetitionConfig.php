<?php

declare(strict_types=1);

namespace SuperElf;

use Sports\Competition;
use Sports\Season;
use SportsHelpers\Identifiable;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\View as ViewPeriod;

class CompetitionConfig extends Identifiable
{
    /**
     * @param Competition $sourceCompetition
     * @param Points $points
     * @param ViewPeriod $createAndJoinPeriod
     * @param AssemblePeriod $assemblePeriod
     * @param TransferPeriod $transferPeriod
     */
    public function __construct(
        protected Competition $sourceCompetition,
        protected Points $points,
        protected ViewPeriod $createAndJoinPeriod,
        protected AssemblePeriod $assemblePeriod,
        protected TransferPeriod $transferPeriod
    ) {
//        $this->pools = new ArrayCollection();
    }

    public function getSeason(): Season
    {
        return $this->getSourceCompetition()->getSeason();
    }

    public function getSourceCompetition(): Competition
    {
        return $this->sourceCompetition;
    }

//    public function getSourceCompetitionId(): int
//    {
//        return (int)$this->sourceCompetition->getId();
//    }

    public function getPoints(): Points
    {
        return $this->points;
    }

    public function getCreateAndJoinPeriod(): ViewPeriod
    {
        return $this->createAndJoinPeriod;
    }

    public function isInAssembleOrTransferPeriod(): bool
    {
        return $this->getAssemblePeriod()->contains() || $this->getTransferPeriod()->contains();
    }

    public function getAssemblePeriod(): AssemblePeriod
    {
        return $this->assemblePeriod;
    }

    public function getTransferPeriod(): TransferPeriod
    {
        return $this->transferPeriod;
    }

    /**
     * @return list<ViewPeriod>
     */
    public function getViewPeriods(): array
    {
        return [
            $this->getCreateAndJoinPeriod(),
            $this->getAssemblePeriod()->getViewPeriod(),
            $this->getTransferPeriod()->getViewPeriod()
        ];
    }

    public function getViewPeriodByDate(\DateTimeImmutable $dateTime): ViewPeriod|null
    {
        $filtered = array_filter($this->getViewPeriods(), function (ViewPeriod $viewPeriod) use ($dateTime): bool {
            return $viewPeriod->getPeriod()->contains($dateTime);
        });
        $viewPeriod = reset($filtered);
        return $viewPeriod === false ? null : $viewPeriod;
    }

//    /**
//     * @return Collection<int|string, Pool>
//     */
//    public function getPools(): Collection
//    {
//        return $this->pools;
//    }


//    /**
//     * @return ArrayCollection<int|string, GameRoundScore>|PersistentCollection<int|string, GameRoundScore>
//     */
//    public function getScores(): ArrayCollection|PersistentCollection
//    {
//        return $this->scores;
//    }
}