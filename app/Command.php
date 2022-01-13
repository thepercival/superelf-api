<?php

declare(strict_types=1);

namespace App;

use App\Commands\CompetitionConfig;
use DateTimeImmutable;
use Exception;
use League\Period\Period;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Association;
use Sports\Association\Repository as AssociationRepository;
use Sports\Competition;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\League;
use Sports\League\Repository as LeagueRepository;
use Sports\Season;
use Sports\Season\Repository as SeasonRepository;
use Sports\Sport;
use Sports\Sport\Repository as SportRepository;
use SportsHelpers\SportRange;
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

    protected function getSportFromInput(InputInterface $input): Sport
    {
        $optionName = 'sport';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "'.$optionName.'"-option given', E_ERROR);
        }
        $sport = $this->sportRepos->findOneBy(["name" => $optionValue ]);
        if ($sport === null) {
            throw new Exception("sport '".$optionValue."' not found", E_ERROR);
        }
        return $sport;
    }

    protected function getAssociationFromInput(InputInterface $input): Association
    {
        $optionName = 'association';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "'.$optionName.'"-option given', E_ERROR);
        }
        $association = $this->associationRepos->findOneBy(["name" => $optionValue ]);
        if ($association === null) {
            throw new Exception("association '".$optionValue."' not found", E_ERROR);
        }
        return $association;
    }

    protected function getLeagueFromInput(InputInterface $input): League
    {
        $optionName = 'league';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "'.$optionName.'"-option given', E_ERROR);
        }
        $league = $this->leagueRepos->findOneBy(["name" => $optionValue ]);
        if ($league === null) {
            throw new Exception("league '".$optionValue."' not found", E_ERROR);
        }
        return $league;
    }

    protected function getSeasonFromInput(InputInterface $input): Season
    {
        $optionName = 'season';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "'.$optionName.'"-option given', E_ERROR);
        }
        $season = $this->seasonRepos->findOneBy(["name" => $optionValue ]);
        if ($season === null) {
            throw new Exception("season '".$optionValue."' not found", E_ERROR);
        }
        return $season;
    }

    protected function getCompetitionFromInput(InputInterface $input): Competition|null
    {
        try {
            $league = $this->getLeagueFromInput($input);
            $season = $this->getSeasonFromInput($input);
            return $this->competitionRepos->findOneBy(
                ['league' => $league, 'season' => $season ]
            );
        } catch (Exception $e) {
        }
        return null;
    }

    protected function getGameRoundNrRangeFromInput(InputInterface $input): SportRange|null
    {
        $rangeOption = (string)$input->getOption("gameRoundRange");
        if (strlen($rangeOption) === 0) {
            return null;
        }
        if (!str_contains($rangeOption, '-')) {
            throw new Exception('misformat gameRoundRange-option');
        }
        $minMax = explode('-', $rangeOption);
        return new SportRange((int)$minMax[0], (int)$minMax[1]);
    }

    /**
     * @param InputInterface $input
     * @return int|string
     * @throws Exception
     */
    protected function getIdFromInput(InputInterface $input, int|string $fallBackValue = null): int|string
    {
        $idOption = $input->getOption("id");
        if (is_int($idOption)) {
            return $idOption;
        }
        if (!is_string($idOption) || strlen($idOption) === 0) {
            if ($fallBackValue === null) {
                throw new Exception("id-option not found");
            }
            return $fallBackValue;
        }
        return $idOption;
    }

    protected function getDateTimeFromInput(InputInterface $input, string $optionName): DateTimeImmutable
    {
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "' . $optionName . '"-option given', E_ERROR);
        }
        $dateTime = DateTimeImmutable::createFromFormat(CompetitionConfig::DateTimeFormat, $optionValue);
        if ($dateTime === false) {
            throw new Exception('invalid datetime "' . $optionName . '" given', E_ERROR);
        }
        return $dateTime;
    }

    protected function getPeriodFromInput(InputInterface $input, string $optionName): Period
    {
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "' . $optionName . '"-option given', E_ERROR);
        }
        if (!str_contains($optionValue, '=>')) {
            throw new Exception('invalid "' . $optionName . '"-option given', E_ERROR);
        }
        $dateTimes = explode('=>', $optionValue);
        if (count($dateTimes) !== 2) {
            throw new Exception('invalid "' . $optionName . '"-option given', E_ERROR);
        }

        $start = DateTimeImmutable::createFromFormat(CompetitionConfig::DateTimeFormat, $dateTimes[0]);
        if ($start === false) {
            throw new Exception('invalid "' . $optionName . '" given', E_ERROR);
        }
        $end = DateTimeImmutable::createFromFormat(CompetitionConfig::DateTimeFormat, $dateTimes[1]);
        if ($end === false) {
            throw new Exception('invalid "' . $optionName . '" given', E_ERROR);
        }
        if ($start->getTimestamp() > $end->getTimestamp()) {
            throw new Exception('invalid "' . $optionName . '" given', E_ERROR);
        }
        return new Period($start, $end);
    }
}
