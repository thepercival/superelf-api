<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Sports\Team\Repository as TeamRepository;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use SuperElf\Player\Filter as PlayerFilter;
use SuperElf\Player\Repository as PlayerRepository;

final class PlayerAction extends Action
{
    public function __construct(
        protected PlayerRepository $playerRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected TeamRepository $teamRepos,
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
    public function fetchOne(Request $request, Response $response, array $args): Response
    {
        try {
            $player = $this->playerRepos->find((int)$args["id"]);
            if ($player === null) {
                throw new \Exception('kan de speler met id "' . $args["id"] . '" niet vinden', E_ERROR);
            }

            $serContext = $this->getSerializationContext(['players', 'statistics']);
            $json = $this->serializer->serialize($player, 'json', $serContext);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $exception) {
            return new ErrorResponse($exception->getMessage(), 500, $this->logger);
        }
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
            /** @var PlayerFilter $playerFilter */
            $playerFilter = $this->serializer->deserialize($this->getRawData(), PlayerFilter::class, 'json');

            $viewPeriod = $this->viewPeriodRepos->find($playerFilter->getViewPeriodId());
            if ($viewPeriod === null) {
                throw new \Exception("de periode is niet meegegeven in het filter", E_ERROR);
            }
            $team = $playerFilter->getTeamId() !== null ? $this->teamRepos->find($playerFilter->getTeamId()) : null;
            $maxResults = null;
            if ($playerFilter->getLine() === null && $team === null) {
                $maxResults = 50;
            }


            $players = $this->playerRepos->findByExt($viewPeriod, $team, $playerFilter->getLine(), $maxResults);
            // aan de persons moeten punten gekoppeld worden en daarna pas vrijgegeven worden???

            $serContext = $this->getSerializationContext(['players']);
            $json = $this->serializer->serialize($players, 'json', $serContext);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
