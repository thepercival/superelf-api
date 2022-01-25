<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Container\ContainerInterface;
use Sports\Association;
use Sports\Association\Repository as AssociationRepository;
use Sports\Competition;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Competitor\Team\Repository as TeamCompetitorRepository;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\Together as TogetherGame;
use Sports\Game\Together\Repository as TogetherGameRepository;
use Sports\League;
use Sports\League\Repository as LeagueRepository;
use Sports\Person;
use Sports\Person\Repository as PersonRepository;
use Sports\Score\Against as AgainstScore;
use Sports\Score\Against\Repository as AgainstScoreRepository;
use Sports\Score\Together as TogetherScore;
use Sports\Score\Together\Repository as TogetherScoreRepository;
use Sports\Season;
use Sports\Season\Repository as SeasonRepository;
use Sports\Sport;
use Sports\Sport\Repository as SportRepository;
use Sports\Team;
use Sports\Team\Player;
use Sports\Team\Player\Repository as PlayerRepository;
use Sports\Team\Repository as TeamRepository;
use SportsImport\Attacher\Association as AssociationAttacher;
use SportsImport\Attacher\Association\Repository as AssociationAttacherRepository;
use SportsImport\Attacher\Competition as CompetitionAttacher;
use SportsImport\Attacher\Competition\Repository as CompetitionAttacherRepository;
use SportsImport\Attacher\Competitor\Team as TeamCompetitorAttacher;
use SportsImport\Attacher\Competitor\Team\Repository as TeamCompetitorAttacherRepository;
use SportsImport\Attacher\Game\Against as AgainstGameAttacher;
use SportsImport\Attacher\Game\Against\Repository as AgainstGameAttacherRepository;
use SportsImport\Attacher\League as LeagueAttacher;
use SportsImport\Attacher\League\Repository as LeagueAttacherRepository;
use SportsImport\Attacher\Person as PersonAttacher;
use SportsImport\Attacher\Person\Repository as PersonAttacherRepository;
use SportsImport\Attacher\Season as SeasonAttacher;
use SportsImport\Attacher\Season\Repository as SeasonAttacherRepository;
use SportsImport\Attacher\Sport as SportAttacher;
use SportsImport\Attacher\Sport\Repository as SportAttacherRepository;
use SportsImport\Attacher\Team as TeamAttacher;
use SportsImport\Attacher\Team\Repository as TeamAttacherRepository;
use SportsImport\CacheItemDb;
use SportsImport\CacheItemDb\Repository as CacheItemDbRepository;
use SportsImport\ExternalSource;
use SportsImport\ExternalSource\Repository as ExternalSourceRepository;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Competitor;
use SuperElf\Competitor\Repository as CompetitorRepository;
use SuperElf\Formation;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Formation\Line\Repository as FormationLineRepository;
use SuperElf\Formation\Place as FormationPlayer;
use SuperElf\Formation\Place\Repository as FormationPlayerRepository;
use SuperElf\Formation\Repository as FormationRepository;
use SuperElf\GameRound;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Period\Assemble as AssemblePeriod;
use SuperElf\Period\Assemble\Repository as AssemblePeriodRepository;
use SuperElf\Period\Transfer as TransferPeriod;
use SuperElf\Period\Transfer\Repository as TransferPeriodRepository;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Player as S11Player;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Player\Totals as S11PlayerTotals;
use SuperElf\Player\Totals\Repository as S11PlayerTotalsRepository;
use SuperElf\Points;
use SuperElf\Points\Repository as PointsRepository;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\PoolCollection;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\ScoutedPlayer;
use SuperElf\ScoutedPlayer\Repository as ScoutedPlayerRepository;
use SuperElf\Statistics;
use SuperElf\Statistics\Repository as StatisticsRepository;
use SuperElf\Substitute\Appearance as SubstituteAppearance;
use SuperElf\Substitute\Appearance\Repository as SubstituteAppearanceRepository;
use SuperElf\Substitution;
use SuperElf\Substitution\Repository as SubstitutionRepository;
use SuperElf\Transfer;
use SuperElf\Transfer\Repository as TransferRepository;
use SuperElf\User;
use SuperElf\User\Repository as UserRepository;

