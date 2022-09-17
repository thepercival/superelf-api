<?php

declare(strict_types=1);

namespace SuperElf\Totals;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Sports\Competitor\StartLocationMap;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Team;
use SuperElf\CompetitionConfig;
use SuperElf\Formation\Place\Repository as FormationPlaceRepository;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Totals\Calculator as TotalsCalculator;
use SuperElf\Totals\Repository as TotalsRepository;

class Syncer
{
    // protected PlayerTotalsCalculator $playerTotalsCalculator;
    protected LoggerInterface|null $logger = null;

    public function __construct(
        protected GameRoundRepository $gameRoundRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected TotalsRepository $totalsRepos,
        protected FormationPlaceRepository $formationPlaceRepos
    ) {
    }

    public function sync(
        CompetitionConfig $competitionConfig,
        AgainstGame $game,
        bool $alwaysUpdateTotals = false
    ): void {
        $competition = $game->getRound()->getNumber()->getCompetition();
        if ($competitionConfig->getSourceCompetition() !== $competition) {
            throw new Exception('the game is from another competitonconfig', E_ERROR);
        }

        // $points = $competitionConfig->getPoints();
        $totalsCalculator = new TotalsCalculator($competitionConfig);
        $competitors = array_values($competition->getTeamCompetitors()->toArray());
        $map = new StartLocationMap($competitors);
//
        $viewPeriod = $competitionConfig->getViewPeriodByDate($game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new \Exception(
                'the viewperiod should be found for date: ' . $game->getStartDateTime()->format(DateTime::ISO8601),
                E_ERROR
            );
        }
        // foreach ([AgainstSide::HOME, AgainstSide::AWAY] as $homeAway) {
        foreach ($game->getPlaces(/*$homeAway*/) as $gamePlace) {
            $startLocation = $gamePlace->getPlace()->getStartLocation();
            if ($startLocation === null) {
                continue;
            }
            $teamCompetitor = $map->getCompetitor($startLocation);
            if (!($teamCompetitor instanceof TeamCompetitor)) {
                continue;
            }
            $this->syncStatistics(
                $viewPeriod,
                $totalsCalculator,
                $gamePlace,
                $teamCompetitor->getTeam(),
                $alwaysUpdateTotals
            );
        }
        // }
//        $this->s11PlayerRepos->flush();
//        $this->statisticsRepos->flush();
    }

    protected function syncStatistics(
        ViewPeriod $viewPeriod,
        TotalsCalculator $totalsCalculator,
        AgainstGamePlace $gamePlace,
        Team $team,
        bool $alwaysUpdateTotals = false
    ): void {
        $this->logInfo('calculating statistics ' . $team->getName() . ' ..');
        $game = $gamePlace->getGame();

        $finalScore = (new ScoreConfigService())->getFinalAgainstScore($game);
        if ($finalScore === null) {
            return;
        }
        $gameRound = $viewPeriod->getGameRound($game->getGameRoundNumber());
        if ($gameRound === null) {
            return;
        }
        $s11Players = $this->s11PlayerRepos->findByExt($viewPeriod, $team);
        foreach ($s11Players as $s11Player) {
            $playerStats = array_values($s11Player->getStatistics()->toArray());
            $totalsCalculator->updateTotals($s11Player->getTotals(), $playerStats);
            $this->totalsRepos->save($s11Player->getTotals(), true);

            $totalsCalculator->updateTotalPoints($s11Player);
            $this->s11PlayerRepos->save($s11Player, true);

            $formationPlaces = $this->formationPlaceRepos->findByPlayer($s11Player);
            foreach ($formationPlaces as $formationPlace) {
                $totalsCalculator->updateTotals($formationPlace->getTotals(), $formationPlace->getStatistics());
                $this->totalsRepos->save($s11Player->getTotals(), true);

                $totalsCalculator->updateTotalPoints($formationPlace);
                $this->formationPlaceRepos->save($formationPlace, true);
            }
        }
        $this->logInfo('calculated totals and totalpoints');
    }

    protected function logInfo(string $info): void
    {
        if ($this->logger === null) {
            return;
        }
        $this->logger->info($info);
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
