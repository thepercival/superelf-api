<?php
declare(strict_types=1);

namespace SuperElf\Statistics;

use DateTimeInterface;
use Sports\Game\Participation as GameParticipation;
use Sports\Game\Against as AgainstGame;
use Sports\Score\Config\Service as ScoreConfigService;
use SportsHelpers\Against\Side as AgainstSide;
use SuperElf\Defaults;
use SuperElf\GameRound;
use SuperElf\Sheet;
use SuperElf\Statistics;
use SuperElf\Player as S11Player;
use SuperElf\Period\View as ViewPeriod;
use SportsHelpers\Against\Result as AgainstResult;
use SuperElf\OneTeamSimultaneous;

class Converter
{
    public function __construct(
        protected OneTeamSimultaneous $oneTeamSimultaneous
    ) {
    }

    public function convert(
        ViewPeriod $viewPeriod,
        S11Player $s11Player,
        AgainstGame $game,
        GameParticipation|null $participation
    ): Statistics {
        $gameRound = $viewPeriod->getGameRound($game->getGameRoundNumber());
        if ($gameRound === null) {
            throw new \Exception('gameround "' . $game->getGameRoundNumber() .'" could not be found in viewperiod ' . $viewPeriod, E_ERROR);
        }
        if ($participation === null) {
            return $this->getDefaultStatistics($s11Player, $gameRound, $game);
        }

        $finalScore = (new ScoreConfigService())->getFinalAgainstScore($game);
        $side = $participation->getAgainstGamePlace()->getSide();
        if ($finalScore === null) {
            throw new \Exception('game has no final score while participations exist', E_ERROR);
        }

        $opposite = $side === AgainstSide::HOME ? AgainstSide::AWAY : AgainstSide::HOME;
        $sheet = Sheet::NORMAL;
        if ($finalScore->get($opposite) === 0) {
            $sheet = Sheet::CLEAN;
        } elseif ($finalScore->get($opposite) > Defaults::SPOTTY_SHEET_THRESHOLD) {
            $sheet = Sheet::SPOTTY;
        }

        $nrOfYellowCards = $participation->getWarnings()->count();

        return new Statistics(
            $s11Player,
            $gameRound,
            $finalScore->getResult($side),
            $participation->getBeginMinute(),
            $participation->getEndMinute(),
            $participation->getFieldGoals()->count(),
            $participation->getAssists()->count(),
            $participation->getPenalties()->count(),
            $participation->getOwnGoals()->count(),
            $sheet,
            $nrOfYellowCards,
            $nrOfYellowCards < 2 && $participation->getSendoff() !== null,
            $participation->getAgainstGamePlace()->getGame()->getStartDateTime(),
            new \DateTimeImmutable()
        );
    }

    protected function getDefaultStatistics(
        S11Player $s11Player,
        GameRound $gameRound,
        AgainstGame $game
    ): Statistics {
        $player = $this->oneTeamSimultaneous->getPlayer($s11Player->getPerson(), $game->getStartDateTime());
        if ($player === null) {
            throw new \Exception('"' . $s11Player->getPerson()->getName() .'" is no active player at "' . $game->getStartDateTime()->format(
                DateTimeInterface::ISO8601
            ) .'"', E_ERROR);
        }
        return new Statistics(
            $s11Player,
            $gameRound,
            AgainstResult::LOSS,
            -1,
            -1,
            0,
            0,
            0,
            0,
            Sheet::NORMAL,
            0,
            false,
            $game->getStartDateTime(),
            new \DateTimeImmutable()
        );
    }
}
