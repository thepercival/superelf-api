<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use SuperElf\User\Repository as UserRepository;
use SuperElf\User;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Period\Assemble\Repository as AssemblePeriodRepository;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Transfer\Repository as TransferPeriodRepository;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\Transfer\Transfer\Repository as TransferRepository;
use SuperElf\Period\Transfer\Transfer;
use SuperElf\Period\Transfer\Substitution\Repository as SubstitutionRepository;
use SuperElf\Period\Transfer\Substitution;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Player as S11Player;

use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool;
use SuperElf\Points\Repository as PointsRepository;
use SuperElf\Points;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\PoolCollection;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Formation\Repository as FormationRepository;
use SuperElf\Formation;
use SuperElf\Formation\Line\Repository as FormationLineRepository;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\ScoutedPerson\Repository as ScoutedPersonRepository;
use SuperElf\ScoutedPerson;
use SuperElf\Competitor\Repository as CompetitorRepository;
use SuperElf\Competitor;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\GameRound;
use SuperElf\Player\GameRoundScore\Repository as PlayerGameRoundScoreRepository;
use SuperElf\Player\GameRoundScore as PlayerGameRoundScore;
use SuperElf\Substitute\Appearance\Repository as SubstituteParticipationRepository;
use SuperElf\Substitute\Appearance as SubstituteParticipation;

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
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together\Repository as TogetherGameRepository;
use Sports\Game\Together as TogetherGame;
use Sports\Score\Against\Repository as AgainstScoreRepository;
use Sports\Score\Against as AgainstScore;
use Sports\Score\Together\Repository as TogetherScoreRepository;
use Sports\Score\Together as TogetherScore;
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
use SportsImport\Attacher\Game\Against\Repository as AgainstGameAttacherRepository;
use SportsImport\Attacher\Game\Against as AgainstGameAttacher;
use SportsImport\Attacher\Person\Repository as PersonAttacherRepository;
use SportsImport\Attacher\Person as PersonAttacher;

