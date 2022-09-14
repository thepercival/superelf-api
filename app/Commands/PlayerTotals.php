<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use Psr\Container\ContainerInterface;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Formation\Place\Repository as FormationPlaceRepository;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Points\Creator as PointsCreator;
use SuperElf\Totals\Calculator as TotalsCalculator;
use SuperElf\Totals\Repository as TotalsRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PlayerTotals extends Command
{
    protected ViewPeriodRepository $viewPeriodRepos;
    protected S11PlayerRepository $s11PlayerRepos;
    protected FormationPlaceRepository $formationPlaceRepos;
    protected TotalsRepository $totalsRepos;
    protected PointsCreator $pointsCreator;
    protected CompetitionConfigRepository $competitionConfigRepos;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        /** @var ViewPeriodRepository $viewPeriodRepos */
        $viewPeriodRepos = $container->get(ViewPeriodRepository::class);
        $this->viewPeriodRepos = $viewPeriodRepos;

        /** @var S11PlayerRepository $s11PlayerRepos */
        $s11PlayerRepos = $container->get(S11PlayerRepository::class);
        $this->s11PlayerRepos = $s11PlayerRepos;

        /** @var FormationPlaceRepository $formationPlaceRepos */
        $formationPlaceRepos = $container->get(FormationPlaceRepository::class);
        $this->formationPlaceRepos = $formationPlaceRepos;

        /** @var TotalsRepository $totalsRepos */
        $totalsRepos = $container->get(TotalsRepository::class);
        $this->totalsRepos = $totalsRepos;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var PointsCreator $pointsCreator */
        $pointsCreator = $container->get(PointsCreator::class);
        $this->pointsCreator = $pointsCreator;
    }

    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:update-player-totals')
            // the short description shown while running "php bin/console list"
            ->setDescription('update playertotals')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('updates playertotals');

        // $this->addOption('forceUpdateWhenEqual', null, InputOption::VALUE_NONE, '');
        // league, seasons, viewPeriodDate

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-update-playertotals');
        $this->getLogger()->info('starting command app:update-playertotals');

        try {
            $compConfig = $this->inputHelper->getCompetitionConfigFromInput($input);
            $totalsCalculator = new TotalsCalculator($compConfig);

            $viewPeriods = $this->viewPeriodRepos->findBy(['sourceCompetition' => $compConfig->getSourceCompetition()]);
            foreach ($viewPeriods as $viewPeriod) {
                $this->getLogger()->info('viewPeriod: ' . $viewPeriod);
                $s11Players = $this->s11PlayerRepos->findByExt($viewPeriod);
                foreach ($s11Players as $s11Player) {
                    $playerStats = array_values($s11Player->getStatistics()->toArray());
                    $totalsCalculator->updateTotals($s11Player->getTotals(), $playerStats);
                    $this->totalsRepos->save($s11Player->getTotals(), true);

                    $totalsCalculator->updateTotalPoints($s11Player);
                    $this->s11PlayerRepos->save($s11Player, true);

                    $formationPlaces = $this->formationPlaceRepos->findByPlayer($s11Player);
                    foreach ($formationPlaces as $formationPlace) {
                        $totalsCalculator->updateTotals($formationPlace->getTotals(), $formationPlace->getStatistics());
                        $this->totalsRepos->save($s11Player->getTotals(), true);

                        $totalsCalculator->updateTotalPoints($formationPlace);
                        $this->formationPlaceRepos->save($formationPlace, true);
                    }

                    $this->getLogger()->info(
                        '   player "' . $s11Player->getPerson()->getName() . '": ' . $s11Player->getTotalPoints()
                    );
                }
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }
}
