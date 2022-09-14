<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Sports\Competition;
use Sports\Game\Against\Repository as AgainstGameRepository;
use Sports\Game\State;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\GameRound\Repository as GameRoundRepository;
use SuperElf\Periods\ViewPeriod;
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
    public function fetchCustom(Request $request, Response $response, array $args): Response
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

            $firstNotFinished = $this->getFirstNotFinished($competitionConfig->getSourceCompetition(), $viewPeriod);
            $firstInProgressOrFinished = $this->getFirstFinishedOrInProgress(
                $competitionConfig->getSourceCompetition(),
                $viewPeriod
            );

            $gameRoundNumbers = [
                'firstNotFinished' => $firstNotFinished,
                'firstInProgressOrFinished' => $firstInProgressOrFinished
            ];

            $json = $this->serializer->serialize($gameRoundNumbers, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    protected function getFirstNotFinished(Competition $competition, ViewPeriod $viewPeriod): int|null
    {
        $gameRoundNumbers = $this->againstGameRepos->getCompetitionGameRoundNumbers(
            $competition,
            [State::Created, State::InProgress],
            $viewPeriod->getPeriod()
        );
        return array_shift($gameRoundNumbers);
    }

    protected function getFirstFinishedOrInProgress(Competition $competition, ViewPeriod $viewPeriod): int|null
    {
        $gameRoundNumbersWithFinishedGames = $this->againstGameRepos->getCompetitionGameRoundNumbers(
            $competition,
            [State::Finished],
            $viewPeriod->getPeriod()
        );
        if (count($gameRoundNumbersWithFinishedGames) === 0) {
            return null;
        }

        // start mapped created games
        $gameRoundNumbersWithCreatedGames = $this->againstGameRepos->getCompetitionGameRoundNumbers(
            $competition,
            [State::Created],
            $viewPeriod->getPeriod()
        );
        $mappedGameRoundNumbersWithCreatedGames = [];
        foreach ($gameRoundNumbersWithCreatedGames as $gameRoundNumberWithCreatedGames) {
            $mappedGameRoundNumbersWithCreatedGames[$gameRoundNumberWithCreatedGames] = true;
        }
        // end mapped created games

        foreach ($gameRoundNumbersWithFinishedGames as $gameRoundNumberWithFinishedGames) {
            if (!array_key_exists($gameRoundNumberWithFinishedGames, $mappedGameRoundNumbersWithCreatedGames)) {
                return $gameRoundNumberWithFinishedGames;
            }
        }
        return array_shift($gameRoundNumbersWithFinishedGames);
    }

//    protected function getSerializationContext(): SerializationContext
//    {
//        $serGroups = ['Default','players'];
//        return SerializationContext::create()->setGroups($serGroups);
//    }
}
