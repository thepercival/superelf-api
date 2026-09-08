<?php

declare(strict_types=1);

namespace App\Commands;

use App\Command;
use App\Syncers\AchievementSyncer;
use Psr\Container\ContainerInterface;
use SuperElf\League;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:achievement --league=Eredivisie --season=2025/2026 --s11leaguename=Competition --loglevel=Info
 */
final class AchievementCommand extends Command
{
    private string $customName = 'achievement';

    protected AchievementSyncer $achievementSyncer;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        /** @var AchievementSyncer $achievementSyncer */
        $achievementSyncer = $container->get(AchievementSyncer::class);
        $this->achievementSyncer = $achievementSyncer;
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setName('app:' . $this->customName)
            ->setDescription('assigns achievements (trophies and badges) for a s11-league')
            ->setHelp('assigns achievements (trophies and badges) for a s11-league');

        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');
        $this->addOption('s11leaguename', null, InputOption::VALUE_REQUIRED, 'Competition|Cup|SuperCup');

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
        $this->achievementSyncer->setLogger($logger);

        try {
            $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);
            $s11LeagueName = $this->inputHelper->getStringFromInput($input, 's11leaguename');
            $league = League::from($s11LeagueName);

            $this->achievementSyncer->syncPoolAchievements($competitionConfig, $league);
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        return 0;
    }
}
