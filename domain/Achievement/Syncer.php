<?php

declare(strict_types=1);

namespace SuperElf\Achievement;

use Psr\Log\LoggerInterface;
use Sports\Competition;
use Sports\Game\State;
use Sports\Structure;
use Sports\Structure\Repository as StructureRepository;
use SuperElf\Achievement;
use SuperElf\Achievement\Badge\Repository as BadgeRepository;
use SuperElf\Achievement\Trophy\Calculator as TrophyCalculator;
use SuperElf\Achievement\Trophy\Repository as TrophyRepository;
use SuperElf\Achievement\Unviewed\Trophy as UnviewedTrophy;
use SuperElf\Achievement\Unviewed\Trophy\Repository as UnviewedTrophyRepository;
use SuperElf\CompetitionConfig;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use SuperElf\Points\Calculator as PointsCalculator;
use SuperElf\Points\Creator as PointsCreator;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;

class Syncer
{
    public function __construct(
        protected PoolRepository $poolRepos,
        protected StructureRepository $structureRepos,
        protected TrophyRepository $trophyRepos,
        protected BadgeRepository $badgeRepos,
        protected UnviewedTrophyRepository $unviewedTrophyRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected PointsCreator $pointsCreator,
        protected PointsCalculator $pointsCalculator,
        protected LoggerInterface $logger
    ) {
    }

    // voor alle poolusers
    // houdt state in progress and finished bij
    // kijk als gameroundnumber is afgelopen
    public function syncPoolAchievements(CompetitionConfig $competitionConfig): void
    {
        $allPoolsFinished = true;
        $pools = $this->poolRepos->findBy(['competitionConfig' => $competitionConfig]);
        foreach ($pools as $pool) {
            $this->logger->info('updating poolGames for "' . $pool->getName() . '" .. ');
            foreach ($pool->getCompetitions() as $poolCompetition) {
                $poolFinished = $this->updatePoolAchievements($pool, $poolCompetition);
                if( !$poolFinished ) {
                    $allPoolsFinished = false;
                }
            }
        }
        if( count($pools) > 0 && $allPoolsFinished ) {
            // update badges for $competitionConfig
        }
    }

    public function updatePoolAchievements(Pool $pool, Competition $poolCompetition): bool {
        $structure = $this->structureRepos->getStructure($poolCompetition);
        if( $structure->getSingleCategory()->getLastStructureCell()->getGamesState() !== State::Finished ) {
            return false;
        }
        $this->logger->info('updating achievements for : "' . $pool->getName() . '" and league "'. $poolCompetition->getLeague()->getName() .'"');
        $this->updatePoolTrophies($pool, $poolCompetition, $structure);
        // $this->updatePoolBadges($poolCompetition);

        return true;
    }

    public function updatePoolTrophies(Pool $pool, Competition $poolCompetition, Structure $structure): void {

        $trophyCalculator = new TrophyCalculator();
        $createDateTime = null;
        foreach([1,2] as $rank) {
            $trophies = $this->trophyRepos->findBy(['competition' => $poolCompetition, 'rank' => $rank]);
            if( $createDateTime === null ) {
                $createDateTime = $this->getCreateDateTime($trophies);
            }
            foreach( $trophies as $trophy) {
                $this->logger->info('   trophy remove : ' . $trophy);
                $this->trophyRepos->remove($trophy, true);
            }
            $poolUsers = $trophyCalculator->getPoolUsersByRank($pool, $poolCompetition, $rank, $structure);
            foreach( $poolUsers as $poolUser) {
                $trophy = new Trophy($poolCompetition, $rank, $poolUser, $createDateTime);
                $this->logger->info('   trophy add : ' . $trophy);
                $this->trophyRepos->save($trophy, true);
                foreach( $pool->getUsers() as $poolUser) {
                    $this->unviewedTrophyRepos->save(new UnviewedTrophy($poolUser, $trophy), true);
                }
            }
        }
    }

