<?php
declare(strict_types = 1);

namespace App\SportsImportHelpers;

use App\Repositories\SportsImport\AttacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sports\Association;
use Sports\Competition;
use Sports\Game\Against as AgainstGame;
use Sports\League;
use Sports\Season;
use Sports\Sport;
use SportsImport\Attachers\AssociationAttacher;
use SportsImport\Attachers\LeagueAttacher;
use SportsImport\Attachers\SeasonAttacher;
use SportsImport\Attachers\SportAttacher;
use SportsImport\ExternalSource;
use SportsImport\ExternalSource\ExternSourceCompetitionsInterface;

final class ExternalSourceGetter
{
//    protected ImportGameEvents|null $importGameEventsSender = null;

    /** @var AttacherRepository<SportAttacher> */
    protected AttacherRepository $sportAttacherRepos;
    /** @var AttacherRepository<SeasonAttacher> */
    protected AttacherRepository $seasonAttacherRepos;
    /** @var AttacherRepository<LeagueAttacher> */
    protected AttacherRepository $leagueAttacherRepos;
    /** @var AttacherRepository<AssociationAttacher> */
    protected AttacherRepository $associationAttacherRepos;

    public function __construct(
//        protected ImporterHelpers\Sport $sportImportService,
//        protected ImporterHelpers\Association $associationImportService,
//        protected ImporterHelpers\Season $seasonImportService,
//        protected ImporterHelpers\League $leagueImportService,
//        protected ImporterHelpers\Competition $competitionImportService,
//        protected ImporterHelpers\Team $teamImportService,
//        protected ImporterHelpers\TeamCompetitor $teamCompetitorImportService,
//        protected ImporterHelpers\Structure $structureImportService,
//        protected ImporterHelpers\Game\Against $againstGameImportService,
//        protected ImporterHelpers\Person $personImportService,
//        protected CompetitionRepository $competitionRepos,
//        protected AgainstGameRepository $againstGameRepos,
        EntityManagerInterface $entityManager,
//        protected LoggerInterface $logger
    )
    {
        $metaData = $entityManager->getClassMetadata(SportAttacher::class);
        $this->sportAttacherRepos = new AttacherRepository($entityManager, $metaData);

        $metaData = $entityManager->getClassMetadata(SeasonAttacher::class);
        $this->seasonAttacherRepos = new AttacherRepository($entityManager, $metaData);

        $metaData = $entityManager->getClassMetadata(AssociationAttacher::class);
        $this->associationAttacherRepos = new AttacherRepository($entityManager, $metaData);

        $metaData = $entityManager->getClassMetadata(LeagueAttacher::class);
        $this->leagueAttacherRepos = new AttacherRepository($entityManager, $metaData);
    }

//    public function setEventSender(ImportGameEvents $importGameEventsSender): void
//    {
////        $this->importGameEventsSender = $importGameEventsSender;
//    }

    public function getSport(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternalSource              $externalSource,
        Sport                       $sport
    ): Sport
    {

        $sportAttacher = $this->sportAttacherRepos->findOneByImportable(
            $externalSource,
            $sport
        );
        if (!($sportAttacher instanceof SportAttacher)) {
            throw new \Exception('for external source "' . $externalSource->getName() . '" and sport "' . $sport->getName() . '" there is no externalId', E_ERROR);
        }
        $externalSport = $externalSourceCompetitions->getSport($sportAttacher->getExternalId());
        if ($externalSport === null) {
            throw new \Exception('external source "' . $externalSource->getName() . '" could not find a sport for externalId "' . $sportAttacher->getExternalId() . '"', E_ERROR);
        }
        return $externalSport;
    }

