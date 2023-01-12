<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
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
use Sports\Poule;
use Sports\Poule\Repository as PouleRepository;
use Sports\Place;
use Sports\Place\Repository as PlaceRepository;
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
use SuperElf\Season\Repository as S11SeasonRepository;
use SuperElf\ChatMessage;
use SuperElf\ChatMessage\Repository as ChatMessageRepository;
use SuperElf\ChatMessage\Unread as UnreadChatMessage;
use SuperElf\ChatMessage\Unread\Repository as UnreadChatMessageRepository;
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
use SuperElf\Periods\AssemblePeriod as AssemblePeriod;
use SuperElf\Periods\AssemblePeriod\Repository as AssemblePeriodRepository;
use SuperElf\Periods\TransferPeriod as TransferPeriod;
use SuperElf\Periods\TransferPeriod\Repository as TransferPeriodRepository;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use SuperElf\Player as S11Player;
use SuperElf\Player\Repository as S11PlayerRepository;
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
use SuperElf\Totals as S11Totals;
use SuperElf\Totals\Repository as S11TotalsRepository;
use SuperElf\Transfer;
use SuperElf\Transfer\Repository as TransferRepository;
use SuperElf\User;
use SuperElf\User\Repository as UserRepository;

return [
    UserRepository::class => function (ContainerInterface $container): UserRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(User::class);
        return new UserRepository($entityManager, $metaData);
    },
    ViewPeriodRepository::class => function (ContainerInterface $container): ViewPeriodRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(ViewPeriod::class);
        return new ViewPeriodRepository($entityManager, $metaData);
    },
    AssemblePeriodRepository::class => function (ContainerInterface $container): AssemblePeriodRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(AssemblePeriod::class);
        return new AssemblePeriodRepository($entityManager, $metaData);
    },
    TransferPeriodRepository::class => function (ContainerInterface $container): TransferPeriodRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(TransferPeriod::class);
        return new TransferPeriodRepository($entityManager, $metaData);
    },
    TransferRepository::class => function (ContainerInterface $container): TransferRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Transfer::class);
        return new TransferRepository($entityManager, $metaData);
    },
    SubstitutionRepository::class => function (ContainerInterface $container): SubstitutionRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Substitution::class);
        return new SubstitutionRepository($entityManager, $metaData);
    },
    PoolRepository::class => function (ContainerInterface $container): PoolRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Pool::class);
        return new PoolRepository($entityManager, $metaData);
    },
    PoolUserRepository::class => function (ContainerInterface $container): PoolUserRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(PoolUser::class);
        return new PoolUserRepository($entityManager, $metaData);
    },
    ChatMessageRepository::class => function (ContainerInterface $container): ChatMessageRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(ChatMessage::class);
        return new ChatMessageRepository($entityManager, $metaData);
    },
    UnreadChatMessageRepository::class => function (ContainerInterface $container): UnreadChatMessageRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(UnreadChatMessage::class);
        return new UnreadChatMessageRepository($entityManager, $metaData);
    },
    FormationRepository::class => function (ContainerInterface $container): FormationRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Formation::class);
        return new FormationRepository($entityManager, $metaData);
    },
    FormationLineRepository::class => function (ContainerInterface $container): FormationLineRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(FormationLine::class);
        return new FormationLineRepository($entityManager, $metaData);
    },
    SubstituteAppearanceRepository::class => function (ContainerInterface $container): SubstituteAppearanceRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(SubstituteAppearance::class);
        return new SubstituteAppearanceRepository($entityManager, $metaData);
    },
    FormationPlayerRepository::class => function (ContainerInterface $container): FormationPlayerRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(FormationPlayer::class);
        return new FormationPlayerRepository($entityManager, $metaData);
    },
    PoolCollectionRepository::class => function (ContainerInterface $container): PoolCollectionRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(PoolCollection::class);
        return new PoolCollectionRepository($entityManager, $metaData);
    },
    S11PlayerRepository::class => function (ContainerInterface $container): S11PlayerRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(S11Player::class);
        return new S11PlayerRepository($entityManager, $metaData);
    },
    S11TotalsRepository::class => function (ContainerInterface $container): S11TotalsRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(S11Totals::class);
        return new S11TotalsRepository($entityManager, $metaData);
    },
    PointsRepository::class => function (ContainerInterface $container): PointsRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Points::class);
        return new PointsRepository($entityManager, $metaData);
    },
    GameRoundRepository::class => function (ContainerInterface $container): GameRoundRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(GameRound::class);
        return new GameRoundRepository($entityManager, $metaData);
    },
    StatisticsRepository::class => function (ContainerInterface $container): StatisticsRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Statistics::class);
        return new StatisticsRepository($entityManager, $metaData);
    },
    ScoutedPlayerRepository::class => function (ContainerInterface $container): ScoutedPlayerRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(ScoutedPlayer::class);
        return new ScoutedPlayerRepository($entityManager, $metaData);
    },
    CompetitorRepository::class => function (ContainerInterface $container): CompetitorRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Competitor::class);
        return new CompetitorRepository($entityManager, $metaData);
    },
    CompetitionConfigRepository::class => function (ContainerInterface $container): CompetitionConfigRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(CompetitionConfig::class);
        return new CompetitionConfigRepository($entityManager, $metaData);
    },
    SportRepository::class => function (ContainerInterface $container): SportRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Sport::class);
        return new SportRepository($entityManager, $metaData);
    },
    SportAttacherRepository::class => function (ContainerInterface $container): SportAttacherRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(SportAttacher::class);
        return new SportAttacherRepository($entityManager, $metaData);
    },
    AssociationRepository::class => function (ContainerInterface $container): AssociationRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Association::class);
        return new AssociationRepository($entityManager, $metaData);
    },
    AssociationAttacherRepository::class => function (ContainerInterface $container): AssociationAttacherRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(AssociationAttacher::class);
        return new AssociationAttacherRepository($entityManager, $metaData);
    },
    SeasonRepository::class => function (ContainerInterface $container): SeasonRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Season::class);
        return new SeasonRepository($entityManager, $metaData);
    },
    SeasonAttacherRepository::class => function (ContainerInterface $container): SeasonAttacherRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(SeasonAttacher::class);
        return new SeasonAttacherRepository($entityManager, $metaData);
    },
    LeagueRepository::class => function (ContainerInterface $container): LeagueRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(League::class);
        return new LeagueRepository($entityManager, $metaData);
    },
    LeagueAttacherRepository::class => function (ContainerInterface $container): LeagueAttacherRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(LeagueAttacher::class);
        return new LeagueAttacherRepository($entityManager, $metaData);
    },
    CompetitionRepository::class => function (ContainerInterface $container): CompetitionRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Competition::class);
        return new CompetitionRepository($entityManager, $metaData);
    },
    CompetitionAttacherRepository::class => function (ContainerInterface $container): CompetitionAttacherRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(CompetitionAttacher::class);
        return new CompetitionAttacherRepository($entityManager, $metaData);
    },
    TeamRepository::class => function (ContainerInterface $container): TeamRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Team::class);
        return new TeamRepository($entityManager, $metaData);
    },
    TeamAttacherRepository::class => function (ContainerInterface $container): TeamAttacherRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(TeamAttacher::class);
        return new TeamAttacherRepository($entityManager, $metaData);
    },
    PlayerRepository::class => function (ContainerInterface $container): PlayerRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Player::class);
        return new PlayerRepository($entityManager, $metaData);
    },
    TeamCompetitorRepository::class => function (ContainerInterface $container): TeamCompetitorRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(TeamCompetitor::class);
        return new TeamCompetitorRepository($entityManager, $metaData);
    },
    TeamCompetitorAttacherRepository::class => function (ContainerInterface $container): TeamCompetitorAttacherRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(TeamCompetitorAttacher::class);
        return new TeamCompetitorAttacherRepository($entityManager, $metaData);
    },
    ExternalSourceRepository::class => function (ContainerInterface $container): ExternalSourceRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(ExternalSource::class);
        return new ExternalSourceRepository($entityManager, $metaData);
    },
    CacheItemDbRepository::class => function (ContainerInterface $container): CacheItemDbRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(CacheItemDb::class);
        return new CacheItemDbRepository($entityManager, $metaData);
    },
    AgainstGameRepository::class => function (ContainerInterface $container): AgainstGameRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(AgainstGame::class);
        return new AgainstGameRepository($entityManager, $metaData);
    },
    TogetherGameRepository::class => function (ContainerInterface $container): TogetherGameRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(TogetherGame::class);
        return new TogetherGameRepository($entityManager, $metaData);
    },
    AgainstGameAttacherRepository::class => function (ContainerInterface $container): AgainstGameAttacherRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(AgainstGameAttacher::class);
        return new AgainstGameAttacherRepository($entityManager, $metaData);
    },
    AgainstScoreRepository::class => function (ContainerInterface $container): AgainstScoreRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(AgainstScore::class);
        return new AgainstScoreRepository($entityManager, $metaData);
    },
    TogetherScoreRepository::class => function (ContainerInterface $container): TogetherScoreRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(TogetherScore::class);
        return new TogetherScoreRepository($entityManager, $metaData);
    },
    PersonRepository::class => function (ContainerInterface $container): PersonRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Person::class);
        return new PersonRepository($entityManager, $metaData);
    },
    PersonAttacherRepository::class => function (ContainerInterface $container): PersonAttacherRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(PersonAttacher::class);
        return new PersonAttacherRepository($entityManager, $metaData);
    },
    PouleRepository::class => function (ContainerInterface $container): PouleRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Poule::class);
        return new PouleRepository($entityManager, $metaData);
    },
    PlaceRepository::class => function (ContainerInterface $container): PlaceRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Place::class);
        return new PlaceRepository($entityManager, $metaData);
    },
    S11SeasonRepository::class => function (ContainerInterface $container): S11SeasonRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Season::class);
        return new S11SeasonRepository($entityManager, $metaData);
    }
];
