<?php

namespace App;

use Psr\Container\ContainerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\League;
use Sports\League\Repository as LeagueRepository;
use Sports\Season;
use Sports\Season\Repository as SeasonRepository;
use Symfony\Component\Console\Command\Command as SymCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class Command extends SymCommand
{
    protected LoggerInterface $logger;
    protected Mailer $mailer;
    protected Configuration $config;
    protected LeagueRepository $leagueRepos;
    protected SeasonRepository $seasonRepos;

    public function __construct(ContainerInterface $container)
    {
        $this->config = $container->get(Configuration::class);
        $this->leagueRepos = $container->get(LeagueRepository::class);
        $this->seasonRepos = $container->get(SeasonRepository::class);
        parent::__construct();
    }

    protected function configure()
    {
        $this->addOption('logtofile', null, InputArgument::OPTIONAL, 'logtofile?');

        $this->addOption('league', null, InputOption::VALUE_OPTIONAL, 'the name of the league');
        $this->addOption('season', null, InputOption::VALUE_OPTIONAL, 'the name of the season');
        $this->addOption('batchNr', null, InputOption::VALUE_OPTIONAL, 'the batchnr of the games');
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

    protected function getLeagueFromInput(InputInterface $input): League {
        if( strlen( $input->getOption("league") ) === 0 ) {
            throw new \Exception("no league-option given", E_ERROR);
        }
        $league = $this->leagueRepos->findOneBy( ["name" => $input->getOption("league") ] );
        if( $league === null ) {
            throw new \Exception("league '".$input->getOption("league")."' not found", E_ERROR);
        }
        return $league;
    }

    protected function getSeasonFromInput(InputInterface $input): Season {
        if( strlen( $input->getOption("season") ) === 0 ) {
            throw new \Exception("no season-option given", E_ERROR);
        }
        $season = $this->seasonRepos->findOneBy( ["name" => $input->getOption("season") ] );
        if( $season === null ) {
            throw new \Exception("season '".$input->getOption("season")."' not found", E_ERROR);
        }
        return $season;
    }

    protected function getStartBatchNrFromInput(InputInterface $input): int {
        if( strlen( $input->getOption("batchNr") ) === 0 ) {
            return 1;
        }
        return (int) $input->getOption("batchNr");
    }

}