    public function getAgainstGame(
        ExternalSource\ExternalSourceGamesAndPlayersInterface $externalSourceGamesAndPlayers,
        ExternalSource  $externalSource,
        Competition     $externalCompetition,
        string|int      $gameId,
        bool            $resetCache
    ): AgainstGame
    {
//        $gameAttacher = $this->againstGameAttacherRepos->findOneByExternalId(
//            $externalSource,
//            $gameId
//        );
//        if ($gameAttacher === null) {
//            // $againstGame replaced by $gameId
//            // $competition = $againstGame->getPoule()->getRound()->getNumber()->getCompetition();
        ////            $competition = $externalCompetition;
        ////            $competitors = array_values($competition->getTeamCompetitors()->toArray());
        ////            $competitorMap = new CompetitorMap($competitors);
        ////            $gameOutput = new AgainstGameOutput($competitorMap, $this->logger);
        ////            $gameOutput->output($againstGame, 'there is no externalId for external source "' . $externalSource->getName() .' and game');
//            throw new \Exception('there is no externalId for external source "' . $externalSource->getName() .'" and external gameid "' . (string)$againstGame->getId() . '"', E_ERROR);
//        }
        $externalGame = $externalSourceGamesAndPlayers->getAgainstGame($externalCompetition, $gameId, $resetCache);
        if ($externalGame === null) {
            throw new \Exception('externalSource "' . $externalSource->getName() . '" could not find a game for id "' . $gameId . '"', E_ERROR);
        }
        return $externalGame;
    }

    public function getSeason(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternalSource              $externalSource,
        Season                      $season
    ): Season
    {
        $seasonAttacher = $this->seasonAttacherRepos->findOneByImportable(
            $externalSource,
            $season
        );
        if ($seasonAttacher === null) {
            throw new \Exception("for external source \"" . $externalSource->getName() . "\" and season \"" . $season->getName() . "\" there is no externalId", E_ERROR);
        }
        $externalSeason = $externalSourceCompetitions->getSeason($seasonAttacher->getExternalId());
        if ($externalSeason === null) {
            throw new \Exception('external source "' . $externalSource->getName() . '" could not find a season for externalId "' . $seasonAttacher->getExternalId() . '"', E_ERROR);
        }
        return $externalSeason;
    }

    public function getAssociation(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternalSource              $externalSource,
        Sport                       $sport,
        Association                 $association
    ): Association
    {
        $externalSport = $this->getSport($externalSourceCompetitions, $externalSource, $sport);
        $associationAttacher = $this->associationAttacherRepos->findOneByImportable(
            $externalSource,
            $association
        );
        if ($associationAttacher === null) {
            throw new \Exception("for external source \"" . $externalSource->getName() . "\" and association \"" . $association->getName() . "\" there is no externalId", E_ERROR);
        }
        $externalAssociation = $externalSourceCompetitions->getAssociation($externalSport, $associationAttacher->getExternalId());
        if ($externalAssociation === null) {
            throw new \Exception("external source \"" . $externalSource->getName() . "\" could not find an externalId for \"" . $associationAttacher->getExternalId() . "\"", E_ERROR);
        }
        return $externalAssociation;
    }

    public function getLeague(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternalSource              $externalSource,
        Sport                       $sport,
        League                      $league
    ): League
    {
        $association = $league->getAssociation();
        $externalAssociation = $this->getAssociation($externalSourceCompetitions, $externalSource, $sport, $association);
        $leagueAttacher = $this->leagueAttacherRepos->findOneByImportable(
            $externalSource,
            $league
        );
        if ($leagueAttacher === null) {
            throw new \Exception('for external source "' . $externalSource->getName() . '" and league "' . $league->getName() . '" there is no externalId', E_ERROR);
        }
        $externalLeague = $externalSourceCompetitions->getLeague($externalAssociation, $leagueAttacher->getExternalId());
        if ($externalLeague === null) {
            throw new \Exception('external source "' . $externalSource->getName() . '" could not find a league for externalId "' . $leagueAttacher->getExternalId() . '"', E_ERROR);
        }
        return $externalLeague;
    }

    public function getCompetition(
        ExternSourceCompetitionsInterface $externalSourceCompetitions,
        ExternalSource              $externalSource,
        Sport                       $sport,
        League                      $league,
        Season                      $season
    ): Competition
    {
        $externalSport = $this->getSport($externalSourceCompetitions, $externalSource, $sport);
        $externalLeague = $this->getLeague($externalSourceCompetitions, $externalSource, $sport, $league);
        $externalSeason = $this->getSeason($externalSourceCompetitions, $externalSource, $season);
        $externalCompetition = $externalSourceCompetitions->getCompetition(
            $externalSport,
            $externalLeague,
            $externalSeason
        );
        if ($externalCompetition === null) {
            throw new \Exception("external source \"" . $externalSource->getName() . "\" could not find a competition for sport/league/season \"" . $externalSport->getName() . "\"/\"" . $externalLeague->getName() . "\"/\"" . $externalSeason->getName() . "\"", E_ERROR);
        }
        return $externalCompetition;
    }
}