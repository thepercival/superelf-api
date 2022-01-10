<?php

namespace App\Commands;

use App\Command;
use App\Commands\CompetitionConfig\Action as CompetitionConfigAction;
use Psr\Container\ContainerInterface;
use Sports\Competition;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Team\Player\Repository as TeamPlayerRepository;
use SuperElf\CompetitionConfig\Administrator;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * create --league=eredivisie --season=2014/2015 --createAndJoinStart="2014-07-23 12:00" --assemblePeriod="2014-08-23 12:00=>2014-09-23 12:00" --assemblePeriod="2015-02-01 12:00=>2015-02-03 12:00" --loglevel=200
 */
class CompetitionConfig extends Command
{
    public const DateTimeFormat = 'Y-m-d H:i';
    protected AgainstGameRepository $againstGameRepos;
    protected TeamPlayerRepository $teamPlayerRepos;
    protected CompetitionConfigRepository $competitionConfigRepos;

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

        parent::__construct($container);
    }

    protected function configure(): void
    {
        $this
            ->setName('app:competitionconfig')
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
                case CompetitionConfigAction::SetCreateAndJoinStart:
                    return $this->setCreateAndJoinStart($input);
                case CompetitionConfigAction::SetAssemblePeriod:
                    return $this->setAssemblePeriod($input);
                case CompetitionConfigAction::SetTransferPeriod:
                    return $this->setTransferPeriod($input);
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
        $competition = $this->getCompetitionFromInput($input);
        if ($competition === null) {
            throw new \Exception('competition not found', E_ERROR);
        }
        $admin = $this->getAdministrator($competition);
        $admin->create(
            $competition,
            $this->getDateTimeFromInput($input, 'createAndJoinStart'),
            $this->getPeriodFromInput($input, 'assemblePeriod'),
            $this->getPeriodFromInput($input, 'transferPeriod')
        );
        // throw new \Exception('implement', E_ERROR);
        return 0;
    }

    protected function getAdministrator(Competition $competition): Administrator
    {
        $existingCompetitionConfigs = $this->competitionConfigRepos->findBy(['competition' => $competition]);
        return new Administrator($existingCompetitionConfigs);
    }

    protected function setCreateAndJoinStart(InputInterface $input): int
    {
        throw new \Exception('implement', E_ERROR);
    }

    protected function setAssemblePeriod(InputInterface $input): int
    {
        return 0;
    }

    protected function setTransferPeriod(InputInterface $input): int
    {
        // input seasonname and TransferPeriod-dates

        // check if     no games within period
        //              if after assembleperiod end
        //              if before season end
        //              check if no transfers has been done? maybe check if is in past and show warning
        //              run gameRoundSync in some way
        return 0;
    }

    protected function getCompetitionConfigFromInput(InputInterface $input): \SuperElf\CompetitionConfig
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
