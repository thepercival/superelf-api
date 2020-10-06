<?php

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
use Sports\League;
use Sports\League\Repository as LeagueRepository;
use Sports\Season;
use Sports\Season\Repository as SeasonRepository;

use SportsHelpers\Range;
use Symfony\Component\Console\Command\Command as SymCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class Command extends SymCommand
{
    protected LoggerInterface $logger;
    protected Mailer $mailer;
    protected Configuration $config;
    protected SportRepository $sportRepos;
    protected AssociationRepository $associationRepos;
    protected LeagueRepository $leagueRepos;
    protected SeasonRepository $seasonRepos;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(Configuration::class);
        $this->sportRepos = $container->get(SportRepository::class);
        $this->associationRepos = $container->get(AssociationRepository::class);
        $this->leagueRepos = $container->get(LeagueRepository::class);
        $this->seasonRepos = $container->get(SeasonRepository::class);
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('logtofile', null, InputArgument::OPTIONAL, 'logtofile?');

        $this->addOption('league', null, InputOption::VALUE_OPTIONAL, 'the name of the league');
        $this->addOption('season', null, InputOption::VALUE_OPTIONAL, 'the name of the season');
        $this->addOption('batchNrRange', null, InputOption::VALUE_OPTIONAL, '1-4');
        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'game-id');
    }

    protected function initLogger(InputInterface $input, string $name)
    {
        $logToFile = $input->hasOption('logtofile') ? filter_var(
            $input->getOption('logtofile'),
            FILTER_VALIDATE_BOOLEAN
        ) : false;
        $loggerSettings = $this->config->getArray('logger');

        $this->logger = new Logger($name);
        $processor = new UidProcessor();
        $this->logger->pushProcessor($processor);

        $path = $logToFile ? ($loggerSettings['path'] . $name . '.log') : 'php://stdout';
        $handler = new StreamHandler($path, $loggerSettings['level']);
        $this->logger->pushHandler($handler);
    }

    protected function initMailer(LoggerInterface $logger)
    {
        $emailSettings = $this->config->getArray('email');
        $this->mailer = new Mailer(
            $logger,
            $emailSettings['from'],
            $emailSettings['fromname'],
            $emailSettings['admin']
        );
    }

    protected function getSportFromInput(InputInterface $input): Sport {
        if( strlen( $input->getOption("sport") ) === 0 ) {
            throw new Exception("no sport-option given", E_ERROR);
        }
        $sport = $this->sportRepos->findOneBy( ["name" => $input->getOption("sport") ] );
        if( $sport === null ) {
            throw new Exception("sport '".$input->getOption("sport")."' not found", E_ERROR);
        }
        return $sport;
    }

    protected function getAssociationFromInput(InputInterface $input): Association {
        if( strlen( $input->getOption("association") ) === 0 ) {
            throw new Exception("no association-option given", E_ERROR);
        }
        $association = $this->associationRepos->findOneBy( ["name" => $input->getOption("association") ] );
        if( $association === null ) {
            throw new Exception("association '".$input->getOption("association")."' not found", E_ERROR);
        }
        return $association;
    }

    protected function getLeagueFromInput(InputInterface $input): League {
        if( strlen( $input->getOption("league") ) === 0 ) {
            throw new Exception("no league-option given", E_ERROR);
        }
        $league = $this->leagueRepos->findOneBy( ["name" => $input->getOption("league") ] );
        if( $league === null ) {
            throw new Exception("league '".$input->getOption("league")."' not found", E_ERROR);
        }
        return $league;
    }

    protected function getSeasonFromInput(InputInterface $input): Season {
        if( strlen( $input->getOption("season") ) === 0 ) {
            throw new Exception("no season-option given", E_ERROR);
        }
        $season = $this->seasonRepos->findOneBy( ["name" => $input->getOption("season") ] );
        if( $season === null ) {
            throw new Exception("season '".$input->getOption("season")."' not found", E_ERROR);
        }
        return $season;
    }

    protected function getBatchNrRangeFromInput(InputInterface $input): Range {
        if( strlen( $input->getOption("batchNrRange") ) === 0 ) {
            return new Range(1,1);
        }
        if (strpos($input->getOption("batchNrRange"), "-") === false ) {
            throw new Exception("misformat batchNrRange-option");
        }
        $minMax = explode('-', $input->getOption('batchNrRange'));
        return new Range( (int)$minMax[0], (int)$minMax[1]);
    }

    /**
     * @param InputInterface $input
     * @return int|string
     * @throws Exception
     */
    protected function getIdFromInput(InputInterface $input) {
        if( strlen( $input->getOption("id") ) === 0 ) {
            throw new Exception("id-option not found");
        }
        return $input->getOption('id');
    }
}
