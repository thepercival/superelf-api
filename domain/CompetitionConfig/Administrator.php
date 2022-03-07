<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig;

use DateTimeImmutable;
use League\Period\Period;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Game\State as GameState;
use SuperElf\CompetitionConfig;
use SuperElf\Defaults;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Points\Creator as PointsCreator;

class Administrator
{

    /**
     * @param list<CompetitionConfig> $existingCompetitionConfigs
     */
    public function __construct(
        protected array $existingCompetitionConfigs
    )
    {
    }

    /**
     * @param Competition $sourceCompetition
     * @param DateTimeImmutable $createAndJoinStart
     * @param Period $assemblePeriodInput
     * @param Period $transferPeriodInput
     * @param list<AgainstGame> $sourceCompetitionGames
     * @return CompetitionConfig
     * @throws \League\Period\Exception
     */
    public function create(
        Competition $sourceCompetition,
        DateTimeImmutable $createAndJoinStart,
        Period $assemblePeriodInput,
        Period $transferPeriodInput,
        array $sourceCompetitionGames
    ): CompetitionConfig {
        $this->validateNonexistance($sourceCompetition);

        $assembleStart = $assemblePeriodInput->getStartDate();
        $season = $sourceCompetition->getSeason();
        $seasonStart = $season->getPeriod()->getStartDate();
        $this->validateCreateAndJoinStart($createAndJoinStart, $seasonStart, $assembleStart);
        $transferStart = $transferPeriodInput->getStartDate();
        $this->validateAssemblePeriod(
            $assemblePeriodInput,
            $createAndJoinStart,
            $transferStart,
            $sourceCompetitionGames
        );
        $assembleEnd = $assemblePeriodInput->getEndDate();
        $seasonEnd = $season->getPeriod()->getEndDate();
        $this->validateTransferPeriod($transferPeriodInput, $assembleEnd, $seasonEnd, $sourceCompetitionGames);

        $assembleEnd = $assemblePeriodInput->getEndDate();
        $assembleViewPeriod = new ViewPeriod(new Period($assembleEnd, $transferPeriodInput->getStartDate()));
        $assemblePeriod = new AssemblePeriod($assemblePeriodInput, $assembleViewPeriod);
        $transferEnd = $transferPeriodInput->getEndDate();
        $transferViewPeriod = new ViewPeriod(new Period($transferEnd, $seasonEnd));
        $transferPeriod = new TransferPeriod($transferPeriodInput, $transferViewPeriod, Defaults::MAXNROFTRANSFERS);

        $newCompetitionConfig = new CompetitionConfig(
            $sourceCompetition,
            (new PointsCreator())->createDefault($season),
            new ViewPeriod(new Period($createAndJoinStart, $assembleEnd)),
            $assemblePeriod,
            $transferPeriod
        );
        $this->existingCompetitionConfigs[] = $newCompetitionConfig;
        return $newCompetitionConfig;
    }

    protected function validateNonexistance(Competition $sourceCompetition): void
    {
        foreach ($this->existingCompetitionConfigs as $competitionConfig) {
            if ($competitionConfig->getSourceCompetition() === $sourceCompetition) {
                $msg = 'competitionConfig for competition "' . $sourceCompetition->getName() . '" already exists';
                throw new \Exception($msg, E_ERROR);
            }
        }
    }

    protected function validateCreateAndJoinStart(
        DateTimeImmutable $createAndJoinStart,
        DateTimeImmutable $seasonStart,
        DateTimeImmutable $assembleStart
    ): void {
        if ($createAndJoinStart < $seasonStart) {
            $msg = 'createAndJoinStart "' . $createAndJoinStart->format('Y-m-d H:i') . '" should be ';
            $msg .= 'after seasonStart "' . $seasonStart->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
        if ($createAndJoinStart > $assembleStart) {
            $msg = 'createAndJoinStart "' . $createAndJoinStart->format('Y-m-d H:i') . '" should be ';
            $msg .= 'before assembleStart "' . $assembleStart->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
    }

    /**
     * @param Period $assemblePeriod
     * @param DateTimeImmutable $createAndJoinStart
     * @param DateTimeImmutable $transferStart
     * @param list<AgainstGame> $sourceCompetitionGames
     * @throws \Exception
     */
    protected function validateAssemblePeriod(
        Period $assemblePeriod,
        DateTimeImmutable $createAndJoinStart,
        DateTimeImmutable $transferStart,
        array $sourceCompetitionGames
    ): void {
        if ($assemblePeriod->getStartDate() < $createAndJoinStart) {
            $msg = 'assembleStart "' . $assemblePeriod->getStartDate()->format('Y-m-d H:i') . '" should be ';
            $msg .= 'after createAndJoinStart "' . $createAndJoinStart->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
        if ($assemblePeriod->getEndDate() > $transferStart) {
            $msg = 'assembleEnd "' . $assemblePeriod->getEndDate()->format('Y-m-d H:i') . '" should be ';
            $msg .= 'before transferStart "' . $transferStart->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
        foreach ($sourceCompetitionGames as $game) {
            if ($assemblePeriod->contains($game->getStartDateTime()) && $game->getState() !== GameState::Canceled) {
                $msg = 'gameStart "' . $game->getStartDateTime()->format('Y-m-d H:i') . '" should be ';
                $msg .= 'before assembleStart "' . $assemblePeriod->getStartDate()->format('Y-m-d H:i') . '"';
                $msg .= ' or after assembleEnd "' . $assemblePeriod->getEndDate()->format('Y-m-d H:i') . '"';
                throw new \Exception($msg, E_ERROR);
            }
        }
    }

    /**
     * @param Period $transferPeriod
     * @param DateTimeImmutable $assembleEnd
     * @param DateTimeImmutable $seasonEnd
     * @param list<AgainstGame> $sourceCompetitionGames
     * @throws \Exception
     */
    protected function validateTransferPeriod(
        Period $transferPeriod,
        DateTimeImmutable $assembleEnd,
        DateTimeImmutable $seasonEnd,
        array $sourceCompetitionGames
    ): void {
        if ($transferPeriod->getStartDate() < $assembleEnd) {
            $msg = 'transferStart "' . $transferPeriod->getStartDate()->format('Y-m-d H:i') . '" should be ';
            $msg .= 'after assembleEnd "' . $assembleEnd->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
        if ($transferPeriod->getEndDate() > $seasonEnd) {
            $msg = 'transferEnd "' . $transferPeriod->getEndDate()->format('Y-m-d H:i') . '" should be ';
            $msg .= 'before seasonEnd "' . $seasonEnd->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
        foreach ($sourceCompetitionGames as $game) {
            if ($transferPeriod->contains($game->getStartDateTime()) && $game->getState() !== GameState::Canceled) {
                $msg = 'gameStart "' . $game->getStartDateTime()->format('Y-m-d H:i') . '" should be ';
                $msg .= 'before transferStart "' . $transferPeriod->getStartDate()->format('Y-m-d H:i') . '"';
                $msg .= ' or after transferEnd "' . $transferPeriod->getEndDate()->format('Y-m-d H:i') . '"';
                throw new \Exception($msg, E_ERROR);
            }
        }
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
