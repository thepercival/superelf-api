<?php
declare(strict_types=1);

namespace SuperElf\Substitute\Appearance;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Substitute\Appearance;
use SuperElf\GameRound;

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
        $sql = '
          delete	sa
          from		substituteAppearances sa
        	            join formationLines fl on sa.formationLineId
        	            join formations f on fl.formationId = f.id
          where	    sa.gameRoundId = ' . $gameRoundId . '
          and		(   select  count(*)
            	        from    statistics s
            	                join playersPerFormationLine flp on flp.playerId = s.playerId and flp.formationLineId = fl.id
            	        where   s.gameRoundId = sa.gameRoundId
                        and     s.beginMinute > -1 = 0 
                or 
                    (   select  count(*)
                    	from    statistics s
                    	        join playersPerFormationLine flp on flp.playerId = s.playerId and flp.formationLineId = fl.id
                    	where   s.gameRoundId = sa.gameRoundId
                        ) < fl.nrOfPersons
         )';
        $this->_em->getConnection()->executeQuery( $sql );
    }

    protected function add(GameRound $gameRound): void
    {
        $gameRoundId = (string)$gameRound->getId();
        $sql = '
            insert into substituteAppearances( formationLineId, gameRoundId )
            (select fl.id, ' .$gameRoundId . '
            from    substituteAppearances sa
                    join formationLines fl on sa.formationLineId
        	        join formations f on fl.formationId = f.id
            where   sa.gameRoundId = ' . $gameRoundId . '
            and     exists (
                        select  count(*)
                        from    statistics s
                                join playersPerFormationLine flp on flp.playerId = s.playerId and flp.formationLineId = fl.id
                        where   s.gameRoundId = sa.gameRoundId
                        and     s.beginMinute > -1
                    )
                and
                    (select count(*)
                    from    statistics s
                            join playersPerFormationLine flp on flp.playerId = s.playerId and flp.formationLineId = fl.id
                    where   s.gameRoundId = sa.gameRoundId
                  ) = fl.nrOfPersons 
        )';
        $this->_em->getConnection()->executeQuery( $sql );
    }
}
