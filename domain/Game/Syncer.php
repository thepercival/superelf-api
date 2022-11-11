<?php

declare(strict_types=1);

namespace SuperElf\Game;

use Psr\Log\LoggerInterface;
use Sports\Competition;
use Sports\Competitor\StartLocationMap;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\Phase as GamePhase;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\State;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Together\Repository as TogetherGameRepository;
use Sports\Poule;
use Sports\Place\Repository as PlaceRepository;
use Sports\Qualify\Service as QualifyService;
use Sports\Round;
use Sports\Score\Against as AgainstScore;
use Sports\Score\Against\Repository as AgainstScoreRepository;
use Sports\Score\Together as TogetherScore;
use Sports\Score\Together\Repository as TogetherScoreRepository;
use Sports\Structure\Repository as StructureRepository;
use SportsHelpers\Against\Side;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SuperElf\CompetitionConfig;
use SuperElf\Competitor as PoolCompetitor;
use SuperElf\Formation;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\League as S11League;
use SuperElf\Periods\AssemblePeriod;
use SuperElf\Periods\TransferPeriod;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Points;
use SuperElf\Points\Calculator as PointsCalculator;
use SuperElf\Points\Creator as PointsCreator;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;

class Syncer
{
    public function __construct(
        protected PoolRepository $poolRepos,
        protected StructureRepository $structureRepos,
        protected AgainstGameRepository $againstGameRepos,
        protected TogetherGameRepository $togetherGameRepos,
        protected AgainstScoreRepository $againstScoreRepos,
        protected TogetherScoreRepository $togetherScoreRepos,
        protected PlaceRepository $placeRepos,
        protected GameRoundRepository $gameRoundRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected PointsCreator $pointsCreator,
        protected PointsCalculator $pointsCalculator,
        protected LoggerInterface $logger
    ) {
    }

    // voor alle poolusers
    // houdt state in progress and finished bij
    // kijk als gameroundnumber is afgelopen
    public function sync(CompetitionConfig $competitionConfig, int $gameRoundNumber): void
    {
        $editPeriods = $this->getValidEditPeriods($competitionConfig, $gameRoundNumber);

        $pools = $this->poolRepos->findBy(['competitionConfig' => $competitionConfig]);
        foreach ($pools as $pool) {
            $this->logger->info('updating poolGames for "' . $pool->getName() . '" .. ');
            foreach ($pool->getCompetitions() as $poolCompetition) {
                $sportVariant = $poolCompetition->getSingleSport()->createVariant();
                foreach ($editPeriods as $editPeriod) {
                    if ($editPeriod->getViewPeriod()->getGameRound($gameRoundNumber) === null) {
                        continue;
                    }
                    if ($sportVariant instanceof Single || $sportVariant instanceof AllInOneGame) {
                        $this->updatePoolCompetitionGames(
                            $competitionConfig,
                            $editPeriod,
                            $pool,
                            $poolCompetition,
                            $gameRoundNumber
                        );
                    } else { // if ($sportVariant instanceof AgainstH2h || $sportVariant instanceof AgainstGpp)
                        $this->updatePoolCupGames(
                            $competitionConfig,
                            $editPeriod,
                            $pool,
                            $poolCompetition,
                            $gameRoundNumber
                        );
                    }
                }
            }
        }
    }

    /**
     * @param CompetitionConfig $competitionConfig
     * @param int $gameRoundNumber
     * @return list<AssemblePeriod|TransferPeriod>
     */
    protected function getValidEditPeriods(CompetitionConfig $competitionConfig, int $gameRoundNumber): array
    {
        $editPeriods = [];
        $assemblePeriod = $competitionConfig->getAssemblePeriod();
        if ($assemblePeriod->getViewPeriod()->getGameRound($gameRoundNumber) !== null) {
            $editPeriods[] = $assemblePeriod;
        }
        $transferPeriod = $competitionConfig->getTransferPeriod();
        if ($transferPeriod->getViewPeriod()->getGameRound($gameRoundNumber) !== null) {
            $editPeriods[] = $transferPeriod;
        }
        return $editPeriods;
    }

