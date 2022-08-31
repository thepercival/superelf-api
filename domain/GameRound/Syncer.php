<?php

declare(strict_types=1);

namespace SuperElf\GameRound;

use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use Sports\Game\Against\Repository as AgainstGameRepository;
use SuperElf\CompetitionConfig;
use SuperElf\GameRound;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Periods\ViewPeriod;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Points\Calculator as PointsCalculator;
use SuperElf\Points\Creator as PointsCreator;

class Syncer
{
    protected LoggerInterface|null $logger = null;

    public function __construct(
        protected AgainstGameRepository $againstGameRepos,
        protected GameRoundRepository $gameRoundRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected PointsCreator $pointsCreator,
        protected PointsCalculator $pointsCalculator
    ) {
    }

    /**
     * @param CompetitionConfig $competitionConfig
     * @param list<DateTimeImmutable>|null $datesToSync
     * @return list<int>
     * @throws Exception
     */
    public function sync(
        CompetitionConfig $competitionConfig,
        array|null $datesToSync = null
    ): array {
        $competition = $competitionConfig->getSourceCompetition();

        $changedGameRoundNumbers = [];

        $viewPeriods = $this->getViewPeriodsToSync($competitionConfig, $datesToSync);
        foreach ($viewPeriods as $viewPeriod) {
            $viewPeriodGameRoundNumbers = $this->againstGameRepos->getCompetitionGameRoundNumbers(
                $competition,
                null,
                $viewPeriod->getPeriod()
            );
            // ---------- ADD --------------------- //
            foreach ($viewPeriodGameRoundNumbers as $viewPeriodGameRoundNumber) {
                if ($viewPeriod->getGameRound($viewPeriodGameRoundNumber) === null) {
                    $newGameRound = new GameRound($viewPeriod, $viewPeriodGameRoundNumber);
                    $this->gameRoundRepos->save($newGameRound, true);
                    $changedGameRoundNumbers[] = $newGameRound->getNumber();
                    $vpDescr = 'viewperiod "' . $viewPeriod . '"';
                    $this->logInfo('add gameround "' . $viewPeriodGameRoundNumber . '" for ' . $vpDescr);
                }
            }

            // ---------- REMOVE --------------------- //
            $viewPeriodGameRounds = $viewPeriod->getGameRounds();
            foreach ($viewPeriodGameRounds as $viewPeriodGameRound) {
                if (array_search($viewPeriodGameRound->getNumber(), $viewPeriodGameRoundNumbers) === false) {
                    $viewPeriodGameRounds->removeElement($viewPeriodGameRound);
                    $this->gameRoundRepos->remove($viewPeriodGameRound);
                    $changedGameRoundNumbers[] = $viewPeriodGameRound->getNumber();
                    $vpDescr = 'viewperiod "' . $viewPeriod . '"';
                    $this->logInfo('removed gameround "' . $viewPeriodGameRound->getNumber() . '" for ' . $vpDescr);
                }
            }
        }
        return array_values(array_unique($changedGameRoundNumbers));
    }

    /**
     * @param CompetitionConfig $competitionConfig
     * @param list<DateTimeImmutable>|null $datesToSync
     * @return list<ViewPeriod>
     */
    public function getViewPeriodsToSync(
        CompetitionConfig $competitionConfig,
        array|null $datesToSync = null
    ): array {
        $createAndJoin = $competitionConfig->getCreateAndJoinPeriod();
        $assembleViewPeriod = $competitionConfig->getAssemblePeriod()->getViewPeriod();
        $transferViewPeriod = $competitionConfig->getTransferPeriod()->getViewPeriod();
        $viewPeriods = [$createAndJoin, $assembleViewPeriod, $transferViewPeriod];
        if ($datesToSync === null) {
            return $viewPeriods;
        }
        return array_values(
            array_filter($viewPeriods, function (ViewPeriod $viewPeriod) use ($datesToSync): bool {
                foreach ($datesToSync as $dateToSync) {
                    if ($viewPeriod->contains($dateToSync)) {
                        return true;
                    };
                }
                return false;
            })
        );
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function logInfo(string $info): void
    {
        if ($this->logger === null) {
            return;
        }
        $this->logger->info($info);
    }
}
