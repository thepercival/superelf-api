<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Memcached;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Repositories\AgainstGameRepository;
use SuperElf\CacheService;
use SuperElf\Pool;
use SuperElf\Repositories\CompetitionConfigRepository as CompetitionConfigRepository;
use SuperElf\Repositories\ViewPeriodRepository as ViewPeriodRepository;

final class PoolTotalsAction extends Action
{
    protected CacheService $cacheService;

    public function __construct(
        protected CompetitionConfigRepository $competitionConfigRepos,
        protected AgainstGameRepository $againstGameRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected Configuration $config,
        Memcached $memcached,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->cacheService = new CacheService($serializer, $memcached, $config->getString('namespace'));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchViewPeriod(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");

            $viewPeriod = $this->viewPeriodRepos->find((int)$args["viewPeriodId"]);
            if ($viewPeriod === null) {
                throw new \Exception('kan de periode niet vinden', E_ERROR);
            }

            $jsonTotals = $this->cacheService->getViewPeriodTotals($pool, $viewPeriod);
            return $this->respondWithJson($response, $jsonTotals);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    public function fetchGameRound(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");

            $viewPeriod = $this->viewPeriodRepos->find((int)$args["viewPeriodId"]);
            if ($viewPeriod === null) {
                throw new \Exception('kan de periode niet vinden', E_ERROR);
            }
            $gameRound = $viewPeriod->getGameRound((int)$args["gameRoundNr"]);
            if ($gameRound === null) {
                throw new \Exception('kan de wedstrijdronde niet vinden', E_ERROR);
            }

            $jsonTotals = $this->cacheService->getGameRoundTotals($pool, $gameRound);
            return $this->respondWithJson($response, $jsonTotals);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
