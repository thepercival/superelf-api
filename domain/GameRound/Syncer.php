<?php

declare(strict_types=1);

namespace SuperElf\GameRound;

use Exception;
use Psr\Log\LoggerInterface;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\State as GameState;
use SuperElf\CompetitionConfig;
use SuperElf\GameRound;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
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

    public function sync(
        CompetitionConfig $competitionConfig,
        AgainstGame $game,
        \DateTimeImmutable|null $oldGameDate
    ): void {
        $competition = $game->getRound()->getNumber()->getCompetition();
        if ($competitionConfig->getSourceCompetition() !== $competition) {
            throw new Exception('the game is from another competitonconfig', E_ERROR);
        }

        $viewPeriod = $competitionConfig->getViewPeriodByDate($game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new \Exception("no viewperiod found for game", E_ERROR);
        }

        $gameRound = $viewPeriod->getGameRound($game->getGameRoundNumber());
        if ($gameRound === null) {
            $gameRound = new GameRound($viewPeriod, $game->getGameRoundNumber());
            $this->gameRoundRepos->save($gameRound, true);
            $vpDescr = 'viewperiod "' . $viewPeriod . '"';
            $this->logInfo('add gameround "' . $game->getGameRoundNumber() . '" for ' . $vpDescr);
        }

        if ($oldGameDate === null) {
            return;
        }

        // ////////////////// RESCHEDULED ////////////////////////
        $oldViewPeriod = $competitionConfig->getViewPeriodByDate($oldGameDate);
        if ($oldViewPeriod === null || $oldViewPeriod === $viewPeriod) {
            return;
        }

        // if no games for this gameroundnumber in oldviewperiod than remove gameroundnumber
        $againstGames = $this->againstGameRepos->getCompetitionGames(
            $competition,
            [GameState::Created, GameState::InProgress, GameState::Finished],
            $game->getGameRoundNumber(),
            $oldViewPeriod->getPeriod()
        );
        if (count($againstGames) > 0) {
            return;
        }

        $gameRound = $oldViewPeriod->getGameRound($game->getGameRoundNumber());
        if ($gameRound === null) {
            return;
        }
        $oldViewPeriod->getGameRounds()->removeElement($gameRound);
        $this->gameRoundRepos->remove($gameRound, true);
        $vpDescr = 'viewperiod "' . $oldViewPeriod . '"';
        $this->logInfo('remove gameround "' . $game->getGameRoundNumber() . '" for ' . $vpDescr);
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
