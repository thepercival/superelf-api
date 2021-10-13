<?php
declare(strict_types=1);

use Doctrine\Common\Cache\Cache;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;

use App\Mailer;
use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\Repository as ExternalSourceRepository;
use SportsImport\CacheItemDb\Repository as CacheItemDbRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use Selective\Config\Configuration;
use Slim\App;
use Slim\Factory\AppFactory;

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
            if ($routeCacheFile) {
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
    EntityManager::class => function (ContainerInterface $container): EntityManager {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        $doctrineAppConfig = $config->getArray('doctrine');
        /** @var array<string, string|bool|null> $doctrineMetaConfig */
        $doctrineMetaConfig = $doctrineAppConfig['meta'];
        /** @var bool $devMode */
        $devMode = $doctrineMetaConfig['dev_mode'];
        /** @var string|null $proxyDir */
        $proxyDir = $doctrineMetaConfig['proxy_dir'];
        /** @var Cache|null $cache */
        $cache = $doctrineMetaConfig['cache'];
        $doctrineConfig = Doctrine\ORM\Tools\Setup::createConfiguration($devMode, $proxyDir, $cache);
        $driver = new \Doctrine\ORM\Mapping\Driver\XmlDriver($doctrineMetaConfig['entity_path']);
        $doctrineConfig->setMetadataDriverImpl($driver);
        /** @var array<string, mixed> $connectionParams */
        $connectionParams = $doctrineAppConfig['connection'];
        $em = Doctrine\ORM\EntityManager::create($connectionParams, $doctrineConfig);
        // $em->getConnection()->setAutoCommit(false);
        return $em;
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
