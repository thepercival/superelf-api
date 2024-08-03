<?php

namespace App\Commands;

use App\Command;
use App\Commands\CompetitionConfig\Action as CompetitionConfigAction;
use Psr\Container\ContainerInterface;
use Sports\Competition;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Team\Player\Repository as TeamPlayerRepository;
use SuperElf\CompetitionConfig\Administrator;
use SuperElf\CompetitionConfig as CompetitionConfigBase;
use SuperElf\CompetitionConfig\Output as CompetitionConfigOutput;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\GameRound\Syncer as GameRoundSyncer;
use SuperElf\Player\Syncer as S11PlayerSyncer;
use SuperElf\Statistics\Syncer as StatisticsSyncer;
use SuperElf\Substitute\Appearance\Syncer as AppearanceSyncer;
use SuperElf\Totals\Syncer as TotalsSyncer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * php bin/console.php app:admin-competitionconfigs create --league=Eredivisie --season=2024/2025 --createAndJoinStart="2024-08-03 12:00" --assemblePeriod="2024-09-03 08:00=>2024-09-14 16:00" --transferPeriod="2025-02-02 12:00=>2025-02-07 18:00" --loglevel=200
 * php bin/console.php app:admin-competitionconfigs create --league=Eredivisie --season=2023/2024 --createAndJoinStart="2014-07-23 12:00" --assemblePeriod="2014-08-23 12:00=>2014-09-23 12:00" --transferPeriod="2015-02-01 12:00=>2015-02-03 12:00" --loglevel=200
 *      create                  --league=Eredivisie
 *                              --season=2014/2015
 *                              --createAndJoinStart="2014-07-23 12:00"
 *                              --assemblePeriod="2014-08-23 12:00=>2014-09-23 12:00"
 *                              --transferPeriod="2015-02-01 12:00=>2015-02-03 12:00"
 *                              --loglevel=200
 * php bin/console.php app:admin-competitionconfigs update-assemble-period  --league=Eredivisie --season=2022/2023 --assemblePeriod="2022-09-05 11:00=>2022-09-09 17:30" --loglevel=200
 *      update-assemble-period  --league=Eredivisie
 *                              --season=2022/2023
 *                              --assemblePeriod="2022-09-05 11:00=>2022-09-09 17:30"
 *                              --loglevel=200
 * php bin/console.php app:admin-competitionconfigs update-transfer-period  --league=Eredivisie --season=2022/2023 --transferPeriod="2023-01-23 15:00=>2023-01-24 18:45" --loglevel=200
 *      update-transfer-period  --league=Eredivisie
 *                              --season=2022/2023
 *                              --transferPeriod="2023-01-23 15:00=>2023-01-24 18:45"
 *                              --loglevel=200
 * php bin/console.php app:admin-competitionconfigs show --league=Eredivisie --season=2023/2024
 *      show                    --league=Eredivisie
 *                              --season=2022/2023
 */
class CompetitionConfig extends Command
{
    public const DateTimeFormat = 'Y-m-d H:i';
    protected AgainstGameRepository $againstGameRepos;
    protected TeamPlayerRepository $teamPlayerRepos;
    protected CompetitionConfigRepository $competitionConfigRepos;
    protected GameRoundSyncer $gameRoundSyncer;
    protected S11PlayerSyncer $s11PlayerSyncer;
    protected StatisticsSyncer $statisticsSyncer;
    protected AppearanceSyncer $appearanceSyncer;
    protected TotalsSyncer $totalsSyncer;

    public function __construct(ContainerInterface $container)
    {
        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

        /** @var TeamPlayerRepository $teamPlayerRepos */
        $teamPlayerRepos = $container->get(TeamPlayerRepository::class);
        $this->teamPlayerRepos = $teamPlayerRepos;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var GameRoundSyncer $gameRoundSyncer */
        $gameRoundSyncer = $container->get(GameRoundSyncer::class);
        $this->gameRoundSyncer = $gameRoundSyncer;

        /** @var S11PlayerSyncer $s11PlayerSyncer */
        $s11PlayerSyncer = $container->get(S11PlayerSyncer::class);
        $this->s11PlayerSyncer = $s11PlayerSyncer;

        /** @var StatisticsSyncer $statisticsSyncer */
        $statisticsSyncer = $container->get(StatisticsSyncer::class);
        $this->statisticsSyncer = $statisticsSyncer;

        /** @var AppearanceSyncer $appearanceSyncer */
        $appearanceSyncer = $container->get(AppearanceSyncer::class);
        $this->appearanceSyncer = $appearanceSyncer;

        /** @var TotalsSyncer $totalsSyncer */
        $totalsSyncer = $container->get(TotalsSyncer::class);
        $this->totalsSyncer = $totalsSyncer;
        parent::__construct($container);
    }

    protected function configure(): void
    {
        $this
            ->setName('app:admin-competitionconfigs')
            ->setDescription('admins the competitionconfigs')
            ->setHelp('admins the competitionconfigs');

        $actions = array_map(fn(CompetitionConfigAction $action) => $action->value, CompetitionConfigAction::cases());
        $this->addArgument('action', InputArgument::REQUIRED, join(',', $actions));

        $f = CompetitionConfig::DateTimeFormat;
        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');
        $this->addOption('createAndJoinStart', null, InputOption::VALUE_REQUIRED, $f);
        $this->addOption('assemblePeriod', null, InputOption::VALUE_REQUIRED, $f . '=>' . $f);
        $this->addOption('transferPeriod', null, InputOption::VALUE_REQUIRED, $f . '=> ' . $f);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-competitionconfig');

        try {
            $action = $this->getAction($input);

            switch ($action) {
                case CompetitionConfigAction::Create:
                    return $this->create($input);
                case CompetitionConfigAction::UpdateAssemblePeriod:
                    return $this->updateAssemblePeriod($input);
                case CompetitionConfigAction::UpdateTransferPeriod:
                    return $this->updateTransferPeriod($input);
                case CompetitionConfigAction::Show:
                    return $this->show($input);
                case CompetitionConfigAction::Remove:
                    return $this->remove($input);
                default:
                    throw new \Exception('onbekende actie', E_ERROR);
            }
        } catch (\Exception $e) {
            if ($this->logger !== null) {
                $this->logger->error($e->getMessage());
            }
        }
        return 0;
    }

