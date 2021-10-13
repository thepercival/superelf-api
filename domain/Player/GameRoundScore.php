<?php
declare(strict_types=1);

namespace SuperElf\Player;

use SuperElf\Player as S11Player;
use SuperElf\GameRound;
use SuperElf\GameRound\Score as BaseGameRoundScore;

class GameRoundScore extends BaseGameRoundScore
{
    protected array $stats = [];

    public function __construct(protected S11Player $player, GameRound $gameRound)
    {
        parent::__construct($gameRound);
        //        if (!$player->getGameRoundScores()->contains($this)) {
//            $player->getGameRoundScores()->add($this) ;
//        }
    }

    public function getPlayer(): S11Player
    {
        return $this->player;
    }

//    /**
//     * @return array<int,int|bool>
//     */
//    public function getStats(): array {
//        return $this->stats;
//    }
//
//    /**
//     * @param array<int,int|bool> $stats
//     */
//    public function setStats(array $stats ): void {
//        $this->stats = $stats;
//    }
//
//    public function participated(): bool {
//        return $this->stats[ViewPeriodPerson::LINEUP] || $this->stats[ViewPeriodPerson::SUBSTITUTE];
//    }
}
