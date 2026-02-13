<?php

namespace App\Commands\Validator;

use App\Command;
use Psr\Container\ContainerInterface;
use Sports\Repositories\AgainstGameRepository;
use SportsImport\Getter as ImportGetter;
use SuperElf\Repositories\CompetitionConfigRepository as CompetitionConfigRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class CompetitionConfig extends Command
{
    protected ImportGetter $getter;
    protected CompetitionConfigRepository $competitionConfigRepos;
    protected AgainstGameRepository $againstGameRepos;

    //protected TeamPlayerRepository $teamPlayerRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var ImportGetter $getter */
        $getter = $container->get(ImportGetter::class);
        $this->getter = $getter;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

//        /** @var TeamPlayerRepository $teamPlayerRepos */
//        $teamPlayerRepos = $container->get(TeamPlayerRepository::class);
//        $this->teamPlayerRepos = $teamPlayerRepos;

        parent::__construct($container);
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:validate-competitionconfig')
            // the short description shown while running "php bin/console list"
            ->setDescription('validates the competitionconfig')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('validates the competitionconfig');

        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-validate-competitionconfig');

        $this->getLogger()->info('validates the competitionconfig');


        // $teamPlayerOutput = new TeamPlayerOutput($this->getLogger());

        try {
            $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);

            $competition = $competitionConfig->getSourceCompetition();

            $seasonPeriod = $competition->getSeason()->getPeriod();

            $againstGames = $this->againstGameRepos->getCompetitionGames(
                $competition,
                null,
                null,
                $competitionConfig->getAssemblePeriod()->getPeriod()
            );
            if (count($againstGames) > 0) {
                throw new \Exception('the assembleperiod has games within the period', E_ERROR);
            }

            $againstGames = $this->againstGameRepos->getCompetitionGames(
                $competition,
                null,
                null,
                $competitionConfig->getTransferPeriod()->getPeriod()
            );
            if (count($againstGames) > 0) {
                throw new \Exception('the transferperiod has games within the period', E_ERROR);
            }


            foreach ($competitionConfig->getViewPeriods() as $periodA) {
                foreach ($competitionConfig->getViewPeriods() as $periodB) {
                    if ($periodA !== $periodB && $periodA->getPeriod()->overlaps($periodB->getPeriod())) {
                        throw new \Exception('the viewperiods overlap', E_ERROR);
                    }
                }
                if (!$seasonPeriod->contains($periodA->getPeriod())) {
                    throw new \Exception('the viewperiods needs to be within the seasons', E_ERROR);
                }
            }
            if ($competitionConfig->getCreateAndJoinPeriod()->getStartDateTime() >
                $competitionConfig->getAssemblePeriod()->getStartDateTime()) {
                throw new \Exception('the createandjoinstart should be before assemblestart', E_ERROR);
            }
            if ($competitionConfig->getAssemblePeriod()->getEndDateTime() >
                $competitionConfig->getTransferPeriod()->getStartDateTime()) {
                throw new \Exception('the assembleend should be before transferstart', E_ERROR);
            }
            $this->getLogger()->info('validation succeeded');
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }
}
