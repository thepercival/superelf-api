<?php

namespace App\Commands;

use App\Command;
use App\Commands\Person\Action as PersonAction;
use App\MailHandler;
use DateTimeInterface;
use League\Period\Period;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Person;
use Sports\Person\Repository as PersonRepository;
use Sports\Sport\FootballLine;
use Sports\Team\Player\Repository as PlayerRepository;
use Sports\Team\Repository as TeamRepository;
use Sports\Team\Role\Editor as RoleEditor;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Formation\Place\Repository as FormationPlaceRepository;
use SuperElf\OneTeamSimultaneous;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Player\Syncer as S11PlayerSyncer;
use SuperElf\Totals\Calculator as TotalsCalculator;
use SuperElf\Totals\Repository as TotalsRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * php bin/console.php app:person
 *      fetch                 --league=Eredivisie --season=2022/2023 --firstName=Justin --loglevel=200
 *      createWithS11Players  --league=Eredivisie --season=2022/2023 --firstName="Coen" --lastName="Dunnink" --dateOfBirth="1987-05-20" --loglevel=200
 *      makeTransfer          --league=Eredivisie --season=2022/2023 --id=21 --at=2022-08-23 --newTeamAbbr="EMM" --newLine=A --loglevel=200
 *      updateCurrentLine     --league=Eredivisie --season=2022/2023 --id=21 --newLine=A --loglevel=200
 *      stop                  --league=Eredivisie --season=2022/2023 --id=21 --at=2022-08-23 --loglevel=200
 */
class PersonCommand extends Command
{
    private string $customName = 'person';

    protected S11PlayerSyncer $s11PlayerSyncer;
    protected AgainstGameRepository $againstGameRepos;
    protected S11PlayerRepository $s11PlayerRepos;
    protected PlayerRepository $playerRepos;
    protected FormationPlaceRepository $formationPlaceRepos;
    protected TotalsRepository $totalsRepos;
    protected TeamRepository $teamRepos;
    protected PersonRepository $personRepos;
    protected CompetitionConfigRepository $competitionConfigRepos;

    public function __construct(ContainerInterface $container)
    {
        /** @var S11PlayerSyncer $s11PlayerSyncer */
        $s11PlayerSyncer = $container->get(S11PlayerSyncer::class);
        $this->s11PlayerSyncer = $s11PlayerSyncer;

        /** @var AgainstGameRepository $againstGameRepos */
        $againstGameRepos = $container->get(AgainstGameRepository::class);
        $this->againstGameRepos = $againstGameRepos;

        /** @var S11PlayerRepository $s11PlayerRepos */
        $s11PlayerRepos = $container->get(S11PlayerRepository::class);
        $this->s11PlayerRepos = $s11PlayerRepos;

        /** @var FormationPlaceRepository $formationPlaceRepos */
        $formationPlaceRepos = $container->get(FormationPlaceRepository::class);
        $this->formationPlaceRepos = $formationPlaceRepos;

        /** @var PersonRepository $personRepos */
        $personRepos = $container->get(PersonRepository::class);
        $this->personRepos = $personRepos;

        /** @var PlayerRepository $playerRepos */
        $playerRepos = $container->get(PlayerRepository::class);
        $this->playerRepos = $playerRepos;

        /** @var TotalsRepository $totalsRepos */
        $totalsRepos = $container->get(TotalsRepository::class);
        $this->totalsRepos = $totalsRepos;

        /** @var TeamRepository $teamRepos */
        $teamRepos = $container->get(TeamRepository::class);
        $this->teamRepos = $teamRepos;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        parent::__construct($container);
    }

    protected function configure(): void
    {
        $this
            ->setName('app:' . $this->customName)
            ->setDescription('admins the persons')
            ->setHelp('admins the persons');

        $actions = array_map(fn(PersonAction $action) => $action->value, PersonAction::cases());
        $this->addArgument('action', InputArgument::REQUIRED, join(',', $actions));

        $this->addOption('league', null, InputOption::VALUE_OPTIONAL, 'Eredivisie');
        $this->addOption('season', null, InputOption::VALUE_OPTIONAL, '2014/2015');
        $this->addOption('firstName', null, InputOption::VALUE_OPTIONAL, 'Mike');
        $this->addOption('nameInsertion', null, InputOption::VALUE_OPTIONAL, 'van');
        $this->addOption('lastName', null, InputOption::VALUE_OPTIONAL, 'Havenaar');
        $this->addOption('dateOfBirth', null, InputOption::VALUE_OPTIONAL, 'Y-m-d');
        $this->addOption('id', null, InputOption::VALUE_OPTIONAL, 'nr');
        $this->addOption('at', null, InputOption::VALUE_OPTIONAL, 'Y-m-d');
        $this->addOption('newTeamAbbr', null, InputOption::VALUE_OPTIONAL, 'EMM');
        $this->addOption('newLine', null, InputOption::VALUE_OPTIONAL, 'A||M||D||G');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $loggerName = 'command-' . $this->customName;
        $logger = $this->initLoggerNew(
            $this->getLogLevel($input),
            $this->getStreamDef($input, $loggerName),
            $loggerName,
        );
        $this->s11PlayerSyncer->setLogger($logger);;

        try {
            $action = $this->getAction($input);

            switch ($action) {
                case PersonAction::Fetch:
                    return $this->fetch($input);
                case PersonAction::CreateWithS11Players:
                    return $this->create($input);
                case PersonAction::MakeTransfer:
                    return $this->transfer($input);
                case PersonAction::UpdateCurrentLine:
                    return $this->updateCurrentLine($input);
                case PersonAction::Stop:
                    return $this->stop($input);
                default:
                    throw new \Exception('onbekende actie', E_ERROR);
            }
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
        }
        return 0;
    }

