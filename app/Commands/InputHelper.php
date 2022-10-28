<?php

namespace App\Commands;

use App\Commands\CompetitionConfig as CompetitionConfigCommand;
use DateTimeImmutable;
use Exception;
use League\Period\Period;
use Psr\Container\ContainerInterface;
use Sports\Association;
use Sports\Association\Repository as AssociationRepository;
use Sports\Competition;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\League;
use Sports\League\Repository as LeagueRepository;
use Sports\Person;
use Sports\Person\Repository as PersonRepository;
use Sports\Season;
use Sports\Season\Repository as SeasonRepository;
use Sports\Sport;
use Sports\Sport\Repository as SportRepository;
use Sports\Team;
use Sports\Team\Repository as TeamRepository;
use SportsHelpers\SportRange;
use SuperElf\CompetitionConfig;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use Symfony\Component\Console\Input\InputInterface;

class InputHelper
{
    protected SportRepository $sportRepos;
    protected AssociationRepository $associationRepos;
    protected LeagueRepository $leagueRepos;
    protected TeamRepository $teamRepos;
    protected PersonRepository $personRepos;
    protected SeasonRepository $seasonRepos;
    protected CompetitionRepository $competitionRepos;
    protected CompetitionConfigRepository $competitionConfigRepos;


    public function __construct(ContainerInterface $container)
    {
        /** @var SportRepository $sportRepos */
        $sportRepos = $container->get(SportRepository::class);
        $this->sportRepos = $sportRepos;

        /** @var AssociationRepository $associationRepos */
        $associationRepos = $container->get(AssociationRepository::class);
        $this->associationRepos = $associationRepos;

        /** @var LeagueRepository $leagueRepos */
        $leagueRepos = $container->get(LeagueRepository::class);
        $this->leagueRepos = $leagueRepos;

        /** @var SeasonRepository $seasonRepos */
        $seasonRepos = $container->get(SeasonRepository::class);
        $this->seasonRepos = $seasonRepos;

        /** @var CompetitionRepository $competitionRepos */
        $competitionRepos = $container->get(CompetitionRepository::class);
        $this->competitionRepos = $competitionRepos;

        /** @var CompetitionConfigRepository $competitionConfigRepos */
        $competitionConfigRepos = $container->get(CompetitionConfigRepository::class);
        $this->competitionConfigRepos = $competitionConfigRepos;

        /** @var TeamRepository $teamRepos */
        $teamRepos = $container->get(TeamRepository::class);
        $this->teamRepos = $teamRepos;

        /** @var PersonRepository $personRepos */
        $personRepos = $container->get(PersonRepository::class);
        $this->personRepos = $personRepos;
    }

