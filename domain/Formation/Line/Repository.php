<?php
declare(strict_types=1);

namespace SuperElf\Formation\Line;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Player as Player;

/**
 * @template-extends EntityRepository<FormationLine>
 */
class Repository extends EntityRepository
{
    /**
     * @use BaseRepository<FormationLine>
     */
    use BaseRepository;

    /**
     * @param int $line
     * @param Player $player
     * @return list<FormationLine>
     */
    public function findByExt(int $line, Player $player): array
    {
        // $exprExists = $this->_em->getExpressionBuilder();

        $query = $this->createQueryBuilder('fl')
            ->distinct()
            ->join('fl.formation', 'f')
            ->join('fl.players', 'pl')
            ->where('fl.number = :line')
            ->andWhere('pl = :player')

//            ->andWhere(
//                $exprExists->exists(
//                    $this->_em->createQueryBuilder()
//                        ->select('gr.id')
//                        ->from('SuperElf\Formation\Line\ViewPeriodPerson', 'flvpp')
//                        ->where('flvpp.viewPeriodPerson = :viewPeriodPerson')
//                        ->getDQL()
//                )
//            )
        ;
        $query = $query->setParameter('line', $line);
        $query = $query->setParameter('player', $player);

        /** @var list<FormationLine> $formationLines */
        $formationLines = $query->getQuery()->getResult();
        return $formationLines;
    }
}