    protected function getAction(InputInterface $input): PersonAction
    {
        /** @var string $action */
        $action = $input->getArgument('action');
        return PersonAction::from($action);
    }

    protected function fetch(InputInterface $input): int
    {
        $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);

        $firstName = $this->inputHelper->getStringFromInput($input, 'firstName', '');
        $lastName = $this->inputHelper->getStringFromInput($input, 'lastName', '');

        // voor welke viewperiods moet ik de persoon toevoegen??
//        --createAndJoin
//        --assemble
//        --transfer

        // $viewPeriods = $competitionConfig->getViewPeriods();
        $filters = [];
        if (strlen($firstName) > 0) {
            $filters['firstName'] = $firstName;
        }
        if (strlen($lastName) > 0) {
            $filters['lastName'] = $lastName;
        }
        $persons = $this->personRepos->findBy($filters);
        if (count($persons) == 0) {
            throw new \Exception('no persons found', E_ERROR);
        }

        foreach ($persons as $person) {
            $this->logPerson($person, $competitionConfig);
        }
        // $this->getLogger()->info('FirstName: "' . ->get() person and s11Players created and saved');
        // }


        // throw new \Exception('implement', E_ERROR);
        return 0;
    }

    private function logPerson(Person $person, CompetitionConfig $competitionConfig, Period|null $period = null): void
    {
        if ($period === null) {
            $period = $competitionConfig->getSeason()->getPeriod();
        }
        $this->getLogger()->info($person->getName() . '(id:' . (string)$person->getId() . ')');
        $this->getLogger()->info('    teamPlayers');
        foreach ($person->getPlayers(null, $period) as $player) {
            $line = FootballLine::getFirstChar(FootballLine::from($player->getLine()));
            $periodDescription = '        ' . $player->getPeriod()->toIso80000('Y-m-d');
            $msg = $periodDescription . ' => ' . $player->getTeam()->getName() . ' (' . $line . ')';
            $this->getLogger()->info($msg);
        }

        $this->getLogger()->info('    viewPeriods');
        $viewPeriods = $competitionConfig->getViewPeriods($period);
        foreach ($viewPeriods as $viewPeriod) {
            $s11Player = $this->s11PlayerRepos->findOneBy(
                ["viewPeriod" => $viewPeriod, "person" => $person]
            );
            if ($s11Player === null) {
                continue;
            }

            $msg = '        ' . $viewPeriod->getPeriod()->toIso80000('Y-m-d');
            $msg .= ' => totalpoints: ' . $s11Player->getTotalPoints();
            $msg .= ', nrOfWins: ' . $s11Player->getTotals()->getNrOfWins();
            $msg .= ', nrOfDraws: ' . $s11Player->getTotals()->getNrOfDraws();
            $this->getLogger()->info($msg);
        }
    }

    protected function create(InputInterface $input): int
    {
        $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);

        $firstName = $this->inputHelper->getStringFromInput($input, 'firstName');
        $nameInsertion = $this->inputHelper->getStringFromInput($input, 'nameInsertion', '');
        $nameInsertion = strlen($nameInsertion) === 0 ? null : $nameInsertion;
        $lastName = $this->inputHelper->getStringFromInput($input, 'lastName');
        $dateOfBirth = $this->inputHelper->getDateTimeFromInput($input, 'dateOfBirth', 'Y-m-d');

        // $viewPeriods = $competitionConfig->getViewPeriods();
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

        $this->syncPerson($person, $competitionConfig);

        $this->getLogger()->info('person(id:' . (string)$person->getId() . ') and s11Players created and saved');
        // throw new \Exception('implement', E_ERROR);
        return 0;
    }



    protected function transfer(InputInterface $input): int
    {
        $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);

        $id = $this->inputHelper->getStringFromInput($input, 'id');

        $person = $this->personRepos->find($id);
        if ($person === null) {
            throw new \Exception('person not found', E_ERROR);
        }

        $newAt = $this->inputHelper->getDateTimeFromInput($input, 'at', 'Y-m-d');
        $newAt = $newAt->setTime(0, 0);

        $newTeamAbbr = $this->inputHelper->getStringFromInput($input, 'newTeamAbbr',);
        $newTeam = $this->teamRepos->findOneBy(['abbreviation' => $newTeamAbbr]);
        if ($newTeam === null) {
            throw new \Exception('team not found by abbr. : "' . $newTeamAbbr . '"', E_ERROR);
        }

        $newLine = $this->getLineFromInput($input);

        $season = $competitionConfig->getSeason();

        $roleEditor = new RoleEditor($this->getLogger());
        $roleEditor->update($season, $person, $newAt, $newTeam, $newLine);

        $this->personRepos->save($person);

        $this->syncPerson($person, $competitionConfig);
        $this->syncS11PlayerTotals($person, $competitionConfig);

        $this->getLogger()->info('the person is now saved as:');
        $this->logPerson($person, $competitionConfig);

        // throw new \Exception('implement', E_ERROR);
        return 0;
    }

    protected function updateCurrentLine(InputInterface $input): int
    {
        $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);

        $id = $this->inputHelper->getStringFromInput($input, 'id');

        $person = $this->personRepos->find($id);
        if ($person === null) {
            throw new \Exception('person not found', E_ERROR);
        }

        $updateAt = (new \DateTimeImmutable())->setTime(0, 0);
