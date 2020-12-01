<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

use SuperElf\User\Repository as UserRepository;
use SuperElf\User;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Period\Assemble\Repository as AssemblePeriodRepository;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer\Repository as TransferPeriodRepository;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\CompetitionPerson\Repository as CompetitionPersonRepository;
use SuperElf\CompetitionPerson;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\PoolCollection;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Formation\Repository as FormationRepository;
use SuperElf\Formation;
use SuperElf\ScoutedPerson\Repository as ScoutedPersonRepository;
use SuperElf\ScoutedPerson;
use SuperElf\Competitor\Repository as CompetitorRepository;
use SuperElf\Competitor;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\GameRound;
use SuperElf\CompetitionPerson\GameRoundScore\Repository as PersonGameRoundScoreRepository;
use SuperElf\CompetitionPerson\GameRoundScore as PersonGameRoundScore;

use SportsImport\ExternalSource\Repository as ExternalSourceRepository;
use SportsImport\ExternalSource;
use SportsImport\CacheItemDb\Repository as CacheItemDbRepository;
use SportsImport\CacheItemDb;

use Sports\Sport\Repository as SportRepository;
use Sports\Sport;
use Sports\Association\Repository as AssociationRepository;
use Sports\Association;
use Sports\Season\Repository as SeasonRepository;
use Sports\Season;
use Sports\League\Repository as LeagueRepository;
use Sports\League;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Competition;
use Sports\Team\Repository as TeamRepository;
use Sports\Team;
use Sports\Competitor\Team\Repository as TeamCompetitorRepository;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Game\Repository as GameRepository;
use Sports\Game;
use Sports\Game\Score\Repository as GameScoreRepository;
use Sports\Game\Score as GameScore;
use Sports\Person\Repository as PersonRepository;
use Sports\Person;
use Sports\Team\Player\Repository as PlayerRepository;
use Sports\Team\Player;

use SportsImport\Attacher\Sport\Repository as SportAttacherRepository;
use SportsImport\Attacher\Sport as SportAttacher;
use SportsImport\Attacher\Association\Repository as AssociationAttacherRepository;
use SportsImport\Attacher\Association as AssociationAttacher;
use SportsImport\Attacher\Season\Repository as SeasonAttacherRepository;
use SportsImport\Attacher\Season as SeasonAttacher;
use SportsImport\Attacher\League\Repository as LeagueAttacherRepository;
use SportsImport\Attacher\League as LeagueAttacher;
use SportsImport\Attacher\Competition\Repository as CompetitionAttacherRepository;
use SportsImport\Attacher\Competition as CompetitionAttacher;
use SportsImport\Attacher\Team\Repository as TeamAttacherRepository;
use SportsImport\Attacher\Team as TeamAttacher;
use SportsImport\Attacher\Competitor\Team\Repository as TeamCompetitorAttacherRepository;
use SportsImport\Attacher\Competitor\Team as TeamCompetitorAttacher;
use SportsImport\Attacher\Game\Repository as GameAttacherRepository;
use SportsImport\Attacher\Game as GameAttacher;
use SportsImport\Attacher\Person\Repository as PersonAttacherRepository;
use SportsImport\Attacher\Person as PersonAttacher;

