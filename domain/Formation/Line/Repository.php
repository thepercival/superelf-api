<?php
declare(strict_types=1);

namespace SuperElf\Formation\Line;

use Doctrine\ORM\EntityRepository;
use SportsHelpers\Repository as BaseRepository;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Period\View\Person as ViewPeriodPerson;

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
     * @param ViewPeriodPerson $viewPeriodPerson
     * @return array|FormationLine[]
     */
    public function findByExt(int $line, ViewPeriodPerson $viewPeriodPerson  )
    {
        $exprExists = $this->getEM()->getExpressionBuilder();

        $query = $this->createQueryBuilder('fl')
            ->distinct()
            ->join('fl.formation', 'f')
            ->join('fl.viewPeriodPersons', 'vpp')
            ->where('fl.number = :line')
            ->andWhere('vpp = :viewPeriodPerson')

//            ->andWhere(
//                $exprExists->exists(
//                    $this->getEM()->createQueryBuilder()
//                        ->select('gr.id')
//                        ->from('SuperElf\Formation\Line\ViewPeriodPerson', 'flvpp')
//                        ->where('flvpp.viewPeriodPerson = :viewPeriodPerson')
//                        ->getDQL()
//                )
//            )
        ;
        $query = $query->setParameter('line', $line );
        $query = $query->setParameter('viewPeriodPerson', $viewPeriodPerson );

        return $query->getQuery()->getResult();
    }
}