//        if ($updateAt === false) {
//            throw new \Exception('could not reset time', E_ERROR);
//        }

        $newLine = $this->getLineFromInput($input);

        // $season = $competitionConfig->getSeason();

        $player = (new OneTeamSimultaneous())->getPlayer($person, $updateAt);
        if ($player === null) {
            throw new \Exception(
                                            '"' . $person->getName() . '" speelt op "' . $updateAt->format(
                                                DateTimeInterface::ISO8601
                ) . '" niet voor een team', E_ERROR
            );
        }

        $player->setLine($newLine->value);
//        $roleEditor = new RoleEditor();
//        $roleEditor->update($season, $person, $newAt, $newTeam, $newLine);

        $this->personRepos->save($person);

        $this->syncPerson($person, $competitionConfig);

        $this->syncS11PlayerTotals($person, $competitionConfig, $player->getPeriod());

        $this->getLogger()->info('the person is now saved as:');
        $this->logPerson($person, $competitionConfig);

        return 0;
    }

    protected function getLineFromInput(InputInterface $input): FootballLine
    {
        $newLineInput = $this->inputHelper->getStringFromInput($input, 'newLine');
        foreach (FootballLine::cases() as $footballLine) {
            if ($footballLine::getFirstChar($footballLine) === $newLineInput) {
                return $footballLine;
            }
        }
        throw new \Exception('line "' . $newLineInput . '" not found', E_ERROR);
    }

    protected function stop(InputInterface $input): int
    {
        $competitionConfig = $this->inputHelper->getCompetitionConfigFromInput($input);

        $id = $this->inputHelper->getStringFromInput($input, 'id');

        $person = $this->personRepos->find($id);
        if ($person === null) {
            throw new \Exception('person not found', E_ERROR);
        }

        $stopAt = $this->inputHelper->getDateTimeFromInput($input, 'at', 'Y-m-d');
        $stopAt = $stopAt->setTime(0, 0);

        $oneTeamSimultaneous = new OneTeamSimultaneous();
        $player = $oneTeamSimultaneous->getPlayer($person, $stopAt);
        if ($player === null) {
            $this->getLogger()->info('the player for the following person is not found');
            $this->logPerson($person, $competitionConfig);
            throw new \Exception('player not found', E_ERROR);
        }

        $player->setEndDateTime($stopAt);
        $this->playerRepos->save($player);
//        foreach ($viewPeriods as $viewPeriod) {
//            $s11Player = new S11PlayerBase($viewPeriod, $person);
//            $this->s11PlayerRepos->save($s11Player);
//        }

        $this->getLogger()->info('the person is now saved as:');
        $this->logPerson($person, $competitionConfig);

        // throw new \Exception('implement', E_ERROR);
        return 0;
    }

    protected function syncPerson(Person $person, CompetitionConfig $competitionConfig): void
    {
        $viewPeriods = $competitionConfig->getViewPeriods();
        foreach ($viewPeriods as $viewPeriod) {
            $this->s11PlayerSyncer->syncS11Player($viewPeriod, $person);
        }
    }

    protected function syncS11PlayerTotals(
        Person $person,
        CompetitionConfig $competitionConfig,
        Period|null $period = null
    ): void {
        $viewPeriods = $competitionConfig->getViewPeriods($period);
        foreach ($viewPeriods as $viewPeriod) {
            $s11Player = $this->s11PlayerRepos->findOneBy(
                ["viewPeriod" => $viewPeriod, "person" => $person]
            );
            if ($s11Player === null) {
                continue;
            }

            $totalsCalculator = new TotalsCalculator($competitionConfig);

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
        }
    }
}
