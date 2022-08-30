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
     * @throws Exception
     */
    public function sync(
        CompetitionConfig $competitionConfig,
        array|null $datesToSync = null
    ): void {
        $competition = $competitionConfig->getSourceCompetition();


        $viewPeriods = $this->getViewPeriodsToSync($competitionConfig, $datesToSync);
        foreach ($viewPeriods as $viewPeriod) {
            $viewPeriodGameRoundNumbers = $this->againstGameRepos->getCompetitionGameRoundNumbers(
                $competition,
                null,
                $viewPeriod->getPeriod()
            );
            // ---------- ADD --------------------- //
            foreach ($viewPeriodGameRoundNumbers as $viewPeriodGameRoundNumber) {
                // $this->syncGameRound( $competitionConfig, $viewPeriod, $gameRoundNumber );
                $gameRound = $viewPeriod->getGameRound($viewPeriodGameRoundNumber);
                if ($gameRound === null) {
                    $gameRound = new GameRound($viewPeriod, $viewPeriodGameRoundNumber);
                    $this->gameRoundRepos->save($gameRound, true);
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
                    $vpDescr = 'viewperiod "' . $viewPeriod . '"';
                    $this->logInfo('removed gameround "' . $viewPeriodGameRound->getNumber() . '" for ' . $vpDescr);
                }
            }

//            $viewPeriodGameRoundNumbers = $viewPeriodGameRounds->map(function(GameRound $gameRound): int {
//                return $gameRound->getNumber();
//            });
//            foreach( $viewPeriodGameRoundNumbers as $viewPeriodGameRoundNumber ) {
//                if( array_search($viewPeriodGameRoundNumber, $gameRoundNumbers) === false) {
//                    $gameRound = $viewPeriod->getGameRound($gameRoundNumber);
//                    $viewPeriodGameRounds->removeElement()
//                }
//            }
        }
    }

//    public function syncGameRound(
//        CompetitionConfig $competitionConfig,
//        ViewPeriod $viewPeriod,
//        int $gameRoundNumber
//    ): void {
//
//
//
//        // wanneer gameRound toevoegen: als er geen wedstrijd is met gameRoundNumber niet voorkomt in de viewPeriod
//        // wanneer gameRound verwijderen: als er geen wedstrijd is met gameRoundNumber niet voorkomt in de viewPeriod
//
//        // per viewperiode gameroundnumbers ophalen en dan kijken
//
//
//         else {
//
//        }
//
//        // ////////////////// RESCHEDULED ////////////////////////
//        $oldViewPeriod = $competitionConfig->getViewPeriodByDate($oldGameDate);
//        if ($oldViewPeriod === null || $oldViewPeriod === $viewPeriod) {
//            return;
//        }
//
//        // if no games for this gameroundnumber in oldviewperiod than remove gameroundnumber
//        $againstGames = $this->againstGameRepos->getCompetitionGames(
//            $competition,
//            [GameState::Created, GameState::InProgress, GameState::Finished],
//            $game->getGameRoundNumber(),
//            $oldViewPeriod->getPeriod()
//        );
//        if (count($againstGames) > 0) {
//            return;
//        }
//
//        $gameRound = $oldViewPeriod->getGameRound($game->getGameRoundNumber());
//        if ($gameRound === null) {
//            return;
//        }
//        $oldViewPeriod->getGameRounds()->removeElement($gameRound);
//        $this->gameRoundRepos->remove($gameRound, true);
//        $vpDescr = 'viewperiod "' . $oldViewPeriod . '"';
//        $this->logInfo('remove gameround "' . $game->getGameRoundNumber() . '" for ' . $vpDescr);
//    }

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
