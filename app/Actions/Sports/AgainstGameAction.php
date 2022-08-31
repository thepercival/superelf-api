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
use Sports\Game\Against as AgainstGame;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\Place\Against as AgainstGamePlace;
use Sports\Game\State;
use SportsHelpers\Against\Side;

final class AgainstGameAction extends Action
{
    public function __construct(
        protected CompetitionRepository $competitionRepos,
        protected AgainstGameRepository $againstGameRepos,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
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
                throw new \Exception('kan de $competitie niet vinden', E_ERROR);
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
        foreach ([Side::Home, Side::Away] as $side) {
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
