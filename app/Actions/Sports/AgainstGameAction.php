<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Actions\Action;
use Doctrine\ORM\EntityManagerInterface;
use Sports\Competitor\Team as TeamCompetitor;
use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Sports\Repositories\CompetitionRepository;
use Sports\Competitor;
use Sports\Competitor\StartLocationMap;
use Sports\Game\Against as AgainstGame;
use Sports\Repositories\AgainstGameRepository;
use Sports\Repositories\TeamPlayerRepository;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\State;
use SportsHelpers\Against\AgainstSide;
use SportsImport\Attachers\AgainstGameAttacher;
use SportsImport\ExternalSource\Factory as ExternalSourceFactory;
use SportsImport\ExternalSource\SofaScore;
use SportsImport\Repositories\AttacherRepository;
use SuperElf\Game\Against\EventConverter;
use SuperElf\LineupItem;

final class AgainstGameAction extends Action
{
    protected LineupItem\Converter $lineupConverter;
    protected EventConverter $eventConverter;
    /** @var AttacherRepository<AgainstGameAttacher>  */
    protected AttacherRepository $againstGameAttacherRepos;

    public function __construct(
        protected CompetitionRepository $competitionRepos,
        protected AgainstGameRepository $againstGameRepos,
        protected EntityManagerInterface $entityManager,
        protected TeamPlayerRepository $teamPlayerRepository,
        protected ExternalSourceFactory $externalSourceFactory,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->lineupConverter = new LineupItem\Converter($this->teamPlayerRepository);
        $this->eventConverter = new EventConverter($this->teamPlayerRepository);

        $metadata = $this->entityManager->getClassMetadata(AgainstGameAttacher::class);
        $this->againstGameAttacherRepos = new AttacherRepository($this->entityManager, $metadata);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetch(Request $request, Response $response, array $args): Response
    {
        try {
            $competition = $this->competitionRepos->find((int)$args["competitionId"]);
            if ($competition === null) {
                throw new \Exception('kan de competitie niet vinden', E_ERROR);
            }

            $games = $this->filterGames(
                $this->againstGameRepos->getCompetitionGames(
                    $competition,
                    null,
                    (int)$args["gameRoundNumber"]
                )
            );

            $json = $this->serializer->serialize($games, 'json'/*, $this->getSerializationContext()*/);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchLineup(Request $request, Response $response, array $args): Response
    {
        try {
            $competition = $this->competitionRepos->find((int)$args["competitionId"]);
            if ($competition === null) {
                throw new \Exception('kan de competitie niet vinden', E_ERROR);
            }
            $game = $this->againstGameRepos->find((int)$args["gameId"]);
            if ($game === null) {
                throw new \Exception('kan de wedstrijd niet vinden', E_ERROR);
            }

            $againstSide = AgainstSide::from((string)$args["side"]);

            $lineupItems = $this->lineupConverter->convert($game->getSingleSidePlace($againstSide));

            $json = $this->serializer->serialize($lineupItems, 'json', $this->getSerializationContext(['person']));
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchEvents(Request $request, Response $response, array $args): Response
    {
        try {
            $competition = $this->competitionRepos->find((int)$args["competitionId"]);
            if ($competition === null) {
                throw new \Exception('kan de competitie niet vinden', E_ERROR);
            }
            $game = $this->againstGameRepos->find((int)$args["gameId"]);
            if ($game === null) {
                throw new \Exception('kan de wedstrijd niet vinden', E_ERROR);
            }

            $againstSide = AgainstSide::from((string)$args["side"]);
            $gamePlace = $game->getSingleSidePlace($againstSide);
            $events = $this->eventConverter->convert($gamePlace);

            $json = $this->serializer->serialize($events, 'json', $this->getSerializationContext(['person']));
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    /**
     * @param list<AgainstGame> $games
     * @return array
     */
    protected function filterGames(array $games): array
    {
        $notCanceledGames = array_filter($games, fn(AgainstGame $game) => $game->getState() !== State::Canceled);
        return array_values(
            array_filter($games, function (AgainstGame $game) use ($notCanceledGames): bool {
                if ($game->getState() !== State::Canceled) {
                    return true;
                }
                $sameNotCanceledGames = array_filter(
                    $notCanceledGames,
                    function (AgainstGame $canceledGame) use ($game): bool {
                        return $this->hasSameTeams($canceledGame, $game);
                    }
                );
                return count($sameNotCanceledGames) === 0;
            })
        );
    }

    protected function hasSameTeams(AgainstGame $gameA, AgainstGame $gameB): bool
    {
        foreach ([AgainstSide::Home, AgainstSide::Away] as $side) {
            $sidePlacesGameB = array_map(
                fn(AgainstGamePlace $sidePlaceGameB) => $sidePlaceGameB->getPlace(),
                $gameB->getSidePlaces($side)
            );
            foreach ($gameA->getSidePlaces($side) as $sidePlacesGameA) {
                $sidePlaceGameA = $sidePlacesGameA->getPlace();
                $idx = array_search($sidePlaceGameA, $sidePlacesGameB, true);
                if ($idx === false) {
                    return false;
                }
                array_splice($sidePlacesGameB, $idx, 1);
            }
            if (count($sidePlacesGameB) > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchSofaScoreLink(Request $request, Response $response, array $args): Response
    {
        try {
            $competition = $this->competitionRepos->find((int)$args["competitionId"]);
            if ($competition === null) {
                throw new \Exception('kan de competitie niet vinden', E_ERROR);
            }
            $game = $this->againstGameRepos->find((int)$args["gameId"]);
            if ($game === null) {
                throw new \Exception('kan de wedstrijd niet vinden', E_ERROR);
            }

            $externalSource = $this->externalSourceFactory->createByName(SofaScore::NAME);
            if( $externalSource === null ) {
                throw new \Exception('kan de externalSource niet vinden', E_ERROR);
            }
            $externalId = $this->againstGameAttacherRepos->findOneByImportable($externalSource->getExternalSource(), $game)?->getExternalId();
            if( $externalId === null || strlen($externalId) === 0 ) {
                throw new \Exception('kan de externalId niet vinden', E_ERROR);
            }

            $teamCompetitors = $competition->getTeamCompetitors()->toArray();
            $competitors = array_map(function(TeamCompetitor $teamCompetitor): Competitor {
                return $teamCompetitor;
            }, $teamCompetitors);
            $startLocationMap = new StartLocationMap(array_values($competitors));


            $homeCompetitor = null;
            {
                $homePlace = $game->getSingleSidePlace(AgainstSide::Home)->getPlace();
                $homeStartLocation = $homePlace->getStartLocation();
                if ($homeStartLocation !== null) {
                    $homeCompetitor = $startLocationMap->getCompetitor($homeStartLocation);
                }
            }
            $awayCompetitor = null;
            {
                $awayPlace = $game->getSingleSidePlace(AgainstSide::Away)->getPlace();
                $awayStartLocation = $awayPlace->getStartLocation();
                if ($awayStartLocation !== null) {
                    $awayCompetitor = $startLocationMap->getCompetitor($awayStartLocation);
                }
            }
            if( $homeCompetitor === null || $awayCompetitor === null ) {
                throw new \Exception('competitors could not be found', E_ERROR);
            }

//            $competitorsDescription = $homeCompetitor->getName() . '-' . $awayCompetitor->getName();
//            $competitorsDescription = strtolower(str_replace(" ", "-", $competitorsDescription));
            // $againstPlace->get
            // $link = "https://www.sofascore.com/football/match/pec-zwolle-feyenoord/" . $externalId;
//            $link = "https://www.sofascore.com/football/match/" . $competitorsDescription . "/" . $externalId;
            $encodedCompetitors = urlencode( $homeCompetitor->getName() . " " . $awayCompetitor->getName() );
            $link = "https://www.sofascore.com/api/v1/search/all?q=" . $encodedCompetitors . "&page=0";

            $arrLink = [
                "link" => $link
            ];

            $json = $this->serializer->serialize($arrLink, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
