<?php

declare(strict_types=1);

namespace SuperElf\Substitute\Appearance;

use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use Psr\Log\LoggerInterface;
use Sports\Game\Against as AgainstGame;
use Sports\Team;
use Sports\Team\Calculator as TeamCalculator;
use SportsHelpers\Against\AgainstSide;
use SuperElf\CompetitionConfig;
use SuperElf\Formation;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\GameRound;
use SuperElf\OneTeamSimultaneous;
use SuperElf\Repositories\PoolRepository as PoolRepository;
use SuperElf\Repositories\S11PlayerRepository as PlayerRepository;
use SuperElf\Repositories\ViewPeriodRepository as ViewPeriodRepository;
use SuperElf\Substitute\Appearance;
use SuperElf\Totals\Calculator as TotalsCalculator;
use SuperElf\Totals\TotalsSyncer as TotalsSyncer;

final class Syncer
{
    protected LoggerInterface|null $logger = null;

    /** @var EntityRepository<GameRound>  */
    protected EntityRepository $gameRoundRepos;

    /** @var EntityRepository<Appearance>  */
    protected EntityRepository $appearanceRepos;


    public function __construct(
        protected PlayerRepository $playerRepos,
        protected PoolRepository $poolRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected TotalsSyncer $totalsSyncer,
        protected EntityManagerInterface $entityManager
    ) {
        $this->gameRoundRepos = $entityManager->getRepository(GameRound::class);
        $this->appearanceRepos = $entityManager->getRepository(Appearance::class);
    }

    public function syncSubstituteAppearances(CompetitionConfig $competitionConfig, AgainstGame $game): void
    {
        $competition = $game->getRound()->getNumber()->getCompetition();
        if ($competitionConfig->getSourceCompetition() !== $competition) {
            throw new Exception('the game is from another competitonconfig', E_ERROR);
        }

        $viewPeriod = $competitionConfig->getViewPeriodByDate($game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new Exception(
                'the viewperiod should be found for date: ' . $game->getStartDateTime()->format(
                    DateTimeInterface::ATOM
                ),
                E_ERROR
            );
        }

        $gameRound = $viewPeriod->getGameRound($game->getGameRoundNumber());
        if ($gameRound === null) {
            throw new Exception('gameround "' . $game->getGameRoundNumber() . '"  for viewperiod "' .
                    $viewPeriod->getPeriod()->toIso8601() . '" could not be found for gameStartDate "' .
                    $game->getStartDateTime()->format(DateTimeInterface::ATOM), E_ERROR);
        }
        $this->logInfo('updating substituteAppereances ..');

        $teamCalculator = new TeamCalculator($competition);
        $totalsCalculator = new TotalsCalculator();
        $points = $competitionConfig->getPoints();
        $pools = $this->poolRepos->findBy(['competitionConfig' => $competitionConfig]);
        foreach( $pools as $pool ) {
            $this->logInfo('    pool "' . $pool->getName() . '" ..');
            foreach( $pool->getUsers() as $poolUser ) {
                $formation = $poolUser->getFormation($viewPeriod);
                if( $formation === null ) {
                    continue;
                }
                foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
                    $team = $teamCalculator->getSingleTeam($game, $side);
                    $formationLines = $this->getFormationLinesForTeam($team, $game, $formation);
                    foreach( $formationLines as $formationLine ) {
                        $appearance = $formationLine->getSubstituteAppareance($gameRound);
                        $needsSubstituteAppearance = $this->needsSubstituteAppearance($formationLine, $gameRound);
                        if( $needsSubstituteAppearance ) {
                            if ( $appearance === null ) {
                                $newAppearance = new Appearance($formationLine, $gameRound);
                                $formationLine->getSubstituteAppearances()->add($newAppearance);
                                $this->entityManager->persist($newAppearance);
                                $this->entityManager->flush();
                                $this->totalsSyncer->updateFormationPlacesTotals($totalsCalculator, $points, [$formationLine->getSubstitute()]);
                            }
                        } else { // do not needsSubstituteAppearance
                            if ( $appearance !== null ) {
                                $formationLine->getSubstituteAppearances()->removeElement($appearance);
                                $this->entityManager->remove($appearance);
                                $this->entityManager->flush();
                                $this->totalsSyncer->updateFormationPlacesTotals($totalsCalculator, $points, [$formationLine->getSubstitute()]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Team $team
     * @param AgainstGame $game
     * @param Formation $formation
     * @return list<FormationLine>
     */
    public function getFormationLinesForTeam(Team $team, AgainstGame $game, Formation $formation): array
    {
        $dateTime = $game->getStartDateTime();
        $oneTeamSim = new OneTeamSimultaneous();
        $formationLines = $formation->getLines()->filter(
            function (FormationLine $formationLine) use($oneTeamSim,$team, $dateTime) : bool {
                foreach ($formationLine->getStartingPlaces() as $formationPlace) {
                    $s11Player = $formationPlace->getPlayer();
                    if( $s11Player === null) {
                        continue;
                    }
                    $player = $oneTeamSim->getPlayer($s11Player->getPerson(), $dateTime);
                    if ($player !== null && $player->getTeam() === $team) {
                        return true;
                    }
                }
                return false;
            });
        return array_values($formationLines->toArray());
    }

    public function needsSubstituteAppearance(FormationLine $formationLine, GameRound $gameRound): bool {
        foreach ($formationLine->getStartingPlaces() as $formationPlace) {
            $statistics = $formationPlace->getGameRoundStatistics( $gameRound);
            if( $statistics === null ) {
                continue;
            }
            if( !$statistics->hasAppeared() ) {
                return true;
            }
        }
        return false;
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
