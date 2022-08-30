<?php

declare(strict_types=1);

namespace SuperElf\Player;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Sports\Competitor\StartLocationMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Participation as GameParticipation;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Output\Game\Against as AgainstGameOutput;
use Sports\Person;
use SuperElf\CompetitionConfig;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use SuperElf\Player as S11Player;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Points\Calculator as PointsCalculator;

class Syncer
{
    protected LoggerInterface|null $logger = null;

    public function __construct(
        protected GameRoundRepository $gameRoundRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected PointsCalculator $pointsCalculator
    ) {
    }

    public function sync(CompetitionConfig $competitionConfig, AgainstGame $game): void
    {
        $competition = $game->getRound()->getNumber()->getCompetition();
        if ($competitionConfig->getSourceCompetition() !== $competition) {
            throw new Exception('the game is from another competitonconfig', E_ERROR);
        }

        $competitors = array_values($competition->getTeamCompetitors()->toArray());
        $map = new StartLocationMap($competitors);
        $this->logGame($game, $map);
//
        $viewPeriod = $competitionConfig->getViewPeriodByDate($game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new \Exception(
                'the viewperiod should be found for date: ' . $game->getStartDateTime()->format(DateTime::ISO8601),
                E_ERROR
            );
        }
        foreach ($game->getPlaces(/*$homeAway*/) as $gamePlace) {
            $startLocation = $gamePlace->getPlace()->getStartLocation();
            if ($startLocation === null) {
                continue;
            }
            $teamCompetitor = $map->getCompetitor($startLocation);
            if (!($teamCompetitor instanceof TeamCompetitor)) {
                continue;
            }
            $this->syncS11Players($viewPeriod, $gamePlace);
        }
    }

    protected function syncS11Players(ViewPeriod $viewPeriod, AgainstGamePlace $gamePlace): void
    {
        $this->logInfo('syncing s11Players ..');
        if ($gamePlace->getParticipations()->count() === 0) {
            $this->logWarning('no game-participations');
        }
        foreach ($gamePlace->getParticipations() as $gameParticipation) {
            $this->syncS11Player($viewPeriod, $gameParticipation->getPlayer()->getPerson());
        }
        $this->logInfo('synced s11Players');
    }

    public function syncS11Player(ViewPeriod $viewPeriod, Person $person): S11Player
    {
        $s11Player = $this->s11PlayerRepos->findOneBy(["viewPeriod" => $viewPeriod, "person" => $person ]);
        if ($s11Player !== null) {
            return $s11Player;
        }
        $s11Player = new S11Player($viewPeriod, $person, new Totals());
        $this->logCreateS11Player($this->s11PlayerRepos->save($s11Player, true));
        return $s11Player;
    }
    /**
     * @param S11Player $s11Player
     * @param list<GameParticipation> $gameParticipations
     * @return GameParticipation|null
     */
    protected function getGameParticipation(S11Player $s11Player, array $gameParticipations): GameParticipation|null
    {
        $filtered = array_filter($gameParticipations, function (GameParticipation $gameParticipation) use ($s11Player): bool {
            return $s11Player->getPerson() === $gameParticipation->getPlayer()->getPerson();
        });
        if (count($filtered) > 0) {
            return reset($filtered);
        }
        return null;
    }

    protected function logGame(AgainstGame $game, StartLocationMap $startLocationMap): void
    {
        if ($this->logger === null) {
            return;
        }
        $gameOutput = new AgainstGameOutput($startLocationMap);
        $gameOutput->output($game);
    }

    protected function logCreateS11Player(S11Player $s11Player): void
    {
        $basePeriod = $s11Player->getViewPeriod()->getPeriod();
        $periodDescription = "periode " . $basePeriod->toIso80000('Y-m-d');
        $this->logInfo(
            "  s11Player toegevoegd: " . $periodDescription . " , persoon: " . $s11Player->getPerson()->getName()
        );
    }

    protected function logNoS11Player(Person $person): void
    {
        $this->logInfo("  voor persoon: " . $person->getName() . " is geen speler gevonden");
        foreach ($person->getPlayers() as $playerIt) {
            $basePeriod = $playerIt->getPeriod();
            $this->logInfo("      playerinfo: " . $playerIt->getTeam()->getName() . " (".$playerIt->getLine().") => periode " . $basePeriod);
        }
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

    protected function logWarning(string $warning): void
    {
        if ($this->logger === null) {
            return;
        }
        $this->logger->warning($warning);
    }
}
