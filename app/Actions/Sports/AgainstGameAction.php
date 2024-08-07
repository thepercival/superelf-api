<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Actions\Action;
use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Competitor\StartLocationMap;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\State;
use Sports\Team\Player\Repository as TeamPlayerRepository;
use SportsHelpers\Against\Side as AgainstSide;
use SuperElf\Game\Against\EventConverter;
use SuperElf\LineupItem;

final class AgainstGameAction extends Action
{
    protected LineupItem\Converter $lineupConverter;
    protected EventConverter $eventConverter;

    public function __construct(
        protected CompetitionRepository $competitionRepos,
        protected AgainstGameRepository $againstGameRepos,
        protected TeamPlayerRepository $teamPlayerRepository,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->lineupConverter = new LineupItem\Converter($this->teamPlayerRepository);
        $this->eventConverter = new EventConverter($this->teamPlayerRepository);
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

//    protected function getSerializationContext(): SerializationContext
//    {
//        $serGroups = ['Default','players'];
//        return SerializationContext::create()->setGroups($serGroups);
//    }
}
