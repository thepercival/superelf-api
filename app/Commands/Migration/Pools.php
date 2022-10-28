<?php

namespace App\Commands\Migration;

use App\Command;
use Doctrine\DBAL\Connection as DBConnection;
use Psr\Container\ContainerInterface;
use Sports\Formation as SportsFormation;
use Sports\Person;
use Sports\Person\Repository as PersonRepository;
use Sports\Sport\FootballLine;
use SportsImport\Attacher\Person\Repository as PersonAttacherRepository;
use SportsImport\ExternalSource;
use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\SofaScore;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Formation;
use SuperElf\Formation\Editor as FormationEditor;
use SuperElf\Formation\Place\Repository as FormationPlaceRepository;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Player\Syncer as S11PlayerSyncer;
use SuperElf\Pool;
use SuperElf\Pool\Administrator as PoolAdministrator;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\User\Repository as UserRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * create competitionSeason
 * php bin/console.php app:competitionconfig create --league=Eredivisie --season=2014/2015 --createAndJoinStart="2014-07-23 12:00" --assemblePeriod="2014-08-23 12:00=>2014-09-23 12:00"  --transferPeriod="2015-02-01 12:00=>2015-02-03 12:00" --loglevel=200
 * migrate competitionConfig
 * php bin/console.php app:migrate-pools --league=eredivisie --season=2015/2016 --loglevel=200
 */
class Pools extends Command
{
    protected UserRepository $userRepos;
    protected PoolRepository $poolRepos;
    protected PoolUserRepository $poolUserRepos;
    protected FormationPlaceRepository $formationPlaceRepos;
    protected S11PlayerSyncer $s11PlayerSyncer;
    protected PersonRepository $personRepos;
    protected PersonAttacherRepository $personAttacherRepos;
    protected PoolAdministrator $poolAdministrator;
    protected CompetitionConfigRepository $competitionConfigRepos;
    protected S11PlayerRepository $s11PlayerRepos;
    protected DBConnection $migrationConn;
    protected ExternalSource|null $externalSource;

    public function __construct(ContainerInterface $container)
    {
        /** @var UserRepository $userRepos */
        $userRepos = $container->get(UserRepository::class);
        $this->userRepos = $userRepos;

        /** @var PoolRepository $poolRepos */
        $poolRepos = $container->get(PoolRepository::class);
        $this->poolRepos = $poolRepos;

        /** @var PoolUserRepository $poolUserRepos */
        $poolUserRepos = $container->get(PoolUserRepository::class);
        $this->poolUserRepos = $poolUserRepos;

        /** @var FormationPlaceRepository $formationPlaceRepos */
        $formationPlaceRepos = $container->get(FormationPlaceRepository::class);
        $this->formationPlaceRepos = $formationPlaceRepos;

        /** @var S11PlayerSyncer $s11PlayerSyncer */
        $s11PlayerSyncer = $container->get(S11PlayerSyncer::class);
        $this->s11PlayerSyncer = $s11PlayerSyncer;

        /** @var PersonRepository $personRepos */
        $personRepos = $container->get(PersonRepository::class);
        $this->personRepos = $personRepos;

        /** @var PersonAttacherRepository $personAttacherRepos */
        $personAttacherRepos = $container->get(PersonAttacherRepository::class);
        $this->personAttacherRepos = $personAttacherRepos;

        /** @var S11PlayerRepository $s11PlayerRepos */
        $s11PlayerRepos = $container->get(S11PlayerRepository::class);
        $this->s11PlayerRepos = $s11PlayerRepos;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var PoolAdministrator $poolAdministrator */
        $poolAdministrator = $container->get(PoolAdministrator::class);
        $this->poolAdministrator = $poolAdministrator;

        /** @var ExternalSourceFactory $externalSourceFactory */
        $externalSourceFactory = $container->get(ExternalSourceFactory::class);
        $this->externalSource = $externalSourceFactory->createByName(SofaScore::NAME)?->getExternalSource();

        /** @var DBConnection $migrationConn */
        $migrationConn = $container->get(DBConnection::class);
        $this->migrationConn = $migrationConn;

        parent::__construct($container);
    }

