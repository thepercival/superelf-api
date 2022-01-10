<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Competition;
use SuperElf\CompetitionConfig;
use SuperElf\Defaults;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Points\Creator as PointsCreator;

class Administrator
{
    //PERIODSTART_CREATE_AND_JOIN="2021-08-01 07:00:00"
    //PERIODSTART_ASSEMBLE="2021-12-10 22:00:00"
    //PERIODEND_ASSEMBLE="2022-12-28 18:00:00"
    //PERIODSTART_TRANSFER="2022-02-01 07:00:00"
    //PERIODEND_TRANSFER="2022-02-05 12:00:00"

//    protected CompetitionsCreator $competitionsCreator;
//    protected PointsCreator $pointsCreator;

    /**
     * @param list<CompetitionConfig> $existingCompetitionConfigs
     */
    public function __construct(
        protected array $existingCompetitionConfigs
//        protected PoolRepository $poolRepos,
//        protected PointsRepository $pointsRepository,
//        protected PeriodAdministrator $periodAdministrator,
//        protected SportAdministrator $sportAdministrator,
//        protected PoolCollectionRepository $poolCollectionRepos,
//        protected LeagueRepository $leagueRepos,
//        protected CompetitionRepository $competitionRepos,
//        protected ActiveConfigService $activeConfigService,
//        protected Configuration $config
    )
    {
//        $this->competitionsCreator = new CompetitionsCreator();
//        $this->pointsCreator = new PointsCreator($this->pointsRepository);
    }

    public function create(
        Competition $sourceCompetition,
        DateTimeImmutable $createAndJoinStart,
        Period $assemblePeriodParam,
        Period $transferPeriodParam
    ): CompetitionConfig {
        $assembleStart = new DateTimeImmutable();
        $assembleEnd = new DateTimeImmutable();
        $assembleViewPeriod = new ViewPeriod(new Period($assembleStart, $assembleEnd));
        $assemblePeriod = new AssemblePeriod($assemblePeriodParam, $assembleViewPeriod);

        $transferStart = new DateTimeImmutable();
        $transferEnd = new DateTimeImmutable();
        $transferViewPeriod = new ViewPeriod(new Period($transferStart, $transferEnd));
        $transferPeriod = new TransferPeriod($transferPeriodParam, $transferViewPeriod, Defaults::MAXNROFTRANSFERS);

        return new CompetitionConfig(
            $sourceCompetition,
            (new PointsCreator())->createDefault(),
            new ViewPeriod(new Period($createAndJoinStart, $assemblePeriod->getEndDateTime())),
            $assemblePeriod,
            $transferPeriod
        );
    }

//    public function createCollection(string $name): PoolCollection
//    {
//        $poolCollection = $this->poolCollectionRepos->findOneByName($name);
//        if ($poolCollection === null) {
//            $poolCollection = new PoolCollection(new Association($name));
//            $this->poolCollectionRepos->save($poolCollection);
//        }
//        return $poolCollection;
//    }
//
//    public function createPool(Competition $sourceCompetition, string $name, User $user): Pool
//    {
//        $poolCollection = $this->createCollection($name);
//        $pool = new Pool(
//            $poolCollection,
//            $sourceCompetition,
//            $this->pointsCreator->get($sourceCompetition->getSeason()),
//            $this->periodAdministrator->getCreateAndJoinPeriod($sourceCompetition),
//            $this->periodAdministrator->getAssemblePeriod($sourceCompetition),
//            $this->periodAdministrator->getTransferPeriod($sourceCompetition)
//        );
//
//        $this->addUser($pool, $user, true);
//        $this->poolRepos->save($pool, true);
//
//        $competitionTypes = $this->sportAdministrator->getCompetitionTypes($pool);
//        $sport = $this->sportAdministrator->getSport();
//        $competitions = $this->competitionsCreator->createCompetitions($pool, $sport, $competitionTypes);
//
//        $association = $pool->getCollection()->getAssociation();
//        // because association(through poolcollection) already exists, doctrine gives error
//        foreach ($competitions as $competition) {
//            $association->getLeagues()->removeElement($competition->getLeague());
//        }
//        foreach ($competitions as $competition) {
//            $this->competitionRepos->save($competition);
//        }
//        $this->poolRepos->save($pool);
//        // undo removal
//        foreach ($competitions as $competition) {
//            $association->getLeagues()->add($competition->getLeague());
//        }
//        return $pool;
//    }
}
