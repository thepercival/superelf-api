<?php
declare(strict_types=1);

namespace SuperElf\Player;

use DateTime;
use Psr\Log\LoggerInterface;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Participation as GameParticipation;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Person;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Team;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Statistics\Repository as StatisticsRepository;
use SuperElf\Player as S11Player;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Substitute\Appearance\Calculator as SubstituteAppearanceCalculator;
use SuperElf\Points\Calculator as PointsCalculator;
use SuperElf\Points\Creator as PointsCreator;
use SportsHelpers\Against\Side as AgainstSide;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Output\Game\Against as AgainstGameOutput;
use Sports\Competitor\Map as CompetitorMap;

class Syncer
{
    protected LoggerInterface|null $logger = null;

    public function __construct(
        protected GameRoundRepository $gameRoundRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected PointsCreator $pointsCreator,
        protected PointsCalculator $pointsCalculator
    ) {
    }

    public function sync(AgainstGame $game): void
    {
        $competition = $game->getRound()->getNumber()->getCompetition();
        // viewperiods for season

        $competitors = array_values($competition->getTeamCompetitors()->toArray());
        $map = new CompetitorMap($competitors);
        $this->logGame($game, $map);
//
        $viewPeriod = $this->viewPeriodRepos->findOneByDate($competition, $game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new \Exception('the viewperiod should be found for date: ' . $game->getStartDateTime()->format(DateTime::ISO8601), E_ERROR);
        }
        foreach ($game->getPlaces(/*$homeAway*/) as $gamePlace) {
            $teamCompetitor = $map->getCompetitor($gamePlace->getPlace());
            if (!($teamCompetitor instanceof TeamCompetitor)) {
                continue;
            }
            $this->syncS11Players($viewPeriod, $gamePlace);
        }
    }

    protected function syncS11Players(ViewPeriod $viewPeriod, AgainstGamePlace $gamePlace): void
    {
        $this->logInfo('syncing s11Players ..');
        foreach ($gamePlace->getParticipations() as $gameParticipation) {
            $this->syncS11Player($viewPeriod, $gameParticipation->getPlayer()->getPerson());
        }
        $this->logInfo('sync s11Players');
    }

    protected function syncS11Player(ViewPeriod $viewPeriod, Person $person): void
    {
        $s11Player = $this->s11PlayerRepos->findOneBy(["viewPeriod" => $viewPeriod, "person" => $person ]);
        if ($s11Player !== null) {
            return;
        }
        $s11Player = new S11Player($viewPeriod, $person);
        $this->logCreateS11Player($this->s11PlayerRepos->save($s11Player));
    }
    /**
     * @param S11Player $s11Player
     * @param list<GameParticipation> $gameParticipations
     * @return GameParticipation|null
     */
    protected function getGameParticipation(S11Player $s11Player, array $gameParticipations): GameParticipation|null
    {
        $filtered = array_filter($gameParticipations, function (GameParticipation $gameParticipation) use ($s11Player) : bool {
            return $s11Player->getPerson() === $gameParticipation->getPlayer()->getPerson();
        });
        if (count($filtered) > 0) {
            return reset($filtered);
        }
        return null;
    }

    protected function logGame(AgainstGame $game, CompetitorMap $competitorMap): void
    {
        if ($this->logger === null) {
            return;
        }
        $gameOutput = new AgainstGameOutput($competitorMap);
        $gameOutput->output($game);
    }

    protected function logCreateS11Player(S11Player $s11Player): void
    {
        $basePeriod = $s11Player->getViewPeriod()->getPeriod();
        $periodDescription = "periode " . $basePeriod->getStartDate()->format("Y-m-d"). " t/m " . $basePeriod->getEndDate()->format("Y-m-d");
        $this->logInfo("  toegevoegd: " . $periodDescription ." , persoon: " . $s11Player->getPerson()->getName());
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
}
