<?php

declare(strict_types=1);

use App\Repositories\BadgeRepository;
use App\Repositories\BadgeUnviewedRepository;
use App\Repositories\ChatMessageRepository;
use App\Repositories\ChatMessageUnreadRepository;
use App\Repositories\CompetitionConfigRepository;
use App\Repositories\FormationLineRepository;
use App\Repositories\FormationPlaceRepository;
use App\Repositories\PoolCollectionRepository;
use App\Repositories\PoolRepository;
use App\Repositories\S11PlayerRepository;
use App\Repositories\ScoutedPlayerRepository;
use App\Repositories\SeasonRepository;
use App\Repositories\Sports\AgainstGameRepository;
use App\Repositories\Sports\AgainstScoreRepository;
use App\Repositories\Sports\CompetitionRepository;
use App\Repositories\Sports\PersonRepository;
use App\Repositories\Sports\SportRepository;
use App\Repositories\Sports\TeamPlayerRepository;
use App\Repositories\Sports\TogetherGameRepository;
use App\Repositories\Sports\TogetherScoreRepository;
use App\Repositories\StatisticsRepository;
use App\Repositories\TrophyRepository;
use App\Repositories\TrophyUnviewedRepository;
use App\Repositories\ViewPeriodRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Together as TogetherGame;
use Sports\Person;
use Sports\Score\Against as AgainstScore;
use Sports\Score\Together as TogetherScore;
use Sports\Season;
use Sports\Sport;
use Sports\Team\Player as TeamPlayer;
use SportsImport\CacheItemDb;
use SportsImport\Repositories\CacheItemDbRepository;
use SuperElf\Achievement\Badge;
use SuperElf\Achievement\Trophy;
use SuperElf\Achievement\Unviewed\Badge as UnviewedBadge;
use SuperElf\Achievement\Unviewed\Trophy as UnviewedTrophy;
use SuperElf\ChatMessages\ChatMessage;
use SuperElf\ChatMessages\UnreadChatMessage;
use SuperElf\CompetitionConfig;
use SuperElf\Formation\Line as FormationLine;
use SuperElf\Formation\Place as FormationPlace;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use SuperElf\Pool;
use SuperElf\PoolCollection;
use SuperElf\S11Player as S11Player;
use SuperElf\ScoutedPlayer;
use SuperElf\Statistics;

return [
    ViewPeriodRepository::class => function (ContainerInterface $container): ViewPeriodRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(ViewPeriod::class);
        return new ViewPeriodRepository($entityManager, $metaData);
    },
    PoolRepository::class => function (ContainerInterface $container): PoolRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Pool::class);
        return new PoolRepository($entityManager, $metaData);
    },
    ChatMessageRepository::class => function (ContainerInterface $container): ChatMessageRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(ChatMessage::class);
        return new ChatMessageRepository($entityManager, $metaData);
    },
    ChatMessageUnreadRepository::class => function (ContainerInterface $container): ChatMessageUnreadRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(UnreadChatMessage::class);
        return new ChatMessageUnreadRepository($entityManager, $metaData);
    },
    FormationLineRepository::class => function (ContainerInterface $container): FormationLineRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(FormationLine::class);
        return new FormationLineRepository($entityManager, $metaData);
    },
    FormationPlaceRepository::class => function (ContainerInterface $container): FormationPlaceRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(FormationPlace::class);
        return new FormationPlaceRepository($entityManager, $metaData);
    },
    TeamPlayerRepository::class => function (ContainerInterface $container): TeamPlayerRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(TeamPlayer::class);
        return new TeamPlayerRepository($entityManager, $metaData);
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
    SeasonRepository::class => function (ContainerInterface $container): SeasonRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Season::class);
        return new SeasonRepository($entityManager, $metaData);
    },
    CompetitionRepository::class => function (ContainerInterface $container): CompetitionRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Competition::class);
        return new CompetitionRepository($entityManager, $metaData);
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
    TrophyRepository::class => function (ContainerInterface $container): TrophyRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Trophy::class);
        return new TrophyRepository($entityManager, $metaData);
    },
    TrophyUnviewedRepository::class => function (ContainerInterface $container): TrophyUnviewedRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(UnviewedTrophy::class);
        return new TrophyUnviewedRepository($entityManager, $metaData);
    },
    BadgeRepository::class => function (ContainerInterface $container): BadgeRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(Badge::class);
        return new BadgeRepository($entityManager, $metaData);
    },
    BadgeUnviewedRepository::class => function (ContainerInterface $container): BadgeUnviewedRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(UnviewedBadge::class);
        return new BadgeUnviewedRepository($entityManager, $metaData);
    },
    CacheItemDbRepository::class => function (ContainerInterface $container): CacheItemDbRepository {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);
        $metaData = $entityManager->getClassMetadata(CacheItemDb::class);
        return new CacheItemDbRepository($entityManager, $metaData);
    },
];
