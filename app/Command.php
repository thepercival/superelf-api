<?php

declare(strict_types=1);

namespace App;

use Exception;
use Psr\Container\ContainerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Sport;
use Sports\Sport\Repository as SportRepository;
use Sports\Association;
use Sports\Association\Repository as AssociationRepository;
use SportsImport\Attacher\Association\Repository as AssociationAttacherRepository;
use Sports\League;
use Sports\League\Repository as LeagueRepository;
use Sports\Season;
use Sports\Season\Repository as SeasonRepository;

use SportsHelpers\SportRange;
use Symfony\Component\Console\Command\Command as SymCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use App\Mailer;

class Command extends SymCommand
{
    protected LoggerInterface $logger;
    protected Mailer $mailer;

    protected Configuration $config;
    protected SportRepository $sportRepos;
    protected AssociationRepository $associationRepos;
    protected LeagueRepository $leagueRepos;
    protected SeasonRepository $seasonRepos;
    protected AssociationAttacherRepository $associationAttacherRepos;

    public function __construct(ContainerInterface $container, string $loggerName)
    {
        /** @var Configuration config */
        $this->config = $container->get(Configuration::class);
        /** @var SportRepository sportRepos */
        $this->sportRepos = $container->get(SportRepository::class);
        /** @var AssociationRepository associationRepos */
        $this->associationRepos = $container->get(AssociationRepository::class);
        /** @var LeagueRepository leagueRepos */
        $this->leagueRepos = $container->get(LeagueRepository::class);
        /** @var SeasonRepository seasonRepos */
        $this->seasonRepos = $container->get(SeasonRepository::class);
        /** @var AssociationAttacherRepository associationAttacherRepos */
        $this->associationAttacherRepos = $container->get(AssociationAttacherRepository::class);
        /** @var Mailer mailer */
        $this->mailer = $container->get(Mailer::class);
        parent::__construct();
        $this->initLogger($loggerName);
    }

    protected function configure(): void
    {
        $this->addOption('logtofile', null, InputOption::VALUE_NONE, 'logtofile?');

        $this->addOption('sport', null, InputOption::VALUE_OPTIONAL, 'the name of the sport');
        $this->addOption('association', null, InputOption::VALUE_OPTIONAL, 'the name of the association');
        $this->addOption('league', null, InputOption::VALUE_OPTIONAL, 'the name of the league');
        $this->addOption('season', null, InputOption::VALUE_OPTIONAL, 'the name of the season');
        $this->addOption('batchNrRange', null, InputOption::VALUE_OPTIONAL, '1-4');
        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'game-id');
    }

    private function initLogger(string $name): void
    {
        $this->logger = new Logger($name);
        $processor = new UidProcessor();
        $this->logger->pushProcessor($processor);

        $loggerSettings = $this->config->getArray('logger');
        $path = (string)$loggerSettings['path'] . $name . '.log';
        $handler = new StreamHandler($path, $loggerSettings['level']);
        $this->logger->pushHandler($handler);
    }

    protected function initLoggerFromInput(InputInterface $input): void
    {
        $logToFile = $input->hasOption('logtofile') && filter_var(
            $input->getOption('logtofile'),
            FILTER_VALIDATE_BOOLEAN
        );
        if ($logToFile) {
            return;
        }
        $loggerSettings = $this->config->getArray('logger');
        $this->logger = new Logger('php://stdout');
        $processor = new UidProcessor();
        $this->logger->pushProcessor($processor);
        $handler = new StreamHandler('php://stdout', $loggerSettings['level']);
        $this->logger->pushHandler($handler);
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

    protected function getBatchNrRangeFromInput(InputInterface $input): SportRange
    {
        $batchNrRangeOption = (string)$input->getOption("batchNrRange");
        if (strlen($batchNrRangeOption) === 0) {
            return new SportRange(1, 1);
        }
        if (!str_contains($batchNrRangeOption, '-')) {
            throw new Exception('misformat batchNrRange-option');
        }
        $minMax = explode('-', $batchNrRangeOption);
        return new SportRange((int)$minMax[0], (int)$minMax[1]);
    }

    /**
     * @param InputInterface $input
     * @return int|string
     * @throws Exception
     */
    protected function getIdFromInput(InputInterface $input)
    {
        $idOption = $input->getOption("id");
        if (!is_string($idOption) || strlen($idOption) === 0) {
            throw new Exception("id-option not found");
        }
        return $idOption;
    }
}
