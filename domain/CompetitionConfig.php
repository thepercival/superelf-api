<?php

declare(strict_types=1);

namespace SuperElf;

use League\Period\Period;
use Sports\Competition;
use Sports\Season;
use SportsHelpers\Identifiable;
use SuperElf\Periods\AssemblePeriod as AssemblePeriod;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use SuperElf\Score\LinePoints as LineScorePoints;
use SuperElf\Score\Points as ScorePoints;

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

    /**
     * @return list<ScorePoints>
     */
    public function getScorePoints(): array
    {
        return $this->points->getScorePoints();
    }

    /**
     * @return list<LineScorePoints>
     */
    public function getLineScorePoints(): array
    {
        return $this->points->getLineScorePoints();
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

    public function updateAssemblePeriod(Period $period): void
    {
        $this->createAndJoinPeriod->setEndDateTime($period->getEndDate());
        $this->assemblePeriod->setStartDateTime($period->getStartDate());
        $this->assemblePeriod->setEndDateTime($period->getEndDate());
        $this->assemblePeriod->getViewPeriod()->setStartDateTime($period->getEndDate());
    }

    public function getTransferPeriod(): TransferPeriod
    {
        return $this->transferPeriod;
    }

    public function updateTransferPeriod(Period $period): void
    {
        $this->assemblePeriod->getViewPeriod()->setEndDateTime($period->getStartDate());
        $this->transferPeriod->setStartDateTime($period->getStartDate());
        $this->transferPeriod->setEndDateTime($period->getEndDate());
        $this->transferPeriod->getViewPeriod()->setStartDateTime($period->getStartDate());
    }

    /**
     * @param Period|null $period = null
     * @return  list<ViewPeriod>
     */
    public function getViewPeriods(Period|null $period = null): array
    {
        $periods = [
            $this->getCreateAndJoinPeriod(),
            $this->getAssemblePeriod()->getViewPeriod(),
            $this->getTransferPeriod()->getViewPeriod()
        ];
        if ($period === null) {
            return $periods;
        }
        return array_values(
            array_filter($periods, fn(ViewPeriod $periodIt): bool => $periodIt->getPeriod()->overlaps($period))
        );
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
