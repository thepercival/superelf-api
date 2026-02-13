<?php

declare(strict_types=1);

namespace SuperElf\CompetitionConfig;

use DateTimeImmutable;
use League\Period\Period as LeaguePeriod;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use SuperElf\CompetitionConfig;
use SuperElf\Defaults;
use SuperElf\Periods\AssemblePeriod as AssemblePeriod;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use SuperElf\Points\Creator as PointsCreator;

final class Administrator
{
    /**
     * @param list<CompetitionConfig> $existingCompetitionConfigs
     */
    public function __construct(
        protected array $existingCompetitionConfigs
    ) {
    }

    /**
     * @param Competition $sourceCompetition
     * @param DateTimeImmutable $createAndJoinStart
     * @param LeaguePeriod $assemblePeriodInput
     * @param LeaguePeriod $transferPeriodInput
     * @param list<AgainstGame> $sourceCompetitionGames
     * @return CompetitionConfig
     */
    public function create(
        Competition $sourceCompetition,
        DateTimeImmutable $createAndJoinStart,
        LeaguePeriod $assemblePeriodInput,
        LeaguePeriod $transferPeriodInput,
        array $sourceCompetitionGames
    ): CompetitionConfig {
        $this->validateNonexistance($sourceCompetition);

        $this->validatePeriods(
            $sourceCompetition,
            $assemblePeriodInput,
            $createAndJoinStart,
            $transferPeriodInput,
            $sourceCompetitionGames
        );
        $season = $sourceCompetition->getSeason();
        $seasonEnd = $season->getPeriod()->endDate;

        $assembleEnd = $assemblePeriodInput->endDate;
        $assembleViewPeriod = new ViewPeriod(LeaguePeriod::fromDate($assembleEnd, $transferPeriodInput->startDate));
        $assemblePeriod = new AssemblePeriod($assemblePeriodInput, $assembleViewPeriod);
        $transferEnd = $transferPeriodInput->endDate;
        $transferViewPeriod = new ViewPeriod(LeaguePeriod::fromDate($transferEnd, $seasonEnd));
        $transferPeriod = new TransferPeriod($transferPeriodInput, $transferViewPeriod, Defaults::MAXNROFTRANSFERS);

        $newCompetitionConfig = new CompetitionConfig(
            $sourceCompetition,
            (new PointsCreator())->createDefault($season),
            new ViewPeriod(LeaguePeriod::fromDate($createAndJoinStart, $assembleEnd)),
            $assemblePeriod,
            $transferPeriod
        );
        $this->existingCompetitionConfigs[] = $newCompetitionConfig;
        return $newCompetitionConfig;
    }

    /**
     * @param Competition $sourceCompetition
     * @param LeaguePeriod $assemblePeriodInput
     * @param list<AgainstGame> $sourceCompetitionGames
     */
    public function updateAssemblePeriod(
        Competition $sourceCompetition,
        LeaguePeriod $assemblePeriodInput,
        array $sourceCompetitionGames
    ): CompetitionConfig {
        $competitionConfig = $this->getCompetitionConfig($sourceCompetition);

        $this->validatePeriods(
            $sourceCompetition,
            $assemblePeriodInput,
            $competitionConfig->getCreateAndJoinPeriod()->getStartDateTime(),
            $competitionConfig->getTransferPeriod()->getPeriod(),
            $sourceCompetitionGames
        );

        $competitionConfig->updateAssemblePeriod($assemblePeriodInput);
        return $competitionConfig;
    }

    /**
     * @param Competition $sourceCompetition
     * @param LeaguePeriod $transferPeriodInput
     * @param list<AgainstGame> $sourceCompetitionGames
     */
    public function updateTransferPeriod(
        Competition $sourceCompetition,
        LeaguePeriod $transferPeriodInput,
        array $sourceCompetitionGames
    ): CompetitionConfig {
        $competitionConfig = $this->getCompetitionConfig($sourceCompetition);

        $this->validatePeriods(
            $sourceCompetition,
            $competitionConfig->getAssemblePeriod()->getPeriod(),
            $competitionConfig->getCreateAndJoinPeriod()->getStartDateTime(),
            $transferPeriodInput,
            $sourceCompetitionGames
        );

        $competitionConfig->updateTransferPeriod($transferPeriodInput);
        return $competitionConfig;
    }

