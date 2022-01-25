<?php

declare(strict_types=1);

namespace SuperElf\Substitute\Appearance;

use DateTimeInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Sports\Game\Against as AgainstGame;
use SuperElf\CompetitionConfig;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Player\Repository as PlayerRepository;
use SuperElf\Substitute\Appearance\Repository as AppearanceRepository;

class Syncer
{
    protected LoggerInterface|null $logger = null;

    public function __construct(
        protected GameRoundRepository $gameRoundRepos,
        protected PlayerRepository $playerRepos,
        protected AppearanceRepository $appearanceRepos,
        protected ViewPeriodRepository $viewPeriodRepos
    ) {
    }

    public function sync(CompetitionConfig $competitionConfig, AgainstGame $game): void
    {
        $competition = $game->getRound()->getNumber()->getCompetition();
        if ($competitionConfig->getSourceCompetition() !== $competition) {
            throw new Exception('the game is from another competitonconfig', E_ERROR);
        }

        $viewPeriod = $competitionConfig->getViewPeriodByDate($game->getStartDateTime());
        if ($viewPeriod === null) {
            throw new Exception(
                'the viewperiod should be found for date: ' . $game->getStartDateTime()->format(
                    DateTimeInterface::ISO8601
                ), E_ERROR
            );
        }

        $gameRound = $viewPeriod->getGameRound($game->getGameRoundNumber());
        if ($gameRound === null) {
            throw new Exception('gameround "' . $game->getGameRoundNumber() . '"  for viewperiod "' .
                    $viewPeriod . '" could not be found for gameStartDate "' .
                    $game->getStartDateTime()->format(DateTimeInterface::ISO8601), E_ERROR);
        }
        $this->appearanceRepos->update($gameRound);
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
