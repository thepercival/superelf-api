<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Container\ContainerInterface;
use Selective\Config\Configuration;
use SuperElf\Points\Creator as PointsCreator;
use SuperElf\Repositories\CompetitionConfigRepository;
use SuperElf\Repositories\FormationPlaceRepository;
use SuperElf\Repositories\S11PlayerRepository;
use SuperElf\Repositories\ViewPeriodRepository;
use SuperElf\Totals;
use SuperElf\Totals\Calculator as TotalsCalculator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PlayerTotals extends Command
{
    protected ViewPeriodRepository $viewPeriodRepos;
    protected S11PlayerRepository $s11PlayerRepos;
    protected FormationPlaceRepository $formationPlaceRepos;
    /** @var EntityRepository<Totals>  */
    protected EntityRepository $totalsRepos;
    protected PointsCreator $pointsCreator;
    protected CompetitionConfigRepository $competitionConfigRepos;
    protected EntityManagerInterface $entityManager;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        $this->config = $config;

        /** @var EntityManagerInterface entityManager */
        $this->entityManager = $container->get(EntityManagerInterface::class);

        /** @var ViewPeriodRepository $viewPeriodRepos */
        $viewPeriodRepos = $container->get(ViewPeriodRepository::class);
        $this->viewPeriodRepos = $viewPeriodRepos;

        /** @var S11PlayerRepository $s11PlayerRepos */
        $s11PlayerRepos = $container->get(S11PlayerRepository::class);
        $this->s11PlayerRepos = $s11PlayerRepos;

        /** @var FormationPlaceRepository $formationPlaceRepos */
        $formationPlaceRepos = $container->get(FormationPlaceRepository::class);
        $this->formationPlaceRepos = $formationPlaceRepos;

        $this->totalsRepos = $this->entityManager->getRepository(Totals::class);

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var PointsCreator $pointsCreator */
        $pointsCreator = $container->get(PointsCreator::class);
        $this->pointsCreator = $pointsCreator;
    }

    #[\Override]
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

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-update-playertotals');
        $this->getLogger()->info('starting command app:update-playertotals');

        try {
            $compConfig = $this->inputHelper->getCompetitionConfigFromInput($input);
            $points = $compConfig->getPoints();
            $totalsCalculator = new TotalsCalculator();

            $viewPeriods = $this->viewPeriodRepos->findBy(['sourceCompetition' => $compConfig->getSourceCompetition()]);
            foreach ($viewPeriods as $viewPeriod) {
                $this->getLogger()->info('viewPeriod: ' . $viewPeriod->getPeriod()->toIso8601());
                $s11Players = $this->s11PlayerRepos->findByViewPeriod($viewPeriod);
                foreach ($s11Players as $s11Player) {
                    $playerStats = array_values($s11Player->getStatistics()->toArray());
                    $totalsCalculator->updateTotals($s11Player->getTotals(), $playerStats);
                    $this->entityManager->persist($s11Player->getTotals());
                    $this->entityManager->flush();

                    $totalsCalculator->updateTotalPoints($s11Player, $points);
                    $this->entityManager->persist($s11Player);
                    $this->entityManager->flush();

                    $formationPlaces = $this->formationPlaceRepos->findByPlayer($s11Player);
                    foreach ($formationPlaces as $formationPlace) {
                        $totalsCalculator->updateTotals($formationPlace->getTotals(), $formationPlace->getStatistics());
                        $this->entityManager->persist($s11Player->getTotals());
                        $this->entityManager->flush();

                        $totalsCalculator->updateTotalPoints($formationPlace, $points);
                        $this->entityManager->persist($formationPlace);
                        $this->entityManager->flush();
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
