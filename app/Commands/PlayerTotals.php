<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use Psr\Container\ContainerInterface;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Player\Totals\Calculator as PlayerTotalsCalculator;
use SuperElf\Player\Totals\Repository as S11PlayerTotalsRepository;
use SuperElf\Points\Creator as PointsCreator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PlayerTotals extends Command
{
    protected ViewPeriodRepository $viewPeriodRepos;
    protected S11PlayerRepository $s11PlayerRepos;
    protected S11PlayerTotalsRepository $s11PlayerTotalsRepos;
    protected PointsCreator $pointsCreator;
    protected CompetitionConfigRepository $competitionConfigRepos;
    protected PlayerTotalsCalculator $playerTotalsCalculator;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        /** @var ViewPeriodRepository $viewPeriodRepos */
        $viewPeriodRepos = $container->get(ViewPeriodRepository::class);
        $this->viewPeriodRepos = $viewPeriodRepos;

        /** @var S11PlayerRepository $s11PlayerRepos */
        $s11PlayerRepos = $container->get(S11PlayerRepository::class);
        $this->s11PlayerRepos = $s11PlayerRepos;

        /** @var S11PlayerTotalsRepository $s11PlayerTotalsRepos */
        $s11PlayerTotalsRepos = $container->get(S11PlayerTotalsRepository::class);
        $this->s11PlayerTotalsRepos = $s11PlayerTotalsRepos;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var PointsCreator $pointsCreator */
        $pointsCreator = $container->get(PointsCreator::class);
        $this->pointsCreator = $pointsCreator;

        $this->playerTotalsCalculator = new PlayerTotalsCalculator();
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
            $compConfig = $this->getCompetitionConfigFromInput($input);

            $viewPeriods = $this->viewPeriodRepos->findBy(['sourceCompetition' => $compConfig->getSourceCompetition()]);
            foreach ($viewPeriods as $viewPeriod) {
                $this->getLogger()->info('viewPeriod: ' . $viewPeriod);
                $s11Players = $this->s11PlayerRepos->findByExt($viewPeriod);
                foreach ($s11Players as $s11Player) {
                    $this->playerTotalsCalculator->updateTotals($s11Player);
                    $this->playerTotalsCalculator->updateTotalPoints($compConfig->getPoints(), $s11Player);
                    $this->s11PlayerTotalsRepos->save($s11Player->getTotals(), true);
                    $this->s11PlayerRepos->save($s11Player, true);
                    $p = $s11Player->getTotalPoints();
                    $this->getLogger()->info('   player "' . $s11Player->getPerson()->getName() . '": ' . $p);
                }
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    protected function getCompetitionConfigFromInput(InputInterface $input): CompetitionConfig
    {
        $competition = $this->getCompetitionFromInput($input);
        if ($competition === null) {
            throw new \Exception('competition not found', E_ERROR);
        }
        $competitionConfig = $this->competitionConfigRepos->findOneBy(['competition' => $competition]);
        if ($competitionConfig === null) {
            throw new \Exception('competition not found', E_ERROR);
        }
        return $competitionConfig;
    }
}
