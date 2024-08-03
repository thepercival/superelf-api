<?php

declare(strict_types=1);

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

require 'vendor/autoload.php';

$settings = include 'config/settings.php';
$settings = $settings['doctrine'];

$config = new \Doctrine\ORM\Configuration();
/** @var list<string> $entityPath */
$entityPath = $settings['meta']['entity_path'];
$driver = new \Doctrine\ORM\Mapping\Driver\XmlDriver($entityPath);
$config->setMetadataDriverImpl($driver);

$memcached = new Memcached();
$memcached->addServer('127.0.0.1', 11211);

$cache = new MemcachedAdapter($memcached);
$config->setQueryCache($cache);

$config->setMetadataCache($cache);

/** @var string $proxyDir */
$proxyDir = $settings['meta']['proxy_dir'];
$config->setProxyDir($proxyDir);
$config->setProxyNamespace('superelf');

$connection = DriverManager::getConnection($settings['connection'], $config, new EventManager());
$em = new Doctrine\ORM\EntityManager($connection, $config);

Type::addType('enum_AgainstSide', SportsHelpers\Against\SideType::class);
Type::addType('enum_AgainstResult', SportsHelpers\Against\ResultType::class);
Type::addType('enum_GameMode', SportsHelpers\GameModeType::class);
Type::addType('enum_SelfReferee', SportsHelpers\SelfRefereeType::class);
Type::addType('enum_EditMode', Sports\Planning\EditModeType::class);
Type::addType('enum_QualifyTarget', Sports\Qualify\TargetType::class);
Type::addType('enum_AgainstRuleSet', Sports\Ranking\AgainstRuleSetType::class);
Type::addType('enum_PointsCalculation', Sports\Ranking\PointsCalculationType::class);
Type::addType('enum_PlanningState', SportsPlanning\Planning\StateType::class);
Type::addType('enum_PlanningTimeoutState', SportsPlanning\Planning\TimeoutStateType::class);
Type::addType('enum_GameState', Sports\Game\StateType::class);
Type::addType('enum_BadgeCategory', SuperElf\Achievement\BadgeCategoryType::class);
Type::addType('enum_FootballLine', Sports\Sport\FootballLineType::class);
Type::addType('enum_Distribution', Sports\Qualify\DistributionType::class);

//$em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('string', 'enum_BadgeCategory');

return new SingleManagerProvider($em);