    public function updatePoolCompetitionGames(
        CompetitionConfig $competitionConfig,
        AssemblePeriod|TransferPeriod $editPeriod,
        Pool $pool,
        Competition $poolCompetition,
        int $gameRoundNumber
    ): void {
        $competitors = $pool->getCompetitors($poolCompetition);
        $startLocationMap = new StartLocationMap($competitors);
        $competition = $competitionConfig->getSourceCompetition();

        $togetherGames = $this->getCompetitionTogetherGames($poolCompetition, $gameRoundNumber);
        foreach ($togetherGames as $togetherGame) {
            foreach ($togetherGame->getPlaces() as $gamePlace) {
                // remove scores
                $this->togetherScoreRepos->removeScores($gamePlace);

                // add score
                $competitor = null;
                $startLocation = $gamePlace->getPlace()->getStartLocation();
                if ($startLocation !== null) {
                    $competitor = $startLocationMap->getCompetitor($startLocation);
                }
                if ($competitor !== null && $competitor instanceof PoolCompetitor) {
                    $formation = $competitor->getPoolUser()->getFormation($editPeriod);
                    if ($formation !== null) {
                        $this->saveTogetherScore(
                            $gamePlace,
                            $gameRoundNumber,
                            $formation,
                            $competitionConfig->getPoints()
                        );
                    }
                }
            }

            // als source finished dan game ook finished
            $sourceGameRoundState = $this->getSourceGameRoundState($competition, $gameRoundNumber);
            if ($sourceGameRoundState !== $togetherGame->getState()) {
                $togetherGame->setState($sourceGameRoundState);
                $this->togetherGameRepos->save($togetherGame, true);
                $this->logger->info(
                    'update gameRound ' . $gameRoundNumber . ' to state "' . $sourceGameRoundState->name
                );
            }
        }
    }

    /**
     * @param Competition $poolCompetition
     * @param int $gameRoundNumber
     * @return list<TogetherGame>
     */
    protected function getCompetitionTogetherGames(Competition $poolCompetition, int $gameRoundNumber): array
    {
        $togetherGames = $this->togetherGameRepos->getCompetitionGames($poolCompetition, null);
        return array_values(
            array_filter($togetherGames, function (TogetherGame $game) use ($gameRoundNumber): bool {
                return count(
                        $game->getPlaces()->filter(
                            function (TogetherGamePlace $gamePlace) use ($gameRoundNumber): bool {
                                return $gamePlace->getGameRoundNumber() === $gameRoundNumber;
                            }
                        )
                    ) > 0;
            })
        );
    }

    protected function saveTogetherScore(
        TogetherGamePlace $gamePlace,
        int $gameRoundNumber,
        Formation $formation,
        Points $s11Points
    ): void {
        $viewPeriod = $formation->getViewPeriod();
        $gameRound = $viewPeriod->getGameRound($gameRoundNumber);
        if ($gameRound === null) {
            return;
        }
        $points = $formation->getPoints($gameRound, $s11Points);
        $score = new TogetherScore(
            $gamePlace,
            $points,
            GamePhase::RegularTime
        );
        $this->togetherScoreRepos->save($score, true);
    }

    protected function getSourceGameRoundState(Competition $competition, int $gameRoundNumber): State
    {
        $validStates = [State::Created, State::InProgress, State::Finished];
        $againstGames = $this->againstGameRepos->getCompetitionGames($competition, $validStates, $gameRoundNumber);
        if (count($againstGames) === 0) {
            return State::Created;
        }
        $created = array_filter($againstGames, function (AgainstGame $againstGame): bool {
            return $againstGame->getState() === State::Created;
        });
        $finished = array_filter($againstGames, function (AgainstGame $againstGame): bool {
            return $againstGame->getState() === State::Finished;
        });
        if (count($created) > 0 && count($finished) > 0) {
            return State::InProgress;
        }
        if (count($created) === 0 && count($finished) > 0) {
            return State::Finished;
        }
        return State::Created;
    }

