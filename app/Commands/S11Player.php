<?php

namespace App\Commands;

use App\Command;
use App\Commands\S11Player\Action as S11PlayerAction;
use Psr\Container\ContainerInterface;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Person;
use Sports\Person\Repository as PersonRepository;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Player as S11PlayerBase;
use SuperElf\Player\Repository as S11PlayerRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:s11player create --league=Eredivisie --season=2015/2016 --firstName="Mike" --lastName="Havenaar" --dateOfBirth="1987-05-20" --loglevel=200
 */
class S11Player extends Command
{
    protected AgainstGameRepository $againstGameRepos;
    protected S11PlayerRepository $s11PlayerRepos;
    protected PersonRepository $personRepos;

    protected CompetitionConfigRepository $competitionConfigRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

        /** @var S11PlayerRepository $s11PlayerRepos */
        $s11PlayerRepos = $container->get(S11PlayerRepository::class);
        $this->s11PlayerRepos = $s11PlayerRepos;

        /** @var PersonRepository $personRepos */
        $personRepos = $container->get(PersonRepository::class);
        $this->personRepos = $personRepos;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        parent::__construct($container);
    }

    protected function configure(): void
    {
        $this
            ->setName('app:s11player')
            ->setDescription('admins the s11player')
            ->setHelp('admins the s11player');

        $actions = array_map(fn(S11PlayerAction $action) => $action->value, S11PlayerAction::cases());
        $this->addArgument('action', InputArgument::REQUIRED, join(',', $actions));

        $this->addOption('league', null, InputOption::VALUE_REQUIRED, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_REQUIRED, '2014/2015');
        $this->addOption('firstName', null, InputOption::VALUE_REQUIRED, 'Mike');
        $this->addOption('nameInsertion', null, InputOption::VALUE_OPTIONAL, 'van');
        $this->addOption('lastName', null, InputOption::VALUE_REQUIRED, 'Havenaar');
        $this->addOption('dateOfBirth', null, InputOption::VALUE_REQUIRED, 'Y-m-d');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initLogger($input, 'command-s11player');

        try {
            $action = $this->getAction($input);

            switch ($action) {
                case S11PlayerAction::Create:
                    return $this->create($input);
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

    protected function getAction(InputInterface $input): S11PlayerAction
    {
        /** @var string $action */
        $action = $input->getArgument('action');
        return S11PlayerAction::from($action);
    }

    protected function create(InputInterface $input): int
    {
        $competitionConfig = $this->getCompetitionConfigFromInput($input);

        $firstName = $this->getStringFromInput($input, 'firstName');
        $nameInsertion = $this->getStringFromInput($input, 'nameInsertion', '');
        $nameInsertion = strlen($nameInsertion) === 0 ? null : $nameInsertion;
        $lastName = $this->getStringFromInput($input, 'lastName');
        $dateOfBirth = $this->getDateTimeFromInput($input, 'dateOfBirth', 'Y-m-d');

        // voor welke viewperiods moet ik de persoon toevoegen??
//        --createAndJoin
//        --assemble
//        --transfer

        $viewPeriods = $competitionConfig->getViewPeriods();
        $existingPerson = $this->personRepos->findOneBy([
                                                            'firstName' => $firstName,
                                                            'nameInsertion' => $nameInsertion,
                                                            'lastName' => $lastName,
                                                            'dateOfBirth' => $dateOfBirth
                                                        ]);
        if ($existingPerson !== null) {
            throw new \Exception('implement', E_ERROR);
        };
        $person = new Person($firstName, $nameInsertion, $lastName);
        $person->setDateOfBirth($dateOfBirth);
        $this->personRepos->save($person);
        foreach ($viewPeriods as $viewPeriod) {
            $s11Player = new S11PlayerBase($viewPeriod, $person);
            $this->s11PlayerRepos->save($s11Player);
        }


        $this->getLogger()->info('s11Players created and saved');
        // throw new \Exception('implement', E_ERROR);
        return 0;
    }
}
