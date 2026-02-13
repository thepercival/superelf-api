<?php

declare(strict_types=1);

namespace App;

use App\Commands\InputHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Association;
use Sports\League;
use Sports\Repositories\CompetitionRepository;
use Sports\Repositories\SeasonRepository;
use Sports\Repositories\SportRepository;
use SportsImport\Attachers\AssociationAttacher;
use SportsImport\Repositories\AttacherRepository;
use Symfony\Component\Console\Command\Command as SymCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class Command extends SymCommand
{
    protected LoggerInterface|null $logger = null;
    protected Mailer|null $mailer = null;

    protected Configuration $config;
    protected SportRepository $sportRepos;
    /** @var EntityRepository<Association>  */
    protected EntityRepository $associationRepos;
    /** @var EntityRepository<League>  */
    protected EntityRepository $leagueRepos;
    protected CompetitionRepository $competitionRepos;
    protected SeasonRepository $seasonRepos;
    /** @var AttacherRepository<AssociationAttacher>  */
    protected AttacherRepository $associationAttacherRepos;
    protected InputHelper $inputHelper;

    public function __construct(ContainerInterface $container)
    {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        $this->config = $config;

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get(EntityManagerInterface::class);

        /** @var SportRepository $sportRepos */
        $sportRepos = $container->get(SportRepository::class);
        $this->sportRepos = $sportRepos;

        $this->associationRepos = $entityManager->getRepository(Association::class);
        $this->leagueRepos = $entityManager->getRepository(League::class);

        /** @var SeasonRepository $seasonRepos */
        $seasonRepos = $container->get(SeasonRepository::class);
        $this->seasonRepos = $seasonRepos;

        /** @var CompetitionRepository $competitionRepos */
        $competitionRepos = $container->get(CompetitionRepository::class);
        $this->competitionRepos = $competitionRepos;

        $metadata = $entityManager->getClassMetadata(AssociationAttacher::class);
        $this->associationAttacherRepos = new AttacherRepository($entityManager, $metadata);

        /** @var InputHelper $inputHelper */
        $inputHelper = $container->get(InputHelper::class);
        $this->inputHelper = $inputHelper;

        /** @var Mailer $mailer */
        $mailer = $container->get(Mailer::class);
        $this->mailer = $mailer;
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addOption('logtofile', null, InputOption::VALUE_NONE, 'logtofile?');
        $this->addOption('loglevel', null, InputOption::VALUE_OPTIONAL, Level::Info->name);
    }

    protected function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            throw new Exception('define logger first', E_ERROR);
        }
        return $this->logger;
    }

    protected function initLogger(InputInterface $input, string $name, MailHandler|null $mailHandler = null): Logger
    {
        $logLevel = $this->getLogLevelFromInput($input, Level::Info);

        $this->logger = new Logger($name);
        $processor = new UidProcessor();
        $this->logger->pushProcessor($processor);

        /** @var bool|null $logToFile */
        $logToFile = $input->getOption('logtofile');
        $logToFile = is_bool($logToFile) ? $logToFile : false;
        $path = $logToFile ? ($this->config->getString('logger.path') . $name . '.log') : 'php://stdout';
        $handler = new StreamHandler($path, $logLevel);
        $this->logger->pushHandler($handler);

        if ($mailHandler === null) {
            $mailLogLevel = Level::Error;
            if ($this->config->getString('environment') === 'development') {
                $mailLogLevel = Level::Critical;
            }
            $mailHandler = $this->getMailHandler(null, $mailLogLevel);
        }
        if ($this->mailer !== null) {
            $mailHandler->setMailer($this->mailer);
        }
        $this->logger->pushHandler($mailHandler);
        return $this->logger;
    }

    protected function initLoggerNew(
        Level $logLevel,
        string $streamDef,
        string $name,
        MailHandler|null $mailHandler = null
    ): Logger {
        $this->logger = new Logger($name);
        $processor = new UidProcessor();
        $this->logger->pushProcessor($processor);

        $handler = new StreamHandler($streamDef, $logLevel);
        $this->logger->pushHandler($handler);

        if ($mailHandler === null) {
            $mailHandler = $this->getMailHandler();
        }
        if ($this->mailer !== null) {
            $mailHandler->setMailer($this->mailer);
        }
        $this->logger->pushHandler($mailHandler);
        return $this->logger;
    }

    protected function getMailHandler(
        string|null $subject = null,
        Level|null $mailLogLevel = null
    ): MailHandler {
        if ($subject === null) {
            $subject = ((string)$this->getName()) . ' : error';
        }
        if ($mailLogLevel === null) {
            $mailLogLevel = Level::Error;
        }
        $toEmailAddress = $this->config->getString('email.admin');
        $fromEmailAddress = $this->config->getString('email.from');
        return new MailHandler($toEmailAddress, $subject, $fromEmailAddress, $mailLogLevel);
    }

    protected function getLogLevelFromInput(InputInterface $input, Level|null $defaultLogLevel = null): Level
    {
        /** @var string|null|false $logLevelNameParam */
        $logLevelNameParam = $input->getOption('loglevel');
        if (is_string($logLevelNameParam) && strlen($logLevelNameParam) > 0) {
            try {
                /** @psalm-suppress ArgumentTypeCoercion */
                return Level::fromName($logLevelNameParam);
            } catch (\UnhandledMatchError $e) {
                $this->getLogger()->warning("invalid parameter loglevel: {$logLevelNameParam} : {$e->getMessage()}");
            }
        }
        if ($defaultLogLevel !== null) {
            return $defaultLogLevel;
        }
        $loggerSettings = $this->config->getArray('logger');
        /** @var Level $loggerSettingLevel */
        $loggerSettingLevel = $loggerSettings['level'];

        return $loggerSettingLevel;
    }

    protected function getStreamDefFromInput(InputInterface $input, string|null $fileName = null): string
    {
        /** @var bool|null $logToFile */
        $logToFile = $input->getOption('logtofile');
        $logToFile = is_bool($logToFile) ? $logToFile : false;
        if ($logToFile === false) {
            return 'php://stdout';
        }
        /** @var array<string,string> $loggerSettings */
        $loggerSettings = $this->config->getArray('logger');
        return ($loggerSettings['path'] . (string)$fileName . '.log');
    }
}
