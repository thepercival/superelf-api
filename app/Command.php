<?php

declare(strict_types=1);

namespace App;

use App\Commands\InputHelper;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Association\Repository as AssociationRepository;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\League\Repository as LeagueRepository;
use Sports\Season\Repository as SeasonRepository;
use Sports\Sport\Repository as SportRepository;
use SportsImport\Attacher\Association\Repository as AssociationAttacherRepository;
use Symfony\Component\Console\Command\Command as SymCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class Command extends SymCommand
{
    protected LoggerInterface|null $logger = null;
    protected Mailer $mailer;

    protected Configuration $config;
    protected SportRepository $sportRepos;
    protected AssociationRepository $associationRepos;
    protected LeagueRepository $leagueRepos;
    protected CompetitionRepository $competitionRepos;
    protected SeasonRepository $seasonRepos;
    protected AssociationAttacherRepository $associationAttacherRepos;
    protected InputHelper $inputHelper;

    public function __construct(ContainerInterface $container)
    {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        $this->config = $config;

        /** @var SportRepository $sportRepos */
        $sportRepos = $container->get(SportRepository::class);
        $this->sportRepos = $sportRepos;

        /** @var AssociationRepository $associationRepos */
        $associationRepos = $container->get(AssociationRepository::class);
        $this->associationRepos = $associationRepos;

        /** @var LeagueRepository $leagueRepos */
        $leagueRepos = $container->get(LeagueRepository::class);
        $this->leagueRepos = $leagueRepos;

        /** @var SeasonRepository $seasonRepos */
        $seasonRepos = $container->get(SeasonRepository::class);
        $this->seasonRepos = $seasonRepos;

        /** @var CompetitionRepository $competitionRepos */
        $competitionRepos = $container->get(CompetitionRepository::class);
        $this->competitionRepos = $competitionRepos;

        /** @var AssociationAttacherRepository $associationAttacherRepos */
        $associationAttacherRepos = $container->get(AssociationAttacherRepository::class);
        $this->associationAttacherRepos = $associationAttacherRepos;

        /** @var InputHelper $inputHelper */
        $inputHelper = $container->get(InputHelper::class);
        $this->inputHelper = $inputHelper;

        /** @var Mailer $mailer */
        $mailer = $container->get(Mailer::class);
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('logtofile', null, InputOption::VALUE_NONE, 'logtofile?');
        $this->addOption('loglevel', null, InputOption::VALUE_OPTIONAL, '' . Logger::INFO);
    }

    protected function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            throw new Exception('define logger first', E_ERROR);
        }
        return $this->logger;
    }

    protected function initLogger(InputInterface $input, string $name, MailHandler|null $mailHandler = null): void
    {
        $logLevel = $this->config->getInt('logger.level');
        /** @var string|null $logLevelParam */
        $logLevelParam = $input->getOption('loglevel');
        if (is_string($logLevelParam) && strlen($logLevelParam) > 0) {
            $logLevelTmp = filter_var($logLevelParam, FILTER_VALIDATE_INT);
            if ($logLevelTmp !== false) {
                $logLevel = $logLevelTmp;
            }
        }

        $this->logger = new Logger($name);
        $processor = new UidProcessor();
        $this->logger->pushProcessor($processor);

        /** @var bool|null $logToFile */
        $logToFile = $input->getOption('logtofile');
        $logToFile = is_bool($logToFile) ? $logToFile : false;
        $path = $logToFile ? ($this->config->getInt('logger.path') . $name . '.log') : 'php://stdout';
        $handler = new StreamHandler($path, $logLevel);
        $this->logger->pushHandler($handler);

        if ($mailHandler === null) {
            $mailHandler = $this->getMailHandler();
        }
        $mailHandler->setMailer($this->mailer);
        $this->logger->pushHandler($mailHandler);
    }

    protected function getMailHandler(
        string|null $subject = null,
        int|null $mailLogLevel = null
    ): MailHandler {
        if ($subject === null) {
            $subject = ((string)$this->getName()) . ' : error';
        }
        if ($mailLogLevel === null) {
            $mailLogLevel = Logger::ERROR;
        }
        $toEmailAddress = $this->config->getString('email.admin');
        $fromEmailAddress = $this->config->getString('email.from');
        return new MailHandler($toEmailAddress, $subject, $fromEmailAddress, $mailLogLevel);
    }
}