    public function getSportFromInput(InputInterface $input): Sport
    {
        $optionName = 'sport';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "' . $optionName . '"-option given', E_ERROR);
        }
        $sport = $this->sportRepos->findOneBy(["name" => $optionValue]);
        if ($sport === null) {
            throw new Exception("sport '" . $optionValue . "' not found", E_ERROR);
        }
        return $sport;
    }

    public function getAssociationFromInput(InputInterface $input): Association
    {
        $optionName = 'association';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "' . $optionName . '"-option given', E_ERROR);
        }
        $association = $this->associationRepos->findOneBy(["name" => $optionValue]);
        if ($association === null) {
            throw new Exception("association '" . $optionValue . "' not found", E_ERROR);
        }
        return $association;
    }

    public function getTeamFromInput(InputInterface $input): Team|null
    {
        $optionName = 'teamId';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            return null;
        }
        return $this->teamRepos->findOneBy(['id' => $optionValue]);
    }

    public function getPersonFromInput(InputInterface $input): Person|null
    {
        $optionName = 'personId';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            return null;
        }
        return $this->personRepos->findOneBy(['id' => $optionValue]);
    }

    public function getGameRoundNrRangeFromInput(InputInterface $input): SportRange|null
    {
        $rangeOption = (string)$input->getOption("gameRoundRange");
        if (strlen($rangeOption) === 0) {
            return null;
        }
        if (!str_contains($rangeOption, '-')) {
            throw new Exception('misformat gameRoundRange-option');
        }
        $minMax = explode('-', $rangeOption);
        return new SportRange((int)$minMax[0], (int)$minMax[1]);
    }

    public function getStringFromInput(
        InputInterface $input,
        string $optionName,
        string $fallBackValue = null
    ): string {
        /** @var string|null $optionValue */
        $optionValue = $input->getOption($optionName);
        if (is_string($optionValue)) {
            return $optionValue;
        }
        if ($fallBackValue === null) {
            throw new Exception('option "' . $optionName . '"  not found');
        }
        return $fallBackValue;
    }

    public function getIdFromInput(InputInterface $input, int|string $fallBackValue = null): int|string
    {
        $idOption = $input->getOption("id");
        if (is_int($idOption)) {
            return $idOption;
        }
        if (!is_string($idOption) || strlen($idOption) === 0) {
            if ($fallBackValue === null) {
                throw new Exception("id-option not found");
            }
            return $fallBackValue;
        }
        return $idOption;
    }

    public function getDateTimeFromInput(
        InputInterface $input,
        string $optionName,
        string $format = CompetitionConfigCommand::DateTimeFormat
    ): DateTimeImmutable {
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "' . $optionName . '"-option given', E_ERROR);
        }
        $dateTime = DateTimeImmutable::createFromFormat($format, $optionValue);
        if ($dateTime === false) {
            throw new Exception('invalid datetime "' . $optionName . '" given', E_ERROR);
        }
        return $dateTime;
    }

    public function getDateTimeOptionalFromInput(
        InputInterface $input,
        string $optionName,
        string $format = CompetitionConfigCommand::DateTimeFormat
    ): DateTimeImmutable|null {
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            return null;
        }
        $dateTime = DateTimeImmutable::createFromFormat($format, $optionValue);
        if ($dateTime === false) {
            return null;
        }
        return $dateTime;
    }

    public function getPeriodFromInput(InputInterface $input, string $optionName): Period
    {
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "' . $optionName . '"-option given', E_ERROR);
        }
        if (!str_contains($optionValue, '=>')) {
            throw new Exception('invalid "' . $optionName . '"-option given', E_ERROR);
        }
        $dateTimes = explode('=>', $optionValue);
        if (count($dateTimes) !== 2) {
            throw new Exception('invalid "' . $optionName . '"-option given', E_ERROR);
        }

        $start = DateTimeImmutable::createFromFormat(CompetitionConfigCommand::DateTimeFormat, $dateTimes[0]);
        if ($start === false) {
            throw new Exception('invalid "' . $optionName . '" given', E_ERROR);
        }
        $end = DateTimeImmutable::createFromFormat(CompetitionConfigCommand::DateTimeFormat, $dateTimes[1]);
        if ($end === false) {
            throw new Exception('invalid "' . $optionName . '" given', E_ERROR);
        }
        if ($start->getTimestamp() > $end->getTimestamp()) {
            throw new Exception('invalid "' . $optionName . '" given', E_ERROR);
        }
        return new Period($start, $end);
    }

    public function getCompetitionConfigFromInput(InputInterface $input): CompetitionConfig
    {
        $competition = $this->getCompetitionFromInput($input);
        if ($competition === null) {
            throw new \Exception('competition not found', E_ERROR);
        }
        $competitionConfig = $this->competitionConfigRepos->findOneBy(['sourceCompetition' => $competition]);
        if ($competitionConfig === null) {
            throw new \Exception('competitionConfig not found', E_ERROR);
        }
        return $competitionConfig;
    }

    public function getCompetitionFromInput(InputInterface $input): Competition|null
    {
        try {
            $league = $this->getLeagueFromInput($input);
            $season = $this->getSeasonFromInput($input);
            return $this->competitionRepos->findOneBy(
                ['league' => $league, 'season' => $season]
            );
        } catch (Exception $e) {
        }
        return null;
    }

    public function getLeagueFromInput(InputInterface $input): League
    {
        $optionName = 'league';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "' . $optionName . '"-option given', E_ERROR);
        }
        $league = $this->leagueRepos->findOneBy(["name" => $optionValue]);
        if ($league === null) {
            throw new Exception("league '" . $optionValue . "' not found", E_ERROR);
        }
        return $league;
    }

    public function getSeasonFromInput(InputInterface $input): Season
    {
        $optionName = 'season';
        $optionValue = $input->getOption($optionName);
        if (!is_string($optionValue) || strlen($optionValue) === 0) {
            throw new Exception('no "' . $optionName . '"-option given', E_ERROR);
        }
        $season = $this->seasonRepos->findOneBy(["name" => $optionValue]);
        if ($season === null) {
            throw new Exception("season '" . $optionValue . "' not found", E_ERROR);
        }
        return $season;
    }
}