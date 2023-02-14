<?php

namespace App\Commands\Validator;

use App\Command;
use Psr\Container\ContainerInterface;
use SuperElf\Points as S11Points;
use SuperElf\Pool\Repository as PoolRepository;
use SportsImport\Getter as ImportGetter;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Formation as S11Formation;
use SuperElf\Substitute\Appearance;
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
                        $this->validatePoints($poolUser, $competitionConfig->getPoints());
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

    protected function validatePoints(PoolUser $poolUser, S11Points $s11Points): void {
        $assembleFormation = $poolUser->getAssembleFormation();
        if( $assembleFormation !== null) {
            $this->validateFormationSubstituteAppearances($assembleFormation);
            $this->validateFormationTotalPoints($assembleFormation, $s11Points);
        }
        $transferFormation = $poolUser->getTransferFormation();
        if( $transferFormation !== null) {
            $this->validateFormationSubstituteAppearances($transferFormation);
            $this->validateFormationTotalPoints($transferFormation, $s11Points);
        }
    }

    protected function validateFormationSubstituteAppearances(S11Formation $formation): void {
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

    protected function validateFormationTotalPoints(S11Formation $formation, S11Points $s11Points): void {
        $gameRounds = $formation->getViewPeriod()->getGameRounds();
        foreach( $formation->getLines() as $formationLine) {
            // starting places
            foreach( $formationLine->getStartingPlaces() as $formationPlace) {
                $totalPoints = 0;
                foreach( $gameRounds as $gameRound ) {
                    $totalPoints += $formationPlace->getPoints($gameRound, $s11Points);
                }
                if( $totalPoints !== $formationPlace->getTotalPoints() ) {
                    throw new \Exception('totalpoints starting place incorrect : ' . $totalPoints);
                }
            }
            // substitute
            $substitute = $formationLine->getSubstitute();
            $totalPoints = 0;
            foreach( $gameRounds as $gameRound ) {
                $appearance = $formationLine->getSubstituteAppareance($gameRound);
                if( $appearance === null ) {
                    continue;
                }
                $totalPoints += $substitute->getPoints($gameRound, $s11Points);
            }
            if( $totalPoints !== $substitute->getTotalPoints() ) {
                $person = $substitute->getPlayer()?->getPerson();
                $name =  $person !== null ? $person->getName() : 'unknown';
                $grNrs = join( ',', array_values( $formationLine->getSubstituteAppearances()->map(function(Appearance $appearance): string {
                    return $appearance->getGameRoundNumber() . '';
                })->toArray()) );
                throw new \Exception('totalpoints substitute(' . $formationLine->getLine()->value . ' - "'.$name.'") incorrect : ' . $substitute->getTotalPoints() . ' should be ' . $totalPoints . ', appearances: ' . $grNrs );
            }
        }
    }
}