    protected function getAction(InputInterface $input): CompetitionConfigAction
    {
        /** @var string $action */
        $action = $input->getArgument('action');
        return CompetitionConfigAction::from($action);
    }

    protected function create(InputInterface $input): int
    {
        $competition = $this->inputHelper->getCompetitionFromInput($input);
        if ($competition === null) {
            throw new \Exception('competition not found', E_ERROR);
        }
        $admin = $this->getAdministrator($competition);
        $competitionConfig = $admin->create(
            $competition,
            $this->inputHelper->getDateTimeFromInput($input, 'createAndJoinStart'),
            $this->inputHelper->getPeriodFromInput($input, 'assemblePeriod'),
            $this->inputHelper->getPeriodFromInput($input, 'transferPeriod'),
            $this->againstGameRepos->getCompetitionGames($competition)
        );
        $this->competitionConfigRepos->save($competitionConfig);
        $this->getLogger()->info('competitionConfig created and saved');
        // throw new \Exception('implement', E_ERROR);
        return 0;
    }

    protected function getAdministrator(Competition $competition): Administrator
    {
        $existingCompetitionConfigs = $this->competitionConfigRepos->findBy(['sourceCompetition' => $competition]);
        return new Administrator($existingCompetitionConfigs);
    }

    protected function updateAssemblePeriod(InputInterface $input): int
    {
        $competition = $this->inputHelper->getCompetitionFromInput($input);
        if ($competition === null) {
            throw new \Exception('competition not found', E_ERROR);
        }
        $admin = $this->getAdministrator($competition);
        $competitionConfig = $admin->updateAssemblePeriod(
            $competition,
            $this->inputHelper->getPeriodFromInput($input, 'assemblePeriod'),
            $this->againstGameRepos->getCompetitionGames($competition)
        );
        $this->competitionConfigRepos->save($competitionConfig);
        $this->getLogger()->info('competitionConfig updated and saved assemblePeriod');

        $this->syncGameRoundNumbers($competition, $competitionConfig);
        $this->getLogger()->info('s11Player, statistics and appearances synced');

        return 0;
    }

    protected function updateTransferPeriod(InputInterface $input): int
    {
        $competition = $this->inputHelper->getCompetitionFromInput($input);
        if ($competition === null) {
            throw new \Exception('competition not found', E_ERROR);
        }
        $admin = $this->getAdministrator($competition);
        $competitionConfig = $admin->updateTransferPeriod(
            $competition,
            $this->inputHelper->getPeriodFromInput($input, 'transferPeriod'),
            $this->againstGameRepos->getCompetitionGames($competition)
        );
        $this->competitionConfigRepos->save($competitionConfig);
        $this->getLogger()->info('competitionConfig updated and saved transferPeriod');

        $this->syncGameRoundNumbers($competition, $competitionConfig);
        $this->getLogger()->info('s11Player, statistics and appearances synced');

        return 0;
    }

    private function syncGameRoundNumbers(Competition $competition, CompetitionConfigBase $competitionConfig): void {
        $changedGameRoundNumbers = $this->gameRoundSyncer->syncViewPeriodGameRounds($competitionConfig);
        $this->getLogger()->info(count($changedGameRoundNumbers) . ' gameRoundNumbers synced');

        foreach ($changedGameRoundNumbers as $changedGameRoundNumber) {
            // get games of $changedGameRoundNumber
            $games = $this->againstGameRepos->getCompetitionGames(
                $competition,
                null,
                $changedGameRoundNumber,
            );
            foreach ($games as $game) {
                $this->s11PlayerSyncer->syncS11Players($competitionConfig, $game);
                $this->statisticsSyncer->syncStatistics($competitionConfig, $game);
                $this->appearanceSyncer->syncSubstituteAppearances($competitionConfig, $game);
                $this->totalsSyncer->syncTotals($competitionConfig, $game);
            }
        }
    }

    protected function show(InputInterface $input): int
    {
        $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);

        $output = new CompetitionConfigOutput($this->logger);
        $againstGames = $this->againstGameRepos->getCompetitionGames($competitionConfig->getSourceCompetition());
        $output->output($competitionConfig, $againstGames);
        return 0;
    }

    protected function remove(InputInterface $input): int
    {
        $sourceCompetition = $this->inputHelper->getCompetitionFromInput($input);
        if ($sourceCompetition === null) {
            throw new \Exception('competition not found', E_ERROR);
        }

        $competitionConfigs = $this->competitionConfigRepos->findBy(['sourceCompetition' => $sourceCompetition]);
        $nrOfCompetitionConfigs = count($competitionConfigs);
        while( $competitionConfig = array_shift($competitionConfigs ) ) {
            $this->competitionConfigRepos->remove($competitionConfig);
        }

        $this->getLogger()->info( $nrOfCompetitionConfigs . ' competitionConfigs removed');
        // throw new \Exception('implement', E_ERROR);
        return 0;
    }
}
