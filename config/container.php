<?php

declare(strict_types=1);

use App\Mailer;
use Doctrine\DBAL\Connection as DBConnection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Slim\App;
use Slim\Factory\AppFactory;
use SportsImport\CacheItemDb\Repository as CacheItemDbRepository;
use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\Repository as ExternalSourceRepository;
use SuperElf\UTCDateTimeType;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

return [
    // Application settings
    Configuration::class => function (): Configuration {
        return new Configuration(require __DIR__ . '/settings.php');
    },
    App::class => function (ContainerInterface $container): App {
        AppFactory::setContainer($container);
        $app = AppFactory::create();
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        if ($config->getString("environment") === "production") {
            $routeCacheFile = $config->getString('router.cache_file');
            if (strlen($routeCacheFile) > 0) {
                $app->getRouteCollector()->setCacheFile($routeCacheFile);
            }
        }
        return $app;
    },
    LoggerInterface::class => function (ContainerInterface $container): LoggerInterface {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);

        $loggerSettings = $config->getArray('logger');
        $name = "application";
        $logger = new Logger($name);

        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $loggerPath = $config->getString('logger.path') . $name . '.log';
        $path = $config->getString('environment') === 'development' ? 'php://stdout' : $loggerPath;
        $handler = new StreamHandler($path, $loggerSettings['level']);
        $logger->pushHandler($handler);

        return $logger;
    },
    Memcached::class => function (): Memcached {
        $memcached = new Memcached();
        $memcached->addServer('127.0.0.1', 11211);
        return $memcached;
    },
    EntityManagerInterface::class => function (ContainerInterface $container): EntityManagerInterface {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        $doctrineAppConfig = $config->getArray('doctrine');
        /** @var array<string, string|bool|null> $doctrineMetaConfig */
        $doctrineMetaConfig = $doctrineAppConfig['meta'];
        /** @var bool $devMode */
        $devMode = $doctrineMetaConfig['dev_mode'];

        $docConfig = new \Doctrine\ORM\Configuration();
        if (!$devMode) {
            /** @var Memcached $memcached */
            $memcached = $container->get(Memcached::class);
            $cache = new MemcachedAdapter($memcached, $config->getString('namespace'));
            $docConfig->setQueryCache($cache);

            $docConfig->setMetadataCache($cache);
        }
        /** @var string $proxyDir */
        $proxyDir = $doctrineMetaConfig['proxy_dir'];
        $docConfig->setProxyDir($proxyDir);
        $docConfig->setProxyNamespace($config->getString('namespace'));

        /** @var list<string> $entityPath */
        $entityPath = $doctrineMetaConfig['entity_path'];
        $driver = new \Doctrine\ORM\Mapping\Driver\XmlDriver($entityPath);
        $docConfig->setMetadataDriverImpl($driver);

        /** @var array<string, mixed> $connectionParams */
        $connectionParams = $doctrineAppConfig['connection'];
        $em = Doctrine\ORM\EntityManager::create($connectionParams, $docConfig);

        Type::addType('enum_AgainstSide', SportsHelpers\Against\SideType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('int', 'enum_AgainstSide');
        Type::addType('enum_AgainstResult', SportsHelpers\Against\ResultType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('int', 'enum_AgainstResult');
        Type::addType('enum_GameMode', SportsHelpers\GameModeType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('int', 'enum_GameMode');
        Type::addType('enum_SelfReferee', SportsHelpers\SelfRefereeType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('int', 'enum_SelfReferee');
        Type::addType('enum_EditMode', Sports\Planning\EditModeType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('int', 'enum_EditMode');
        Type::addType('enum_QualifyTarget', Sports\Qualify\TargetType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('string', 'enum_QualifyTarget');
        Type::addType('enum_AgainstRuleSet', Sports\Ranking\AgainstRuleSetType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('int', 'enum_AgainstRuleSet');
        Type::addType('enum_PointsCalculation', Sports\Ranking\PointsCalculationType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('int', 'enum_PointsCalculation');
        Type::addType('enum_PlanningState', SportsPlanning\Planning\StateType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('int', 'enum_PlanningState');
        Type::addType('enum_PlanningTimeoutState', SportsPlanning\Planning\TimeoutStateType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('string', 'enum_PlanningTimeoutState');
        Type::addType('enum_GameState', Sports\Game\StateType::class);
        $em->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('int', 'enum_GameState');

        Type::overrideType('datetime_immutable', UTCDateTimeType::class);
        return $em;
    },
    DBConnection::class => function (ContainerInterface $container): DBConnection {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        $doctrineAppConfig = $config->getArray('doctrine');
        /** @var array<string, string|int> $connectionParams */
        $connectionParams = $doctrineAppConfig['migrationconnection'];
        /** @psalm-suppress ArgumentTypeCoercion */
        return \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
    },
    SerializerInterface::class => function (ContainerInterface $container): SerializerInterface {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        $env = $config->getString("environment");
        $serializerBuilder = SerializerBuilder::create()->setDebug($env === "development");
        if ($env === "production") {
            $serializerBuilder = $serializerBuilder->setCacheDir($config->getString('serializer.cache_dir'));
        }
        $serializerBuilder->setPropertyNamingStrategy(
            new \JMS\Serializer\Naming\SerializedNameAnnotationStrategy(
                new \JMS\Serializer\Naming\IdenticalPropertyNamingStrategy()
            )
        );
        $serializerBuilder->setSerializationContextFactory(
            function (): SerializationContext {
                return SerializationContext::create()->setGroups(['Default']);
            }
        );
        $serializerBuilder->setDeserializationContextFactory(
            function (): DeserializationContext {
                return DeserializationContext::create()->setGroups(['Default']);
            }
        );
        /** @var array<string, string> $ymlDirs */
        $ymlDirs = $config->getArray('serializer.yml_dir');
        foreach ($ymlDirs as $ymlnamespace => $ymldir) {
            $serializerBuilder->addMetadataDir($ymldir, $ymlnamespace);
        }
//        $serializerBuilder->configureHandlers(
//            function (JMS\Serializer\Handler\HandlerRegistry $registry): void {
//                $registry->registerSubscribingHandler(new StructureSerializationHandler());
//                $registry->registerSubscribingHandler(new RoundNumberSerializationHandler());
//                $registry->registerSubscribingHandler(new RoundSerializationHandler());
        ////            $registry->registerSubscribingHandler(new QualifyGroupSerializationHandler());
//            }
//        );
//            $serializerBuilder->configureListeners(function(JMS\Serializer\EventDispatcher\EventDispatcher $dispatcher) {
//                /*$dispatcher->addListener('serializer.pre_serialize',
//                    function(JMS\Serializer\EventDispatcher\PreSerializeEvent $event) {
//                        // do something
//                    }
//                );*/
//                //$dispatcher->addSubscriber(new RoundNumberEventSubscriber());
//                $dispatcher->addSubscriber(new RoundNumberEventSubscriber());
//            });
        $serializerBuilder->addDefaultHandlers();

        return $serializerBuilder->build();
    },
    Mailer::class => function (ContainerInterface $container): Mailer {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);
        /** @var array<string, string|int>|null|null $smtpForDev */
        $smtpForDev = $config->getString("environment") === "development" ? $config->getArray("email.mailtrap") : null;
        return new Mailer(
            $logger,
            $config->getString('email.from'),
            $config->getString('email.fromname'),
            $config->getString('email.admin'),
            $smtpForDev
        );
    },
    ExternalSourceFactory::class => function (ContainerInterface $container): ExternalSourceFactory {
        /** @var ExternalSourceRepository $externalSourceRepos */
        $externalSourceRepos = $container->get(ExternalSourceRepository::class);
        /** @var CacheItemDbRepository $cacheItemDbRepos */
        $cacheItemDbRepos = $container->get(CacheItemDbRepository::class);
        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);
        $externalSourceFactory = new ExternalSourceFactory($externalSourceRepos, $cacheItemDbRepos, $logger);
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        /** @var array<string, string> $proxyConfig */
        $proxyConfig = $config->getArray('proxy');
        $externalSourceFactory->setProxy($proxyConfig);
        return $externalSourceFactory;
    }

];
