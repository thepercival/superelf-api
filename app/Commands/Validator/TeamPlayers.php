<?php

namespace App\Commands\Validator;

use App\Command;
use Psr\Container\ContainerInterface;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Team\Player\Repository as TeamPlayerRepository;
use Sports\Team\Role\Validator as TeamRoleValidator;
use SportsImport\Getter as ImportGetter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class TeamPlayers extends Command
{
    protected ImportGetter $getter;
    protected AgainstGameRepository $againstGameRepos;
    protected TeamPlayerRepository $teamPlayerRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var ImportGetter $getter */
        $getter = $container->get(ImportGetter::class);
        $this->getter = $getter;

        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

        /** @var TeamPlayerRepository $teamPlayerRepos */
        $teamPlayerRepos = $container->get(TeamPlayerRepository::class);
        $this->teamPlayerRepos = $teamPlayerRepos;

        parent::__construct($container);
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('app:validate-team-players')
            // the short description shown while running "php bin/console list"
            ->setDescription('validates the team-players')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('validates the team-players');

        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');

        parent::configure();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-validate-team-players');

        // $this->getLogger()->info('for example checks if there are no overlapping player periods');


        // $teamPlayerOutput = new TeamPlayerOutput($this->getLogger());

        try {
            $competition = $this->inputHelper->getCompetitionFromInput($input);
            if ($competition === null) {
                throw new \Exception('competition could not be found', E_ERROR);
            }
            $validator = new TeamRoleValidator();

            $seasonPeriod = $competition->getSeason()->getPeriod();
            foreach ($competition->getTeams() as $team) {
                $this->getLogger()->info('validating team ' . $team->__toString());
                $teamPlayers = $this->teamPlayerRepos->findByExt($seasonPeriod, $team);
                foreach ($teamPlayers as $teamPlayer) {
                    $validator->validate($teamPlayer, $competition);
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
