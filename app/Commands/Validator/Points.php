<?php

namespace App\Commands\Validator;

use App\Command;
use Psr\Container\ContainerInterface;
use SuperElf\Pool\Repository as PoolRepository;
use SportsImport\Getter as ImportGetter;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Formation as S11Formation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Points extends Command
{
    private string $customName = 'validate-points';
    protected ImportGetter $getter;
    protected CompetitionConfigRepository $competitionConfigRepos;
    protected PoolRepository $poolRepos;

    //protected TeamPlayerRepository $teamPlayerRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var ImportGetter $getter */
        $getter = $container->get(ImportGetter::class);
        $this->getter = $getter;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var PoolRepository $poolRepos */
        $poolRepos = $container->get(PoolRepository::class);
        $this->poolRepos = $poolRepos;

//        /** @var TeamPlayerRepository $teamPlayerRepos */
//        $teamPlayerRepos = $container->get(TeamPlayerRepository::class);
//        $this->teamPlayerRepos = $teamPlayerRepos;

        parent::__construct($container);
    }

    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:validate-points')
            // the short description shown while running "php bin/console list"
            ->setDescription('validates the competitionconfig')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('validates the competitionconfig');

        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loggerName = 'command-' . $this->customName;
        $logger = $this->initLoggerNew(
            $this->getLogLevel($input),
            $this->getStreamDef($input, $loggerName),
            $loggerName,
        );

        try {
            $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);
            $pools = $this->poolRepos->findBy(['competitionConfig' => $competitionConfig]);
            foreach( $pools as $pool) {
                $logger->info($pool->getName() . ' ..');
                $prefix = '    ';
                foreach( $pool->getUsers() as $poolUser) {
                    try {
                        $logger->info($prefix . $poolUser->getUser()->getName() . ' ..');
                        $this->validatePoints($poolUser);
                        $logger->info($prefix . $poolUser->getUser()->getName() . ' success');
                    }
                    catch(\Exception $e) {
                        $logger->info($prefix . $poolUser->getUser()->getName() . ' error => ' . $e->getMessage() );
                        // $logger->error($e->getMessage());
                    }
                }
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    protected function validatePoints(PoolUser $poolUser): void {
        $assembleFormation = $poolUser->getAssembleFormation();
        if( $assembleFormation !== null) {
            $this->validateFormationPoints($assembleFormation);
        }
        $transferFormation = $poolUser->getTransferFormation();
        if( $transferFormation !== null) {
            $this->validateFormationPoints($transferFormation);
        }
    }

    protected function validateFormationPoints(S11Formation $formation): void {
        $gameRounds = $formation->getViewPeriod()->getGameRounds();
        foreach( $formation->getLines() as $formationLine) {
            foreach( $gameRounds as $gameRound ) {
                $substituteAppearance = $formationLine->getSubstituteAppareance($gameRound);
                $descr = $formationLine->getLine()->value . ' - ' . $gameRound->getNumber();
                $onlyAppearences = true;
                foreach( $formationLine->getStartingPlaces() as $formationPlace) {
                    $statistics = $formationPlace->getGameRoundStatistics($gameRound);
                    if( $statistics === null ) {
                        continue;
                    }
                    if( $substituteAppearance === null && !$statistics->hasAppeared() ) {
                        throw new \Exception('no substitute while startingplace with no appearance : ' . $descr);
                    }
                    if( $onlyAppearences && !$statistics->hasAppeared() ) {
                        $onlyAppearences = false;
                    }
                }
                if( $substituteAppearance !== null && $onlyAppearences ) {
                    throw new \Exception('has substitute while startingplaces all appeared : ' . $descr);
                }
            }
        }
    }
}