    /**
     * @param Competition $sourceCompetition
     * @param LeaguePeriod $assemblePeriodInput
     * @param DateTimeImmutable $createAndJoinStart
     * @param LeaguePeriod $transferPeriodInput
     * @param list<AgainstGame> $sourceCompetitionGames
     * @return void
     * @throws \Exception
     */
    private function validatePeriods(
        Competition $sourceCompetition,
        LeaguePeriod $assemblePeriodInput,
        DateTimeImmutable $createAndJoinStart,
        LeaguePeriod $transferPeriodInput,
        array $sourceCompetitionGames
    ): void {
        $assembleStart = $assemblePeriodInput->startDate;
        $season = $sourceCompetition->getSeason();
        $seasonStart = $season->getPeriod()->startDate;
        $this->validateCreateAndJoinStart($createAndJoinStart, $seasonStart, $assembleStart);
        $transferStart = $transferPeriodInput->startDate;
        $this->validateAssemblePeriod(
            $assemblePeriodInput,
            $createAndJoinStart,
            $transferStart,
            $sourceCompetitionGames
        );
        $assembleEnd = $assemblePeriodInput->endDate;
        $seasonEnd = $season->getPeriod()->endDate;
        $this->validateTransferPeriod($transferPeriodInput, $assembleEnd, $seasonEnd, $sourceCompetitionGames);
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

    protected function getCompetitionConfig(Competition $sourceCompetition): CompetitionConfig
    {
        foreach ($this->existingCompetitionConfigs as $competitionConfig) {
            if ($competitionConfig->getSourceCompetition() === $sourceCompetition) {
                return $competitionConfig;
            }
        }
        $msg = 'no competitionConfig for competition "' . $sourceCompetition->getName() . '" found';
        throw new \Exception($msg, E_ERROR);
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
     * @param LeaguePeriod $assemblePeriod
     * @param DateTimeImmutable $createAndJoinStart
     * @param DateTimeImmutable $transferStart
     * @param list<AgainstGame> $sourceCompetitionGames
     * @throws \Exception
     */
    protected function validateAssemblePeriod(
        LeaguePeriod $assemblePeriod,
        DateTimeImmutable $createAndJoinStart,
        DateTimeImmutable $transferStart,
        array $sourceCompetitionGames
    ): void {
        if ($assemblePeriod->startDate < $createAndJoinStart) {
            $msg = 'assembleStart "' . $assemblePeriod->startDate->format('Y-m-d H:i') . '" should be ';
            $msg .= 'after createAndJoinStart "' . $createAndJoinStart->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
        if ($assemblePeriod->endDate > $transferStart) {
            $msg = 'assembleEnd "' . $assemblePeriod->endDate->format('Y-m-d H:i') . '" should be ';
            $msg .= 'before transferStart "' . $transferStart->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
//        foreach ($sourceCompetitionGames as $game) {
//            if ($assemblePeriod->contains($game->getStartDateTime()) && $game->getState() !== GameState::Canceled) {
//                $msg = 'gameStart "' . $game->getStartDateTime()->format('Y-m-d H:i') . '" should be ';
//                $msg .= 'before assembleStart "' . $assemblePeriod->getStartDate()->format('Y-m-d H:i') . '"';
//                $msg .= ' or after assembleEnd "' . $assemblePeriod->getEndDate()->format('Y-m-d H:i') . '"';
//                throw new \Exception($msg, E_ERROR);
//            }
//        }
    }

    /**
     * @param LeaguePeriod $transferPeriod
     * @param DateTimeImmutable $assembleEnd
     * @param DateTimeImmutable $seasonEnd
     * @param list<AgainstGame> $sourceCompetitionGames
     * @throws \Exception
     */
    protected function validateTransferPeriod(
        LeaguePeriod $transferPeriod,
        DateTimeImmutable $assembleEnd,
        DateTimeImmutable $seasonEnd,
        array $sourceCompetitionGames
    ): void {
        if ($transferPeriod->startDate < $assembleEnd) {
            $msg = 'transferStart "' . $transferPeriod->startDate->format('Y-m-d H:i') . '" should be ';
            $msg .= 'after assembleEnd "' . $assembleEnd->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
        if ($transferPeriod->endDate > $seasonEnd) {
            $msg = 'transferEnd "' . $transferPeriod->endDate->format('Y-m-d H:i') . '" should be ';
            $msg .= 'before seasonEnd "' . $seasonEnd->format('Y-m-d H:i') . '"';
            throw new \Exception($msg, E_ERROR);
        }
//        foreach ($sourceCompetitionGames as $game) {
//            if ($transferPeriod->contains($game->getStartDateTime()) && $game->getState() !== GameState::Canceled) {
//                $msg = 'gameStart "' . $game->getStartDateTime()->format('Y-m-d H:i') . '" should be ';
//                $msg .= 'before transferStart "' . $transferPeriod->getStartDate()->format('Y-m-d H:i') . '"';
//                $msg .= ' or after transferEnd "' . $transferPeriod->getEndDate()->format('Y-m-d H:i') . '"';
//                throw new \Exception($msg, E_ERROR);
//            }
//        }
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