return [
    UserRepository::class => function (ContainerInterface $container): UserRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new UserRepository($entityManager, $entityManager->getClassMetaData(User::class));
    },
    ViewPeriodRepository::class => function (ContainerInterface $container): ViewPeriodRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new ViewPeriodRepository($entityManager, $entityManager->getClassMetaData(ViewPeriod::class));
    },
    AssemblePeriodRepository::class => function (ContainerInterface $container): AssemblePeriodRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new AssemblePeriodRepository($entityManager, $entityManager->getClassMetaData(AssemblePeriod::class));
    },
    TransferPeriodRepository::class => function (ContainerInterface $container): TransferPeriodRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new TransferPeriodRepository($entityManager, $entityManager->getClassMetaData(TransferPeriod::class));
    },
    PoolRepository::class => function (ContainerInterface $container): PoolRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new PoolRepository($entityManager, $entityManager->getClassMetaData(Pool::class));
    },
    PoolUserRepository::class => function (ContainerInterface $container): PoolUserRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new PoolUserRepository($entityManager, $entityManager->getClassMetaData(PoolUser::class));
    },
    FormationRepository::class => function (ContainerInterface $container): FormationRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new FormationRepository($entityManager, $entityManager->getClassMetaData(Formation::class));
    },
    PoolCollectionRepository::class => function (ContainerInterface $container): PoolCollectionRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new PoolCollectionRepository($entityManager, $entityManager->getClassMetaData(PoolCollection::class));
    },
    CompetitionPersonRepository::class => function (ContainerInterface $container): CompetitionPersonRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new CompetitionPersonRepository($entityManager, $entityManager->getClassMetaData(CompetitionPerson::class));
    },
    PersonGameRoundScoreRepository::class => function (ContainerInterface $container): PersonGameRoundScoreRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new PersonGameRoundScoreRepository($entityManager, $entityManager->getClassMetaData(PersonGameRoundScore::class));
    },
    GameRoundRepository::class => function (ContainerInterface $container): GameRoundRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new GameRoundRepository($entityManager, $entityManager->getClassMetaData(GameRound::class));
    },
    ScoutedPersonRepository::class => function (ContainerInterface $container): ScoutedPersonRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new ScoutedPersonRepository($entityManager, $entityManager->getClassMetaData(ScoutedPerson::class));
    },
    CompetitorRepository::class => function (ContainerInterface $container): CompetitorRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new CompetitorRepository($entityManager, $entityManager->getClassMetaData(Competitor::class));
    },
    SportRepository::class => function (ContainerInterface $container): SportRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new SportRepository($entityManager, $entityManager->getClassMetaData(Sport::class));
    },
    SportAttacherRepository::class => function (ContainerInterface $container): SportAttacherRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new SportAttacherRepository($entityManager, $entityManager->getClassMetaData(SportAttacher::class));
    },
    AssociationRepository::class => function (ContainerInterface $container): AssociationRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new AssociationRepository($entityManager, $entityManager->getClassMetaData(Association::class));
    },
    AssociationAttacherRepository::class => function (ContainerInterface $container): AssociationAttacherRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new AssociationAttacherRepository($entityManager, $entityManager->getClassMetaData(AssociationAttacher::class));
    },
    SeasonRepository::class => function (ContainerInterface $container): SeasonRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new SeasonRepository($entityManager, $entityManager->getClassMetaData(Season::class));
    },
    SeasonAttacherRepository::class => function (ContainerInterface $container): SeasonAttacherRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new SeasonAttacherRepository($entityManager, $entityManager->getClassMetaData(SeasonAttacher::class));
    },
    LeagueRepository::class => function (ContainerInterface $container): LeagueRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new LeagueRepository($entityManager, $entityManager->getClassMetaData(League::class));
    },
    LeagueAttacherRepository::class => function (ContainerInterface $container): LeagueAttacherRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new LeagueAttacherRepository($entityManager, $entityManager->getClassMetaData(LeagueAttacher::class));
    },
    CompetitionRepository::class => function (ContainerInterface $container): CompetitionRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new CompetitionRepository($entityManager, $entityManager->getClassMetaData(Competition::class));
    },
    CompetitionAttacherRepository::class => function (ContainerInterface $container): CompetitionAttacherRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new CompetitionAttacherRepository($entityManager, $entityManager->getClassMetaData(CompetitionAttacher::class));
    },
    TeamRepository::class => function (ContainerInterface $container): TeamRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new TeamRepository($entityManager, $entityManager->getClassMetaData(Team::class));
    },
    TeamAttacherRepository::class => function (ContainerInterface $container): TeamAttacherRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new TeamAttacherRepository($entityManager, $entityManager->getClassMetaData(TeamAttacher::class));
    },
    PlayerRepository::class => function (ContainerInterface $container): PlayerRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new PlayerRepository($entityManager, $entityManager->getClassMetaData(Player::class));
    },
    TeamCompetitorRepository::class => function (ContainerInterface $container): TeamCompetitorRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new TeamCompetitorRepository($entityManager, $entityManager->getClassMetaData(TeamCompetitor::class));
    },
    TeamCompetitorAttacherRepository::class => function (ContainerInterface $container): TeamCompetitorAttacherRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new TeamCompetitorAttacherRepository($entityManager, $entityManager->getClassMetaData(TeamCompetitorAttacher::class));
    },
    ExternalSourceRepository::class => function (ContainerInterface $container): ExternalSourceRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new ExternalSourceRepository($entityManager, $entityManager->getClassMetaData(ExternalSource::class));
    },
    CacheItemDbRepository::class => function (ContainerInterface $container): CacheItemDbRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new CacheItemDbRepository($entityManager, $entityManager->getClassMetaData(CacheItemDb::class));
    },

    GameRepository::class => function (ContainerInterface $container): GameRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new GameRepository($entityManager, $entityManager->getClassMetaData(Game::class));
    },
    GameAttacherRepository::class => function (ContainerInterface $container): GameAttacherRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new GameAttacherRepository($entityManager, $entityManager->getClassMetaData(GameAttacher::class));
    },
    GameScoreRepository::class => function (ContainerInterface $container): GameScoreRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new GameScoreRepository($entityManager, $entityManager->getClassMetaData(GameScore::class));
    },
    PersonRepository::class => function (ContainerInterface $container): PersonRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new PersonRepository($entityManager, $entityManager->getClassMetaData(Person::class));
    },
    PersonAttacherRepository::class => function (ContainerInterface $container): PersonAttacherRepository {
        $entityManager = $container->get(\Doctrine\ORM\EntityManager::class);
        return new PersonAttacherRepository($entityManager, $entityManager->getClassMetaData(PersonAttacher::class));
    },
];