return [
    UserRepository::class => function (ContainerInterface $container): UserRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<User> $metaData */
        $metaData = $entityManager->getClassMetaData(User::class);
        return new UserRepository($entityManager, $metaData);
    },
    ViewPeriodRepository::class => function (ContainerInterface $container): ViewPeriodRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<ViewPeriod> $metaData */
        $metaData = $entityManager->getClassMetaData(ViewPeriod::class);
        return new ViewPeriodRepository($entityManager, $metaData);
    },
    AssemblePeriodRepository::class => function (ContainerInterface $container): AssemblePeriodRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<AssemblePeriod> $metaData */
        $metaData = $entityManager->getClassMetaData(AssemblePeriod::class);
        return new AssemblePeriodRepository($entityManager, $metaData);
    },
    TransferPeriodRepository::class => function (ContainerInterface $container): TransferPeriodRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<TransferPeriod> $metaData */
        $metaData = $entityManager->getClassMetaData(TransferPeriod::class);
        return new TransferPeriodRepository($entityManager, $metaData);
    },
    TransferRepository::class => function (ContainerInterface $container): TransferRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Transfer> $metaData */
        $metaData = $entityManager->getClassMetaData(Transfer::class);
        return new TransferRepository($entityManager, $metaData);
    },
    SubstitutionRepository::class => function (ContainerInterface $container): SubstitutionRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Substitution> $metaData */
        $metaData = $entityManager->getClassMetaData(Substitution::class);
        return new SubstitutionRepository($entityManager, $metaData);
    },
    PoolRepository::class => function (ContainerInterface $container): PoolRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Pool> $metaData */
        $metaData = $entityManager->getClassMetaData(Pool::class);
        return new PoolRepository($entityManager, $metaData);
    },
    PoolUserRepository::class => function (ContainerInterface $container): PoolUserRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<PoolUser> $metaData */
        $metaData = $entityManager->getClassMetaData(PoolUser::class);
        return new PoolUserRepository($entityManager, $metaData);
    },
    FormationRepository::class => function (ContainerInterface $container): FormationRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Formation> $metaData */
        $metaData = $entityManager->getClassMetaData(Formation::class);
        return new FormationRepository($entityManager, $metaData);
    },
    FormationLineRepository::class => function (ContainerInterface $container): FormationLineRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<FormationLine> $metaData */
        $metaData = $entityManager->getClassMetaData(FormationLine::class);
        return new FormationLineRepository($entityManager, $metaData);
    },
    PoolCollectionRepository::class => function (ContainerInterface $container): PoolCollectionRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<PoolCollection> $metaData */
        $metaData = $entityManager->getClassMetaData(PoolCollection::class);
        return new PoolCollectionRepository($entityManager, $metaData);
    },
    S11PlayerRepository::class => function (ContainerInterface $container): S11PlayerRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<S11Player> $metaData */
        $metaData = $entityManager->getClassMetaData(S11Player::class);
        return new S11PlayerRepository($entityManager, $metaData);
    },
    PointsRepository::class => function (ContainerInterface $container): PointsRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Points> $metaData */
        $metaData = $entityManager->getClassMetaData(Points::class);
        return new PointsRepository($entityManager, $metaData);
    },
    GameRoundRepository::class => function (ContainerInterface $container): GameRoundRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<GameRound> $metaData */
        $metaData = $entityManager->getClassMetaData(GameRound::class);
        return new GameRoundRepository($entityManager, $metaData);
    },
    ScoutedPersonRepository::class => function (ContainerInterface $container): ScoutedPersonRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<ScoutedPerson> $metaData */
        $metaData = $entityManager->getClassMetaData(ScoutedPerson::class);
        return new ScoutedPersonRepository($entityManager, $metaData);
    },
    CompetitorRepository::class => function (ContainerInterface $container): CompetitorRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Competitor> $metaData */
        $metaData = $entityManager->getClassMetaData(Competitor::class);
        return new CompetitorRepository($entityManager, $metaData);
    },
    SportRepository::class => function (ContainerInterface $container): SportRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Sport> $metaData */
        $metaData = $entityManager->getClassMetaData(Sport::class);
        return new SportRepository($entityManager, $metaData);
    },
    SportAttacherRepository::class => function (ContainerInterface $container): SportAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<SportAttacher> $metaData */
        $metaData = $entityManager->getClassMetaData(SportAttacher::class);
        return new SportAttacherRepository($entityManager, $metaData);
    },
    AssociationRepository::class => function (ContainerInterface $container): AssociationRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Association> $metaData */
        $metaData = $entityManager->getClassMetaData(Association::class);
        return new AssociationRepository($entityManager, $metaData);
    },
    AssociationAttacherRepository::class => function (ContainerInterface $container): AssociationAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<AssociationAttacher> $metaData */
        $metaData = $entityManager->getClassMetaData(AssociationAttacher::class);
        return new AssociationAttacherRepository($entityManager, $metaData);
    },
    SeasonRepository::class => function (ContainerInterface $container): SeasonRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Season> $metaData */
        $metaData = $entityManager->getClassMetaData(Season::class);
        return new SeasonRepository($entityManager, $metaData);
    },
    SeasonAttacherRepository::class => function (ContainerInterface $container): SeasonAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<SeasonAttacher> $metaData */
        $metaData = $entityManager->getClassMetaData(SeasonAttacher::class);
        return new SeasonAttacherRepository($entityManager, $metaData);
    },
    LeagueRepository::class => function (ContainerInterface $container): LeagueRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<League> $metaData */
        $metaData = $entityManager->getClassMetaData(League::class);
        return new LeagueRepository($entityManager, $metaData);
    },
    LeagueAttacherRepository::class => function (ContainerInterface $container): LeagueAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<LeagueAttacher> $metaData */
        $metaData = $entityManager->getClassMetaData(LeagueAttacher::class);
        return new LeagueAttacherRepository($entityManager, $metaData);
    },
    CompetitionRepository::class => function (ContainerInterface $container): CompetitionRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Competition> $metaData */
        $metaData = $entityManager->getClassMetaData(Competition::class);
        return new CompetitionRepository($entityManager, $metaData);
    },
    CompetitionAttacherRepository::class => function (ContainerInterface $container): CompetitionAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<CompetitionAttacher> $metaData */
        $metaData = $entityManager->getClassMetaData(CompetitionAttacher::class);
        return new CompetitionAttacherRepository($entityManager, $metaData);
    },
    TeamRepository::class => function (ContainerInterface $container): TeamRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Team> $metaData */
        $metaData = $entityManager->getClassMetaData(Team::class);
        return new TeamRepository($entityManager, $metaData);
    },
    TeamAttacherRepository::class => function (ContainerInterface $container): TeamAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<TeamAttacher> $metaData */
        $metaData = $entityManager->getClassMetaData(TeamAttacher::class);
        return new TeamAttacherRepository($entityManager, $metaData);
    },
    PlayerRepository::class => function (ContainerInterface $container): PlayerRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Player> $metaData */
        $metaData = $entityManager->getClassMetaData(Player::class);
        return new PlayerRepository($entityManager, $metaData);
    },
    TeamCompetitorRepository::class => function (ContainerInterface $container): TeamCompetitorRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<TeamCompetitor> $metaData */
        $metaData = $entityManager->getClassMetaData(TeamCompetitor::class);
        return new TeamCompetitorRepository($entityManager, $metaData);
    },
    TeamCompetitorAttacherRepository::class => function (ContainerInterface $container): TeamCompetitorAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<TeamCompetitorAttacher> $metaData */
        $metaData = $entityManager->getClassMetaData(TeamCompetitorAttacher::class);
        return new TeamCompetitorAttacherRepository($entityManager, $metaData);
    },
    ExternalSourceRepository::class => function (ContainerInterface $container): ExternalSourceRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<ExternalSource> $metaData */
        $metaData = $entityManager->getClassMetaData(ExternalSource::class);
        return new ExternalSourceRepository($entityManager, $metaData);
    },
    CacheItemDbRepository::class => function (ContainerInterface $container): CacheItemDbRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<CacheItemDb> $metaData */
        $metaData = $entityManager->getClassMetaData(CacheItemDb::class);
        return new CacheItemDbRepository($entityManager, $metaData);
    },
    AgainstGameRepository::class => function (ContainerInterface $container): AgainstGameRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<AgainstGame> $metaData */
        $metaData = $entityManager->getClassMetaData(AgainstGame::class);
        return new AgainstGameRepository($entityManager, $metaData);
    },
    TogetherGameRepository::class => function (ContainerInterface $container): TogetherGameRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<TogetherGame> $metaData */
        $metaData = $entityManager->getClassMetaData(TogetherGame::class);
        return new TogetherGameRepository($entityManager, $metaData);
    },
    AgainstGameAttacherRepository::class => function (ContainerInterface $container): AgainstGameAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<AgainstGameAttacher> $metaData */
        $metaData = $entityManager->getClassMetaData(AgainstGameAttacher::class);
        return new AgainstGameAttacherRepository($entityManager, $metaData);
    },
    AgainstScoreRepository::class => function (ContainerInterface $container): AgainstScoreRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<AgainstScore> $metaData */
        $metaData = $entityManager->getClassMetaData(AgainstScore::class);
        return new AgainstScoreRepository($entityManager, $metaData);
    },
    TogetherScoreRepository::class => function (ContainerInterface $container): TogetherScoreRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<TogetherScore> $metaData */
        $metaData = $entityManager->getClassMetaData(TogetherScore::class);
        return new TogetherScoreRepository($entityManager, $metaData);
    },
    PersonRepository::class => function (ContainerInterface $container): PersonRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<Person> $metaData */
        $metaData = $entityManager->getClassMetaData(Person::class);
        return new PersonRepository($entityManager, $metaData);
    },
    PersonAttacherRepository::class => function (ContainerInterface $container): PersonAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetaData<PersonAttacher> $metaData */
        $metaData = $entityManager->getClassMetaData(PersonAttacher::class);
        return new PersonAttacherRepository($entityManager, $metaData);
    },
];