return [
    UserRepository::class => function (ContainerInterface $container): UserRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<User> $metaData */
        $metaData = $entityManager->getClassMetadata(User::class);
        return new UserRepository($entityManager, $metaData);
    },
    ViewPeriodRepository::class => function (ContainerInterface $container): ViewPeriodRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<ViewPeriod> $metaData */
        $metaData = $entityManager->getClassMetadata(ViewPeriod::class);
        return new ViewPeriodRepository($entityManager, $metaData);
    },
    AssemblePeriodRepository::class => function (ContainerInterface $container): AssemblePeriodRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<AssemblePeriod> $metaData */
        $metaData = $entityManager->getClassMetadata(AssemblePeriod::class);
        return new AssemblePeriodRepository($entityManager, $metaData);
    },
    TransferPeriodRepository::class => function (ContainerInterface $container): TransferPeriodRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<TransferPeriod> $metaData */
        $metaData = $entityManager->getClassMetadata(TransferPeriod::class);
        return new TransferPeriodRepository($entityManager, $metaData);
    },
    TransferRepository::class => function (ContainerInterface $container): TransferRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Transfer> $metaData */
        $metaData = $entityManager->getClassMetadata(Transfer::class);
        return new TransferRepository($entityManager, $metaData);
    },
    SubstitutionRepository::class => function (ContainerInterface $container): SubstitutionRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Substitution> $metaData */
        $metaData = $entityManager->getClassMetadata(Substitution::class);
        return new SubstitutionRepository($entityManager, $metaData);
    },
    PoolRepository::class => function (ContainerInterface $container): PoolRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Pool> $metaData */
        $metaData = $entityManager->getClassMetadata(Pool::class);
        return new PoolRepository($entityManager, $metaData);
    },
    PoolUserRepository::class => function (ContainerInterface $container): PoolUserRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<PoolUser> $metaData */
        $metaData = $entityManager->getClassMetadata(PoolUser::class);
        return new PoolUserRepository($entityManager, $metaData);
    },
    FormationRepository::class => function (ContainerInterface $container): FormationRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Formation> $metaData */
        $metaData = $entityManager->getClassMetadata(Formation::class);
        return new FormationRepository($entityManager, $metaData);
    },
    FormationLineRepository::class => function (ContainerInterface $container): FormationLineRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<FormationLine> $metaData */
        $metaData = $entityManager->getClassMetadata(FormationLine::class);
        return new FormationLineRepository($entityManager, $metaData);
    },
    SubstituteAppearanceRepository::class => function (ContainerInterface $container): SubstituteAppearanceRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<SubstituteAppearance> $metaData */
        $metaData = $entityManager->getClassMetadata(SubstituteAppearance::class);
        return new SubstituteAppearanceRepository($entityManager, $metaData);
    },
    FormationPlayerRepository::class => function (ContainerInterface $container): FormationPlayerRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<FormationPlayer> $metaData */
        $metaData = $entityManager->getClassMetadata(FormationPlayer::class);
        return new FormationPlayerRepository($entityManager, $metaData);
    },
    PoolCollectionRepository::class => function (ContainerInterface $container): PoolCollectionRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<PoolCollection> $metaData */
        $metaData = $entityManager->getClassMetadata(PoolCollection::class);
        return new PoolCollectionRepository($entityManager, $metaData);
    },
    S11PlayerRepository::class => function (ContainerInterface $container): S11PlayerRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<S11Player> $metaData */
        $metaData = $entityManager->getClassMetadata(S11Player::class);
        return new S11PlayerRepository($entityManager, $metaData);
    },
    S11PlayerTotalsRepository::class => function (ContainerInterface $container): S11PlayerTotalsRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<S11PlayerTotals> $metaData */
        $metaData = $entityManager->getClassMetadata(S11PlayerTotals::class);
        return new S11PlayerTotalsRepository($entityManager, $metaData);
    },
    PointsRepository::class => function (ContainerInterface $container): PointsRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Points> $metaData */
        $metaData = $entityManager->getClassMetadata(Points::class);
        return new PointsRepository($entityManager, $metaData);
    },
    GameRoundRepository::class => function (ContainerInterface $container): GameRoundRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<GameRound> $metaData */
        $metaData = $entityManager->getClassMetadata(GameRound::class);
        return new GameRoundRepository($entityManager, $metaData);
    },
    StatisticsRepository::class => function (ContainerInterface $container): StatisticsRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Statistics> $metaData */
        $metaData = $entityManager->getClassMetadata(Statistics::class);
        return new StatisticsRepository($entityManager, $metaData);
    },
    ScoutedPlayerRepository::class => function (ContainerInterface $container): ScoutedPlayerRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<ScoutedPlayer> $metaData */
        $metaData = $entityManager->getClassMetadata(ScoutedPlayer::class);
        return new ScoutedPlayerRepository($entityManager, $metaData);
    },
    CompetitorRepository::class => function (ContainerInterface $container): CompetitorRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Competitor> $metaData */
        $metaData = $entityManager->getClassMetadata(Competitor::class);
        return new CompetitorRepository($entityManager, $metaData);
    },
    CompetitionConfigRepository::class => function (ContainerInterface $container): CompetitionConfigRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<CompetitionConfig> $metaData */
        $metaData = $entityManager->getClassMetadata(CompetitionConfig::class);
        return new CompetitionConfigRepository($entityManager, $metaData);
    },
    SportRepository::class => function (ContainerInterface $container): SportRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Sport> $metaData */
        $metaData = $entityManager->getClassMetadata(Sport::class);
        return new SportRepository($entityManager, $metaData);
    },
    SportAttacherRepository::class => function (ContainerInterface $container): SportAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<SportAttacher> $metaData */
        $metaData = $entityManager->getClassMetadata(SportAttacher::class);
        return new SportAttacherRepository($entityManager, $metaData);
    },
    AssociationRepository::class => function (ContainerInterface $container): AssociationRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Association> $metaData */
        $metaData = $entityManager->getClassMetadata(Association::class);
        return new AssociationRepository($entityManager, $metaData);
    },
    AssociationAttacherRepository::class => function (ContainerInterface $container): AssociationAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<AssociationAttacher> $metaData */
        $metaData = $entityManager->getClassMetadata(AssociationAttacher::class);
        return new AssociationAttacherRepository($entityManager, $metaData);
    },
    SeasonRepository::class => function (ContainerInterface $container): SeasonRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Season> $metaData */
        $metaData = $entityManager->getClassMetadata(Season::class);
        return new SeasonRepository($entityManager, $metaData);
    },
    SeasonAttacherRepository::class => function (ContainerInterface $container): SeasonAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<SeasonAttacher> $metaData */
        $metaData = $entityManager->getClassMetadata(SeasonAttacher::class);
        return new SeasonAttacherRepository($entityManager, $metaData);
    },
    LeagueRepository::class => function (ContainerInterface $container): LeagueRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<League> $metaData */
        $metaData = $entityManager->getClassMetadata(League::class);
        return new LeagueRepository($entityManager, $metaData);
    },
    LeagueAttacherRepository::class => function (ContainerInterface $container): LeagueAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<LeagueAttacher> $metaData */
        $metaData = $entityManager->getClassMetadata(LeagueAttacher::class);
        return new LeagueAttacherRepository($entityManager, $metaData);
    },
    CompetitionRepository::class => function (ContainerInterface $container): CompetitionRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Competition> $metaData */
        $metaData = $entityManager->getClassMetadata(Competition::class);
        return new CompetitionRepository($entityManager, $metaData);
    },
    CompetitionAttacherRepository::class => function (ContainerInterface $container): CompetitionAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<CompetitionAttacher> $metaData */
        $metaData = $entityManager->getClassMetadata(CompetitionAttacher::class);
        return new CompetitionAttacherRepository($entityManager, $metaData);
    },
    TeamRepository::class => function (ContainerInterface $container): TeamRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Team> $metaData */
        $metaData = $entityManager->getClassMetadata(Team::class);
        return new TeamRepository($entityManager, $metaData);
    },
    TeamAttacherRepository::class => function (ContainerInterface $container): TeamAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<TeamAttacher> $metaData */
        $metaData = $entityManager->getClassMetadata(TeamAttacher::class);
        return new TeamAttacherRepository($entityManager, $metaData);
    },
    PlayerRepository::class => function (ContainerInterface $container): PlayerRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Player> $metaData */
        $metaData = $entityManager->getClassMetadata(Player::class);
        return new PlayerRepository($entityManager, $metaData);
    },
    TeamCompetitorRepository::class => function (ContainerInterface $container): TeamCompetitorRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<TeamCompetitor> $metaData */
        $metaData = $entityManager->getClassMetadata(TeamCompetitor::class);
        return new TeamCompetitorRepository($entityManager, $metaData);
    },
    TeamCompetitorAttacherRepository::class => function (ContainerInterface $container): TeamCompetitorAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<TeamCompetitorAttacher> $metaData */
        $metaData = $entityManager->getClassMetadata(TeamCompetitorAttacher::class);
        return new TeamCompetitorAttacherRepository($entityManager, $metaData);
    },
    ExternalSourceRepository::class => function (ContainerInterface $container): ExternalSourceRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<ExternalSource> $metaData */
        $metaData = $entityManager->getClassMetadata(ExternalSource::class);
        return new ExternalSourceRepository($entityManager, $metaData);
    },
    CacheItemDbRepository::class => function (ContainerInterface $container): CacheItemDbRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<CacheItemDb> $metaData */
        $metaData = $entityManager->getClassMetadata(CacheItemDb::class);
        return new CacheItemDbRepository($entityManager, $metaData);
    },
    AgainstGameRepository::class => function (ContainerInterface $container): AgainstGameRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<AgainstGame> $metaData */
        $metaData = $entityManager->getClassMetadata(AgainstGame::class);
        return new AgainstGameRepository($entityManager, $metaData);
    },
    TogetherGameRepository::class => function (ContainerInterface $container): TogetherGameRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<TogetherGame> $metaData */
        $metaData = $entityManager->getClassMetadata(TogetherGame::class);
        return new TogetherGameRepository($entityManager, $metaData);
    },
    AgainstGameAttacherRepository::class => function (ContainerInterface $container): AgainstGameAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<AgainstGameAttacher> $metaData */
        $metaData = $entityManager->getClassMetadata(AgainstGameAttacher::class);
        return new AgainstGameAttacherRepository($entityManager, $metaData);
    },
    AgainstScoreRepository::class => function (ContainerInterface $container): AgainstScoreRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<AgainstScore> $metaData */
        $metaData = $entityManager->getClassMetadata(AgainstScore::class);
        return new AgainstScoreRepository($entityManager, $metaData);
    },
    TogetherScoreRepository::class => function (ContainerInterface $container): TogetherScoreRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<TogetherScore> $metaData */
        $metaData = $entityManager->getClassMetadata(TogetherScore::class);
        return new TogetherScoreRepository($entityManager, $metaData);
    },
    PersonRepository::class => function (ContainerInterface $container): PersonRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<Person> $metaData */
        $metaData = $entityManager->getClassMetadata(Person::class);
        return new PersonRepository($entityManager, $metaData);
    },
    PersonAttacherRepository::class => function (ContainerInterface $container): PersonAttacherRepository {
        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);
        /** @psalm-var ClassMetadata<PersonAttacher> $metaData */
        $metaData = $entityManager->getClassMetadata(PersonAttacher::class);
        return new PersonAttacherRepository($entityManager, $metaData);
    },
];
