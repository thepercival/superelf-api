<?php

declare(strict_types=1);

namespace SuperElf\Substitute\Appearance;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\GameRound;
use SuperElf\Substitute\Appearance;

/**
 * @template-extends EntityRepository<Appearance>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<Appearance>
     */
    use BaseRepository;

    public function update(GameRound $gameRound): void
    {
        $this->remove($gameRound);
        $this->add($gameRound);
    }

    protected function remove(GameRound $gameRound): void
    {
        $gameRoundId = (string)$gameRound->getId();
        $sql = 'delete from substituteAppearances where gameRoundId = ' . $gameRoundId;
        $this->getEntityManager()->getConnection()->executeQuery($sql);
    }

    protected function add(GameRound $gameRound): void
    {
        $gameRoundId = (string)$gameRound->getId();
        $sql = "
            insert into substituteAppearances( formationLineId, gameRoundId )
                (select fl.id, " . $gameRoundId . "   
                from    formationLines fl 
                        join formations f on fl.formationId = f.id
		                join viewPeriods vp on vp.id = f.viewPeriodId 
		                join gameRounds gr on gr.viewPeriodId = vp.id 
                where 	gr.id = " . $gameRoundId . "
                and     exists (
                            select  *
                            from    statistics s
                                    join formationPlaces fpl on fpl.playerId = s.playerId and fpl.formationLineId = fl.id
                                    join gameRounds gr on gr.viewPeriodId = f.viewPeriodId
                            where   s.gameRoundId = gr.id and s.beginMinute = -1 and fpl.`number` > 0
                        )
                and     (
                            select count(*)
                            from    statistics s
                                    join formationPlaces fpl on fpl.playerId = s.playerId and fpl.formationLineId = fl.id
                                    join gameRounds gr on gr.viewPeriodId = f.viewPeriodId
                            where   s.gameRoundId = gr.id and fpl.`number` > 0
                        ) = (select count(*) from formationPlaces where formationLineId = fl.id and number > 0) 
                )";
        $this->getEntityManager()->getConnection()->executeQuery($sql);
    }
}