    protected function configure(): void
    {
        $this
            ->setName('app:migrate-pools')
            ->setDescription('migrates the pools')
            ->setHelp('migrates the pools');


//        $f = CompetitionConfig::DateTimeFormat;
        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');


        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-migrate-pools');

        try {
            $compConfig = $this->inputHelper->getCompetitionConfigFromInput($input);
            // -------- REMOVE ----------- //
            $pools = $this->poolRepos->findBy(['competitionConfig' => $compConfig]);
            while ($pool = array_pop($pools)) {
                $this->poolRepos->remove($pool);
            }

            // -------- CREATE ----------- //
            $comp = $compConfig->getSourceCompetition();

            $sql = 'SELECT 	p.Name as Name, p.Id as PoolId ';
            $sql .= ', (select u.LoginName from UsersPerPool pu join UsersExt u on u.id = pu.UserId where PoolId = p.id and admin = 1) as AdminUserName ';
            $sql .= 'FROM 	Pools p ';
            $sql .= 'join CompetitionsPerSeason cs on cs.id = p.CompetitionsPerSeasonId ';
            $sql .= 'join Competitions c on c.id = cs.CompetitionId ';
            $sql .= 'join Seasons s on s.id = cs.SeasonId ';
            $sql .= "where	c.name = '" . $comp->getLeague()->getName() . "'";
            $sql .= "and s.name = '" . $comp->getSeason()->getName() . "'";
            $sql .= "and (select count(*) from UsersPerPool pu where pu.PoolId = p.Id) > 1";

            $stmt = $this->migrationConn->executeQuery($sql);

            while (($row = $stmt->fetchAssociative()) !== false) {
                $user = $this->userRepos->findOneBy(['name' => $row['AdminUserName']]);
                if ($user === null) {
                    throw new \Exception('adminuser "' . $row['AdminUserName'] . '" could not be found', E_ERROR);
                }

                $pool = $this->poolAdministrator->createPool($compConfig, $row['Name'], $user);
                $this->getLogger()->info('pool "' . $pool->getName() . '" created');

                $this->createUsers($pool, (int)$row['PoolId']);
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }


    protected function createUsers(Pool $pool, int $oldPoolId): void
    {
        // -------- CREATE ----------- //
        $sql = 'SELECT u.LoginName as userName, pu.Id as oldPoolUserId ';
        $sql .= 'FROM UsersPerPool pu ';
        $sql .= 'join UsersExt u on u.Id = pu.UserId ';
        $sql .= "where pu.PoolId = " . $oldPoolId . ' ';
        $sql .= 'and pu.Admin = 0 ';
        $sql .= 'order by u.LoginName';

        $stmt = $this->migrationConn->executeQuery($sql);

        while (($row = $stmt->fetchAssociative()) !== false) {
            $user = $this->userRepos->findOneBy(['name' => $row['userName']]);
            if ($user === null) {
                throw new \Exception('pooluser for user "' . $row['userName'] . '" could not be found', E_ERROR);
            }

            $newPoolUser = $this->poolAdministrator->addUser($pool, $user, false);
            $this->poolUserRepos->save($newPoolUser);
            $this->getLogger()->info('  pooluser "' . $newPoolUser->getUser()->getName() . '" created');

            $this->createAssembleFormation($newPoolUser, $row['oldPoolUserId']);

            $this->createTransferFormation($newPoolUser, $row['oldPoolUserId']);
            // break;
        }
    }

    protected function createAssembleFormation(PoolUser $poolUser, int $oldPoolUserId): void
    {
        $assemblePeriod = $poolUser->getPool()->getAssemblePeriod();
        $assembleStart = $assemblePeriod->getStartDateTime();
        $assembleEnd = $assemblePeriod->getEndDateTime();

        // -------- CREATE ----------- //
        $sql = 'SELECT p.FirstName, p.LastName, p.DateOfBirth, p.ExternId, b.StartDateTime, b.EndDateTime, b.Line, b.Number ';
        $sql .= 'FROM Bets b ';
        $sql .= 'join UsersPerPool pu on b.UsersPerPoolId = pu.Id ';
        $sql .= 'join Persons p on b.PersonId = p.Id ';
        $sql .= 'where pu.Id = ' . $oldPoolUserId . ' ';
        $sql .= 'and b.StartDateTime >= "' . $assembleStart->format('Y-m-d H:i:s') . '" ';
        $sql .= 'and b.StartDateTime <= "' . $assembleEnd->modify('+2 hours')->format('Y-m-d H:i:s') . '" ';
        $sql .= 'order by b.Line, b.Number ';

        $stmt = $this->migrationConn->executeQuery($sql);
        $betRows = $stmt->fetchAllAssociative();

        $sportsFormation = $this->createSportsFormation($betRows);
        $formation = (new FormationEditor($this->config))->createAssemble($poolUser, $sportsFormation);
        $poolUser->setAssembleFormation($formation);
        $this->poolUserRepos->save($poolUser);
        $this->getLogger()->info('      assembleformation "' . $sportsFormation->getName() . '" created');
        $this->setFormationPlaces($formation, $betRows);
    }

    protected function createSportsFormation(array $betRows): SportsFormation
    {
        $formation = new SportsFormation();

        foreach (FootballLine::cases() as $line) {
            $lineBetRows = array_filter($betRows, function ($betRow) use ($line) {
                return $betRow['Line'] === $line->value;
            });
            new SportsFormation\Line($formation, $line->value, count($lineBetRows) - 1);
        }
        return $formation;
    }

    protected function setFormationPlaces(Formation $formation, array $betRows): void
    {
        foreach ($betRows as $betRow) {
            $formationLine = $formation->getLine((int)$betRow['Line']);
            $number = $betRow['Number'] > $formationLine->getStartingPlaces()->count() ? 0 : $betRow['Number'];
            $formationPlace = $formationLine->getPlace($number);

            $person = $this->getPersonFromBetRow(
                $betRow['FirstName'],
                $betRow['LastName'],
                $betRow['ExternId'],
                $betRow['DateOfBirth']
            );

            $filter = ['person' => $person, 'viewPeriod' => $formation->getViewPeriod()];
            $s11Player = $this->s11PlayerRepos->findOneBy($filter);
            if ($s11Player === null) {
                // throw new \Exception('for person "'. $person->getName().'" and "' . $formation->getViewPeriod() . '" y no s11player found', E_ERROR);
                $s11Player = $this->s11PlayerSyncer->syncS11Player($formation->getViewPeriod(), $person);
            }

            $formationPlace->setPlayer($s11Player);

            $this->formationPlaceRepos->save($formationPlace);

            $placeMsg = FootballLine::getFirstChar(FootballLine::from($formationLine->getNumber()));
            $placeMsg .= ' ' . $number . ' ' . $person->getName();
            $this->getLogger()->info('          ' . $placeMsg);
        }
    }

    protected function getPersonFromBetRow(
        string $firstName,
        string $lastName,
        string|null $externalId,
        string|null $dateOfBirth
    ): Person
    {
        if ($externalId !== null and strlen($externalId) > 0 && $this->externalSource !== null) {
            $externalIdTmp = substr($externalId, 7);
            $person = $this->personAttacherRepos->findImportable($this->externalSource, $externalIdTmp);
            if ($person !== null) {
                return $person;
            }
        }

//        if ($lastName === 'El Ahmadi') {
//            $er = 23;
//        }
        $persons = $this->personRepos->findBy(['firstName' => $firstName, 'lastName' => $lastName]);
        if (count($persons) > 1) {
            throw new \Exception(
                'no person could be found for firstName "' . $firstName . '" and lastName "' . $lastName . '" (dateOfBirth="' . $dateOfBirth . '")',
                E_ERROR
            );
        } elseif (count($persons) === 0) {
            $nameAnalyzer = new ExternalSource\NameAnalyzer($lastName);
            $maybeNameInsertions = explode(' ', $lastName);
            if (!(count($maybeNameInsertions) === 2 && $nameAnalyzer->getNameInsertions(
                ) === $maybeNameInsertions[0])) {
                throw new \Exception(
                    'no person could be found for firstName "' . $firstName . '" and lastName "' . $lastName . '" (dateOfBirth="' . $dateOfBirth . '")',
                    E_ERROR
                );
            }
            $persons = $this->personRepos->findBy(['firstName' => $firstName, 'lastName' => $maybeNameInsertions[1]]);
            if (count($persons) !== 1) {
                throw new \Exception(
                    'no person could be found for firstName "' . $firstName . '" and lastName "' . $lastName . '" (dateOfBirth="' . $dateOfBirth . '")',
                    E_ERROR
                );
            }
        }

        $person = reset($persons);
        if ($person === false) {
            throw new \Exception(
                'no person could be found for firstName "' . $firstName . '" and lastName "' . $lastName . '" (dateOfBirth="' . $dateOfBirth . '")',
                E_ERROR
            );
        }
        return $person;
    }

    protected function createTransferFormation(PoolUser $poolUser, int $oldPoolUserId): void
    {
        $transferPeriod = $poolUser->getPool()->getTransferPeriod();
        $transferStart = $transferPeriod->getStartDateTime();
        $transferEnd = $transferPeriod->getEndDateTime();

        // -------- CREATE ----------- //
        $sql = 'SELECT p.FirstName, p.LastName, p.DateOfBirth, p.ExternId, b.StartDateTime, b.EndDateTime, b.Line, b.Number ';
        $sql .= 'FROM Bets b ';
        $sql .= 'join UsersPerPool pu on b.UsersPerPoolId = pu.Id ';
        $sql .= 'join Persons p on b.PersonId = p.Id ';
        $sql .= 'where pu.Id = ' . $oldPoolUserId . ' ';
        $sql .= 'and b.EndDateTime > "' . $transferEnd->format('Y-m-d H:i:s') . '" ';
        $sql .= 'order by b.Line, b.Number ';

        $stmt = $this->migrationConn->executeQuery($sql);
        $betRows = $stmt->fetchAllAssociative();

        $sportsFormation = $this->createSportsFormation($betRows);
        $formation = (new FormationEditor($this->config))->createTransfer($poolUser, $sportsFormation);
        $poolUser->setTransferFormation($formation);
        $this->poolUserRepos->save($poolUser);
        $this->getLogger()->info('      transferformation "' . $sportsFormation->getName() . '" created');
        $this->setFormationPlaces($formation, $betRows);
    }


    //  als person niet gevonden(tijdelijk kijken als persoon gevonden kan worden op achternaam,
    // person toevoegen
    //
    /*
    protected function updatePersonDEP( Voetbal_Extern_Game_Participation $oExternParticipation ): ?Voetbal_Person {

        $oExternPlayerPeriod = $oExternParticipation->getPlayerPeriod();

        $sId = $oExternPlayerPeriod->getPerson()->getId();
        $sName = $oExternPlayerPeriod->getPerson()->getName();

        $oTeam = Voetbal_Team_Factory::createObjectFromDatabaseByExtern( $oExternPlayerPeriod->getTeam() );
        $oDateTime = $oExternParticipation->getGame()->getStartDateTime();
        $oOptions = MemberShip_Factory::getMembershipFilters("Voetbal_Team_Membership_Player", $oTeam, null, $oDateTime );
        $oPlayerMemberships = Voetbal_Team_Membership_Player_Factory::createObjectsFromDatabase( $oOptions );

        // zoek op deze naam, als gevonden dan update met Person::ExternId met $oExternPlayerPeriod->getPerson()->getId()
        $oPlayer = $this->filterDEP( $oPlayerMemberships->getArrayCopy(), $sName );
        if( $oPlayer === null ) {
            return null;
        }
        $oPerson = $oPlayer->getClient();
        $oDbWriter = Voetbal_Person_Factory::createDbWriter();
        $oPerson->addObserver( $oDbWriter );
        $oPerson->putExternId( Import_Factory::$m_szExternPrefix . $sId );
        $oDbWriter->write();
        return $oPerson;
    }

    protected function filterDEP( array $arrPlayerMemberships, string $sName ): ?Voetbal_Team_Membership_Player {

        list ($sFirstName, $sNameInsertions, $sLastName) = Voetbal_Person_Factory::getNameParts( $sName );
        if( count($arrPlayerMemberships ) === 1 ) {
            return reset($arrPlayerMemberships);
        }
        $arrLastName = array_filter( $arrPlayerMemberships, function( Voetbal_Team_Membership_Player $oPlayer ) use ($sLastName) {
            $sPlayerLastName = $oPlayer->getClient()->getLastName();
            return strcmp( $this->convertString($sLastName), $this->convertString($sPlayerLastName)) === 0;
        });
        if( count($arrLastName ) === 1 ) {
            return reset($arrLastName);
        } else if( count($arrLastName ) > 1 ) {
            $arrPlayerMemberships = $arrLastName;
        }
        $arrFirstName = array_filter( $arrPlayerMemberships, function( Voetbal_Team_Membership_Player $oPlayer ) use ($sFirstName) {
            $sPlayerFirstName = $oPlayer->getClient()->getFirstName();
            return strcmp( $this->convertString($sFirstName), $this->convertString($sPlayerFirstName)) === 0;
        });
        if( count($arrFirstName ) === 1 ) {
            return reset($arrFirstName);
        }
        return null;
    }*/
}