    /** @psalm-suppress UnusedVariable */
    public function updatePoolCupGames(
        CompetitionConfig $competitionConfig,
        AssemblePeriod|TransferPeriod $editPeriod,
        Pool $pool,
        Competition $poolCompetition,
        int $gameRoundNumber
    ): void {
        $competitors = $pool->getCompetitors($poolCompetition);
        $startLocationMap = new StartLocationMap($competitors);
        $competition = $competitionConfig->getSourceCompetition();

//        if( $poolCompetition->getLeague() === S11League::Cup && $pool->getName() === 'kamp duim') {
//            $erm = 12;
//        }
        // first get complete structure

        $structure = $this->structureRepos->getStructure($poolCompetition);

        $againstGames = $this->againstGameRepos->getCompetitionGames($poolCompetition, null, $gameRoundNumber);
        foreach ($againstGames as $againstGame) {
            // remove scores
            $this->againstScoreRepos->removeScores($againstGame);

            $this->createAndSaveAgainstScores(
                $editPeriod,
                $againstGame,
                $startLocationMap,
                $competitionConfig->getPoints()
            );

            // als source finished dan game ook finished
            $sourceGameRoundState = $this->getSourceGameRoundState($competition, $gameRoundNumber);
            if ($sourceGameRoundState === State::Finished) {
                $againstGame->setState(State::Finished);
                $this->againstGameRepos->save($againstGame, true);
                $this->logger->info(
                    '   update poolGameState to "' . State::Finished->name .'" for poule');

                if( $againstGame->getPoule()->getGamesState() === State::Finished) {
                    $this->logger->info('   poule finished : calculating qualify-places ..');
                    $this->setQualifiedPlaces($againstGame->getPoule());
                    // bye' s
                    foreach ($this->getPoulesWithoutGames($againstGame->getPoule()->getRound()) as $pouleIt ) {
                        $this->setQualifiedPlaces($pouleIt);
                    }
                }
            }
        }
    }

    /**
     * @param Round $round
     * @return list<Poule>
     */
    protected function getPoulesWithoutGames(Round $round): array {
        return array_values( $round->getPoules()->filter( function(Poule $poule): bool {
            return count($poule->getGames()) === 0;
        })->toArray() );
    }

    protected function createAndSaveAgainstScores(
        AssemblePeriod|TransferPeriod $editPeriod,
        AgainstGame $game,
        StartLocationMap $startLocationMap,
        Points $s11Points
    ): void {
        $homeGamePlaces = $game->getSidePlaces(Side::Home);
        $homeGamePlace = array_shift($homeGamePlaces);
        $awayGamePlaces = $game->getSidePlaces(Side::Away);
        $awayGamePlace = array_shift($awayGamePlaces);
        if ($homeGamePlace === null || $awayGamePlace === null) {
            return;
        }
        $homeFormation = $this->getFormation($editPeriod, $startLocationMap, $homeGamePlace);
        $awayFormation = $this->getFormation($editPeriod, $startLocationMap, $awayGamePlace);
        if ($homeFormation === null || $awayFormation === null) {
            return;
        }

        $gameRound = $editPeriod->getViewPeriod()->getGameRound($game->getGameRoundNumber());
        if ($gameRound === null) {
            return;
        }

        $score = new AgainstScore(
            $game,
            $homeFormation->getPoints($gameRound, $s11Points),
            $awayFormation->getPoints($gameRound, $s11Points),
            GamePhase::RegularTime
        );
        $this->againstScoreRepos->save($score, true);
    }

    protected function setQualifiedPlaces(Poule $poule): void {
        $qualifyService = new QualifyService($poule->getRound());
        $changedPlaces = $qualifyService->setQualifiers($poule);
        foreach( $changedPlaces as $changedPlace) {
            if( $changedPlace->getGamesState() !== State::Created ) {
                $this->logger->info('       qualifyPlace not set, because already has finished games');
                continue;
            }
            $this->logger->info('       set qualifyPlace for ' . $changedPlace->getStructureLocation());
            $this->placeRepos->save($changedPlace, true );
        }
    }

    protected function getFormation(
        AssemblePeriod|TransferPeriod $editPeriod,
        StartLocationMap $startLocationMap,
        AgainstGamePlace $againstGamePlace
    ): Formation|null {
        $startLocation = $againstGamePlace->getPlace()->getStartLocation();
        if ($startLocation === null) {
            return null;
        }
        $competitor = $startLocationMap->getCompetitor($startLocation);
        if ($competitor === null || !($competitor instanceof PoolCompetitor)) {
            return null;
        }
        return $competitor->getPoolUser()->getFormation($editPeriod);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
