<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\State;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;

final class GameRoundAction extends Action
{
    public function __construct(
        protected CompetitionConfigRepository $competitionConfigRepos,
        protected AgainstGameRepository $againstGameRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected GameRoundRepository $gameRoundRepos,
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
    public function fetchFirstNotFinished(Request $request, Response $response, array $args): Response
    {
        try {
            $competitionConfig = $this->competitionConfigRepos->find((int)$args["competitionConfigId"]);
            if ($competitionConfig === null) {
                throw new \Exception('kan de competitieconfiguratie niet vinden', E_ERROR);
            }
            $viewPeriod = $this->viewPeriodRepos->find((int)$args["viewPeriodId"]);
            if ($viewPeriod === null) {
                throw new \Exception('kan de viewperiod niet vinden', E_ERROR);
            }

            $gameRound = null;
            $games = $this->againstGameRepos->getCompetitionGames(
                $competitionConfig->getSourceCompetition(),
                [State::Created, State::InProgress],
                null,
                $viewPeriod->getPeriod(),
                1
            );
            $game = array_shift($games);
            if ($game !== null) {
                $gameRound = $this->gameRoundRepos->findOneBy(
                    ['viewPeriod' => $viewPeriod, 'number' => $game->getGameRoundNumber()]
                );
            }

            $json = $this->serializer->serialize($gameRound, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

//    protected function getSerializationContext(): SerializationContext
//    {
//        $serGroups = ['Default','players'];
//        return SerializationContext::create()->setGroups($serGroups);
//    }
}