    /**
     * @param list<Achievement> $achievements
     * @return \DateTimeImmutable
     */
    private function getCreateDateTime(array $achievements): \DateTimeImmutable {
        foreach( $achievements as $achievement) {
            return $achievement->getCreateDateTime();
        }
        return new \DateTimeImmutable();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

//
//    /**
//     * @param CompetitionConfig $competitionConfig
//     * @param int $gameRoundNumber
//     * @return list<AssemblePeriod|TransferPeriod>
//     */
//    protected function getValidEditPeriods(CompetitionConfig $competitionConfig): array
//    {
//        $editPeriods = [];
//        $assemblePeriod = $competitionConfig->getAssemblePeriod();
//        if ($assemblePeriod->getViewPeriod()->getGameRound($gameRoundNumber) !== null) {
//            $editPeriods[] = $assemblePeriod;
//        }
//        $transferPeriod = $competitionConfig->getTransferPeriod();
//        if ($transferPeriod->getViewPeriod()->getGameRound($gameRoundNumber) !== null) {
//            $editPeriods[] = $transferPeriod;
//        }
//        return $editPeriods;
//    }
//
//    public function updatePoolCompetitionGames(
//        CompetitionConfig $competitionConfig,
//        AssemblePeriod|TransferPeriod $editPeriod,
//        Pool $pool,
//        Competition $poolCompetition,
//        int $gameRoundNumber
//    ): void {
//        $competitors = $pool->getCompetitors($poolCompetition);
//        $startLocationMap = new StartLocationMap($competitors);
//        $competition = $competitionConfig->getSourceCompetition();
//
//        $togetherGames = $this->getCompetitionTogetherGames($poolCompetition, $gameRoundNumber);
//        foreach ($togetherGames as $togetherGame) {
//            foreach ($togetherGame->getPlaces() as $gamePlace) {
//                // remove scores
//                $this->togetherScoreRepos->removeScores($gamePlace);
//
//                // add score
//                $competitor = null;
//                $startLocation = $gamePlace->getPlace()->getStartLocation();
//                if ($startLocation !== null) {
//                    $competitor = $startLocationMap->getCompetitor($startLocation);
//                }
//                if ($competitor !== null && $competitor instanceof PoolCompetitor) {
//                    $formation = $competitor->getPoolUser()->getFormation($editPeriod);
//                    if ($formation !== null) {
//                        $this->saveTogetherScore(
//                            $gamePlace,
//                            $gameRoundNumber,
//                            $formation,
//                            $competitionConfig->getPoints()
//                        );
//                    }
//                }
//            }
//
//            // als source finished dan game ook finished
//            $sourceGameRoundState = $this->getSourceGameRoundState($competition, $gameRoundNumber);
//            if ($sourceGameRoundState !== $togetherGame->getState()) {
//                $togetherGame->setState($sourceGameRoundState);
//                $this->togetherGameRepos->save($togetherGame, true);
//                $this->logger->info(
//                    'update gameRound ' . $gameRoundNumber . ' to state "' . $sourceGameRoundState->name
//                );
//            }
//        }
//    }
//
//    /**
//     * @param Competition $poolCompetition
//     * @param int $gameRoundNumber
//     * @return list<TogetherGame>
//     */
//    protected function getCompetitionTogetherGames(Competition $poolCompetition, int $gameRoundNumber): array
//    {
//        $togetherGames = $this->togetherGameRepos->getCompetitionGames($poolCompetition, null);
//        return array_values(
//            array_filter($togetherGames, function (TogetherGame $game) use ($gameRoundNumber): bool {
//                return count(
//                        $game->getPlaces()->filter(
//                            function (TogetherGamePlace $gamePlace) use ($gameRoundNumber): bool {
//                                return $gamePlace->getGameRoundNumber() === $gameRoundNumber;
//                            }
//                        )
//                    ) > 0;
//            })
//        );
//    }
//
//    protected function saveTogetherScore(
//        TogetherGamePlace $gamePlace,
//        int $gameRoundNumber,
//        Formation $formation,
//        Points $s11Points
//    ): void {
//        $viewPeriod = $formation->getViewPeriod();
//        $gameRound = $viewPeriod->getGameRound($gameRoundNumber);
//        if ($gameRound === null) {
//            return;
//        }
//        $points = $formation->getPoints($gameRound, $s11Points);
//        $score = new TogetherScore(
//            $gamePlace,
//            $points,
//            GamePhase::RegularTime
//        );
//        $this->togetherScoreRepos->save($score, true);
//    }
//

//
//    /**
//     * @param AgainstGame $canceledGame
//     * @param list<AgainstGame> $finished
//     * @return bool
//     */
//    protected function canceledGameInFinished(AgainstGame $canceledGame, array $finished): bool {
//        $canceledHomeGamePlaces = $canceledGame->getSidePlaces(Side::Home);
//        $canceledHomeGamePlace = array_shift($canceledHomeGamePlaces);
//        $canceledAwayGamePlaces = $canceledGame->getSidePlaces(Side::Away);
//        $canceledAwayGamePlace = array_shift($canceledAwayGamePlaces);
//        if ($canceledHomeGamePlace === null || $canceledAwayGamePlace === null) {
//            return false;
//        }
//        $canceledHomePlace = $canceledHomeGamePlace->getPlace();
//        $canceledAwayPlace = $canceledAwayGamePlace->getPlace();
//
//        foreach( $finished as $finishedGame) {
//            $finishedHomeGamePlaces = $finishedGame->getSidePlaces(Side::Home);
//            $finishedHomeGamePlace = array_shift($finishedHomeGamePlaces);
//            $finishedAwayGamePlaces = $finishedGame->getSidePlaces(Side::Away);
//            $finishedAwayGamePlace = array_shift($finishedAwayGamePlaces);
//            if ($finishedHomeGamePlace === null || $finishedAwayGamePlace === null) {
//                return false;
//            }
//            $finishedHomePlace = $finishedHomeGamePlace->getPlace();
//            $finishedAwayPlace = $finishedAwayGamePlace->getPlace();
//            if( $finishedHomePlace === $canceledHomePlace && $finishedAwayPlace === $canceledAwayPlace ) {
//                return true;
//            }
//        }
//        return false;
//    }

//    /**
//     * @param Round $round
//     * @return list<Poule>
//     */
//    protected function getPoulesWithoutGames(Round $round): array {
//        return array_values( $round->getPoules()->filter( function(Poule $poule): bool {
//            return count($poule->getGames()) === 0;
//        })->toArray() );
//    }
//
//    protected function createAndSaveAgainstScores(
//        AssemblePeriod|TransferPeriod $editPeriod,
//        AgainstGame $game,
//        StartLocationMap $startLocationMap,
//        Points $s11Points
//    ): void {
//        $homeGamePlaces = $game->getSidePlaces(Side::Home);
//        $homeGamePlace = array_shift($homeGamePlaces);
//        $awayGamePlaces = $game->getSidePlaces(Side::Away);
//        $awayGamePlace = array_shift($awayGamePlaces);
//        if ($homeGamePlace === null || $awayGamePlace === null) {
//            return;
//        }
//        $homeFormation = $this->getFormation($editPeriod, $startLocationMap, $homeGamePlace);
//        $awayFormation = $this->getFormation($editPeriod, $startLocationMap, $awayGamePlace);
//        if ($homeFormation === null || $awayFormation === null) {
//            return;
//        }
//
//        $gameRound = $editPeriod->getViewPeriod()->getGameRound($game->getGameRoundNumber());
//        if ($gameRound === null) {
//            return;
//        }
//
//        $score = new AgainstScore(
//            $game,
//            $homeFormation->getPoints($gameRound, $s11Points),
//            $awayFormation->getPoints($gameRound, $s11Points),
//            GamePhase::RegularTime
//        );
//        $this->againstScoreRepos->save($score, true);
//    }
//
//    protected function setQualifiedPlaces(Poule $poule): void {
//        $qualifyService = new QualifyService($poule->getRound());
//        $changedPlaces = $qualifyService->setQualifiers($poule);
//        foreach( $changedPlaces as $changedPlace) {
//            if( $changedPlace->getGamesState() !== State::Created ) {
//                $this->logger->info('       qualifyPlace not set, because already has finished games');
//                continue;
//            }
//            $this->logger->info('       set qualifyPlace for ' . $changedPlace->getStructureLocation());
//            $this->placeRepos->save($changedPlace, true );
//        }
//    }
//
//    protected function getFormation(
//        AssemblePeriod|TransferPeriod $editPeriod,
//        StartLocationMap $startLocationMap,
//        AgainstGamePlace $againstGamePlace
//    ): Formation|null {
//        $startLocation = $againstGamePlace->getPlace()->getStartLocation();
//        if ($startLocation === null) {
//            return null;
//        }
//        $competitor = $startLocationMap->getCompetitor($startLocation);
//        if ($competitor === null || !($competitor instanceof PoolCompetitor)) {
//            return null;
//        }
//        return $competitor->getPoolUser()->getFormation($editPeriod);
//    }
//

//
//        protected function getSourceGameRoundState(Competition $competition, int $gameRoundNumber): State
//    {
//        // $validStates = [State::Created, State::InProgress, State::Finished];
//        $againstGames = $this->againstGameRepos->getCompetitionGames($competition, null, $gameRoundNumber);
//        if (count($againstGames) === 0) {
//            return State::Created;
//        }
//        $created = array_filter($againstGames, function (AgainstGame $againstGame): bool {
//            return $againstGame->getState() === State::Created;
//        });
//        $finished = array_filter($againstGames, function (AgainstGame $againstGame): bool {
//            return $againstGame->getState() === State::Finished;
//        });
//        if (count($created) > 0 && count($finished) > 0) {
//            return State::InProgress;
//        }
//        if (count($created) === 0 && count($finished) > 0) {
//            $canceled = array_filter($againstGames, function (AgainstGame $againstGame): bool {
//                return $againstGame->getState() === State::Canceled;
//            });
//            if( !$this->allCanceledInFinished(array_values($canceled),array_values($finished)) ) {
//                return State::InProgress;
//            }
//            return State::Finished;
//        }
//        return State::Created;
//    }
//
//        /**
//     * @param list<AgainstGame> $canceled
//     * @param list<AgainstGame> $finished
//     * @return bool
//     */
//    protected function allCanceledInFinished(array $canceled, array $finished): bool {
//        foreach( $canceled as $canceledGame) {
//            if( !$this->canceledGameInFinished($canceledGame, $finished) ) {
//                return false;
//            }
//        }
//        return true;
//    }
}
