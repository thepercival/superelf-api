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
use Sports\Competition\Repository as CompetitionRepository;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use Selective\Config\Configuration;

final class GameRoundAction extends Action
{

    public function __construct(
        protected CompetitionConfigRepository $competitionConfigRepos,
        protected CompetitionRepository $competitionRepos,
        protected AgainstGameRepository $againstGameRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected Configuration $config,
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
    public function fetchShells(Request $request, Response $response, array $args): Response
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

            $sourceCompetition = $competitionConfig->getSourceCompetition();
            $gameRoundShells = $this->viewPeriodRepos->findGameRoundShells($sourceCompetition, $viewPeriod);

            $json = $this->serializer->serialize($gameRoundShells, 'json');
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
    public function fetchActive(Request $request, Response $response, array $args): Response
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

            $sourceCompetition = $competitionConfig->getSourceCompetition();
            $gameRoundShells = $this->viewPeriodRepos->findGameRoundShells(
                $sourceCompetition, $viewPeriod, true /* order by date to return correct values */ );

            $firstCreatedOrInProgress = null;
            foreach( $gameRoundShells as $gameRoundShell) {
                if( $gameRoundShell->state === State::Created && $firstCreatedOrInProgress === null) {
                    $firstCreatedOrInProgress = $gameRoundShell;
                }
                if( $gameRoundShell->state === State::InProgress) {
                    if( $firstCreatedOrInProgress === null ) {
                        $firstCreatedOrInProgress = $gameRoundShell;
                    }
                }
            }

            $lastInProgress = null;
            $lastFinished = null;
            $reversedGameRoundShells = array_reverse($gameRoundShells);
            foreach( $reversedGameRoundShells as $gameRoundShell) {
                if( $gameRoundShell->state === State::Finished && $lastFinished === null) {
                    $lastFinished = $gameRoundShell; // 30
                }
                if( $gameRoundShell->state === State::InProgress && $lastInProgress === null) {
                    $lastInProgress = $gameRoundShell;
                }
            }
            $lastFinishedOrInProgress = $lastInProgress ?? $lastFinished;

            $gameRoundNumbers = [
                'firstCreatedOrInProgress' => $firstCreatedOrInProgress,
                'lastFinishedOrInProgress' => $lastFinishedOrInProgress
            ];

            $json = $this->serializer->serialize($gameRoundNumbers, 'json');
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
    public function isActive(Request $request, Response $response, array $args): Response
    {
        try {
            $competition = $this->competitionRepos->find((int)$args["competitionId"]);
            if ($competition === null) {
                throw new \Exception('kan de competitie niet vinden', E_ERROR);
            }
            $gameRoundNumber = (int)$args["gameRoundNumber"];

            $active = $this->againstGameRepos->hasCompetitionGames($competition,null, $gameRoundNumber);
            $leagueActive = [
                'active' => $active
            ];

            $json = $this->serializer->serialize($leagueActive, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400, $this->logger);
        }
    }


//    protected function getSerializationContext(): SerializationContext
//    {
//        $serGroups = ['Default','players'];
//        return SerializationContext::create()->setGroups($serGroups);
//    }
}
