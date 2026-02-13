<?php

namespace App\Commands\Transfer;

use App\Command;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Sports\Person;
use Sports\Team\Player;
use SuperElf\Formation as S11Formation;
use SuperElf\Formation\Calculator;
use SuperElf\Formation\Validator as FormationValidator;
use SuperElf\OneTeamSimultaneous;
use SuperElf\S11Player as S11Player;
use SuperElf\S11Player\S11PlayerSyncer as S11PlayerSyncer;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Repositories\CompetitionConfigRepository;
use SuperElf\Repositories\PoolRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateFormationsCommand extends Command
{
    private string $customName = 'create-transfer-formations';

    private FormationValidator $formationValidator;
    private bool $dryRun = false;

    protected CompetitionConfigRepository $competitionConfigRepos;
    protected PoolRepository $poolRepos;

    /** @var EntityRepository<PoolUser>  */
    protected EntityRepository $poolUserRepos;
    /** @var EntityRepository<S11Formation>  */
    protected EntityRepository $s11FormationRepos;
    protected S11PlayerSyncer $s11PlayerSyncer;
    /** @var EntityManagerInterface $entityManager */
    protected EntityManagerInterface $entityManager;

    public function __construct(ContainerInterface $container)
    {
        /** @var EntityManagerInterface entityManager */
        $this->entityManager = $container->get(EntityManagerInterface::class);

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var PoolRepository $poolRepository */
        $poolRepository = $container->get(PoolRepository::class);
        $this->poolRepos = $poolRepository;

        $this->poolUserRepos = $this->entityManager->getRepository(PoolUser::class);
        $this->s11FormationRepos = $this->entityManager->getRepository(S11Formation::class);

        /** @var S11PlayerSyncer $s11PlayerSyncer */
        $s11PlayerSyncer = $container->get(S11PlayerSyncer::class);
        $this->s11PlayerSyncer = $s11PlayerSyncer;

        parent::__construct($container);

        $this->formationValidator = new FormationValidator($this->config);
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setName('app:' . $this->customName)
            ->setDescription('creates the transfer formations')
            ->setHelp('creates the transfer formations');

        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE);
        $this->addOption('poolUserId', null, InputOption::VALUE_OPTIONAL, 'poolUserId');

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loggerName = 'command-' . $this->customName;
        $logger = $this->initLoggerNew(
            $this->getLogLevelFromInput($input),
            $this->getStreamDefFromInput($input, $loggerName),
            $loggerName,
        );

        try {
            /** @var bool|null $dryRun */
            $dryRun = $input->getOption('dry-run');
            $this->dryRun = is_bool($dryRun) ? $dryRun : false;

            $poolUserId = $this->inputHelper->getStringFromInput($input, 'poolUserId', '');
            $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);
            $pools = $this->poolRepos->findBy(['competitionConfig' => $competitionConfig]);
            while ($pool = array_pop($pools)) {
                $logger->info( '    ' . $pool->getName() );
                foreach( $pool->getUsers() as $poolUser ) {
                    if( $poolUserId !== '' && $poolUserId != $poolUser->getId() ) {
                        continue;
                    }
                    $this->createTransferFormation($poolUser, $logger);
                }
            }
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        return 0;
    }

    private function createTransferFormation(PoolUser $poolUser, LoggerInterface $logger): void {
        try {
            $calculator = new Calculator();

            $prefix = '        ';
            $logger->info( $prefix . $poolUser->getuser()->getName() . ' ('. (string)$poolUser->getId() .')');

            $assembleFormation = $poolUser->getAssembleFormation();
            if( $assembleFormation !== null ) {
                $logger->info( $calculator->output( $prefix . '   assemble formation : ', $assembleFormation ) );
                $this->outputFormation(
                    $assembleFormation,
                    $assembleFormation->getViewPeriod()->getStartDateTime(),
                    $prefix . '        ',
                    $logger);
            }

            $transferFormation = $this->formationValidator->validateTransferActions( $poolUser );
            $poolUser->setTransferFormation($transferFormation);

            $transferViewPeriod = $transferFormation->getViewPeriod();
            foreach( $transferFormation->getLines() as $formationLine ) {
                foreach ($formationLine->getPlaces() as $formationPlace) {
                    $oldS11Player = $formationPlace->getPlayer();
                    if ($oldS11Player !== null) {
                        $newS11Player = $this->s11PlayerSyncer->syncS11Player($transferViewPeriod, $oldS11Player->getPerson());
                        $formationPlace->setPlayer($newS11Player);
                    }
                }
            }
            $this->outputActions($poolUser, $prefix . '   ', $logger);
            $logger->info( $calculator->output( $prefix . '   new formation : ', $transferFormation ) );
            $this->outputFormation(
                $transferFormation,
                $transferFormation->getViewPeriod()->getStartDateTime(),
                $prefix . '        ',
                $logger);

            if( !$this->dryRun) {
                //            $this->poolUserRepos->flush();
                //            if( $oldTransferFormation !== null ) {
                //                $this->s11FormationRepos->remove($oldTransferFormation, true);
                //            }
                $this->entityManager->persist($poolUser);
                $this->entityManager->flush();
            }

        } catch (\Exception $e) {
            $logger->error( '            ' . $e->getMessage());
        }
    }

    private function outputFormation(
        S11Formation $formation,
        \DateTimeImmutable $dateTime,
        string $prefix,
        LoggerInterface $logger): void {
        foreach( $formation->getLines() as $formationLine ) {
            $outputPlaces = [];
            foreach ($formationLine->getPlaces() as $formationPlace) {
                $outputPlace = $formationPlace->getNumber() . ' ';
                $s11Player = $formationPlace->getPlayer();
                $outputPlayer = '';
                if ( $s11Player !== null ) {
                    $outputPlayer = $this->outputS11Player($s11Player, $dateTime);
                }
                $outputPlaces [] = $outputPlace . $this->inputHelper->toLength( $outputPlayer, 15 );
            }

            $logger->info( $prefix . $formationLine->getLine()->value . ' => ' . join(' | ', $outputPlaces) );
        }
    }

    private function outputActions(
        PoolUser $poolUser,
        string $prefix,
        LoggerInterface $logger): void {
        $this->outputReplacements($poolUser, $prefix, $logger);
        $this->outputTransfers($poolUser, $prefix, $logger);
        $this->outputSubstitutions($poolUser, $prefix, $logger);
    }

    private function outputReplacements(PoolUser $poolUser,string $prefix,LoggerInterface $logger): void {
        $logger->info( $prefix . 'replacements : ' );
        foreach( $poolUser->getReplacements() as $replacement ) {
            $outputPlayerOut = $this->inputHelper->toLength( $this->outputTeamPlayer($replacement->getPlayerOut()), 15) ;
            $outputPlayerIn = $this->inputHelper->toLength( $this->outputTeamPlayer($replacement->getPlayerIn()), 15) ;
            $logger->info( $prefix . '    out :    ' . $outputPlayerOut . ' => <= in : ' . $outputPlayerIn );
        }
    }

    private function outputTransfers(PoolUser $poolUser,string $prefix,LoggerInterface $logger): void {
        $logger->info( $prefix . 'transfers : ' );
        foreach( $poolUser->getTransfers() as $transfer ) {
            $outputPlayerOut = $this->inputHelper->toLength( $this->outputTeamPlayer($transfer->getPlayerOut()), 15) ;
            $outputPlayerIn = $this->inputHelper->toLength( $this->outputTeamPlayer($transfer->getPlayerIn()), 15) ;
            $logger->info( $prefix . '    out : ' . $transfer->getLineNumberOut()->value . ' - ' . $transfer->getPlaceNumberOut() . ' ' . $outputPlayerOut . ' => <= in : ' . $outputPlayerIn );
        }
    }

    private function outputSubstitutions(PoolUser $poolUser, string $prefix,LoggerInterface $logger): void {
        $logger->info( $prefix . 'substitutions : ' );
        foreach( $poolUser->getSubstitutions() as $substitution ) {
//            $outputPlayerOut = $this->toLength( $this->outputTeamPlayer($transfer->getPlayerOut()), 15) ;
//            $outputPlayerIn = $this->toLength( $this->outputTeamPlayer($transfer->getPlayerIn()), 15) ;
//            $logger->info( $prefix . '    out :    ' . $outputPlayerOut . ' => <= in : ' . $outputPlayerIn );$outputPlayerOut = $this->toLength( $this->outputTeamPlayer($transfer->getPlayerOut()), 15) ;
            $logger->info( $prefix . '    out :    ' . $substitution->getLineNumberOut()->value . ' - ' . $substitution->getPlaceNumberOut() );
        }
    }

    private function outputS11Player(S11Player|null $s11Player, \DateTimeImmutable $dateTime): string {
        if( $s11Player === null) {
            return '';
        }
        return $this->outputPerson($s11Player->getPerson(), $dateTime);
    }

    private function outputPerson(Person $person, \DateTimeImmutable $dateTime): string {
        $teamPlayer = (new OneTeamSimultaneous())->getPlayer($person, $dateTime);
        if( $teamPlayer === null) {
            return '';
        }
        $personName = substr($person->getFirstName(), 0, 1);
        $nameInsertion = (string)$person->getNameInsertion();
        $nameInsertion = strlen($nameInsertion) > 0 ? ' ' . $nameInsertion : '';
        $personName .= $nameInsertion  . ' ' . $person->getLastName();
        return ((string)$teamPlayer->getTeam()->getAbbreviation()) . ' - ' . $personName;
    }

    private function outputTeamPlayer(Player $teamPlayer): string {
        $person = $teamPlayer->getPerson();
        $personName = substr($person->getFirstName(), 0, 1);
        $nameInsertion = (string)$person->getNameInsertion();
        $nameInsertion = strlen($nameInsertion) > 0 ? ' ' . $nameInsertion : '';
        $personName .= $nameInsertion  . ' ' . $person->getLastName();
        return ((string)$teamPlayer->getTeam()->getAbbreviation()) . ' - ' . $personName;
    }
}