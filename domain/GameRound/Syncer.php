<?php
declare(strict_types=1);

namespace SuperElf\GameRound;

use Psr\Log\LoggerInterface;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\State;
use SuperElf\GameRound;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
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

    public function sync(AgainstGame $game, \DateTimeImmutable|null $oldGameDate): void
    {
        $competition = $game->getRound()->getNumber()->getCompetition();

        $viewPeriod = $this->viewPeriodRepos->findOneByDate($competition, $game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new \Exception("no viewperiod found for game", E_ERROR);
        }

        $gameRound = $viewPeriod->getGameRound($game->getGameRoundNumber());
        if ($gameRound === null) {
            $gameRound = new GameRound($viewPeriod, $game->getGameRoundNumber());
            $this->gameRoundRepos->save($gameRound);
            $vpDescr = 'viewperiod "' . $viewPeriod . '"';
            $this->logInfo('add gameround "' . $game->getGameRoundNumber() . '" for ' . $vpDescr);
        }

        if ($oldGameDate === null) {
            return;
        }

        // ////////////////// RESCHEDULED ////////////////////////
        $oldViewPeriod = $this->viewPeriodRepos->findOneByDate($competition, $oldGameDate);
        if ($oldViewPeriod === null || $oldViewPeriod === $viewPeriod) {
            return;
        }

        // if no games for this gameroundnumber in oldviewperiod than remove gameroundnumber
        $againstGames = $this->againstGameRepos->getCompetitionGames(
            $competition,
            State::Created + State::InProgress + State::Finished,
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
        $this->gameRoundRepos->remove($gameRound);
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
