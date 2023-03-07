<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use App\Response\ForbiddenResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\Achievement\Trophy\Repository as TrophyRepository;
use SuperElf\Achievement\Badge\Repository as BadgeRepository;
use SuperElf\Pool\User as PoolUser;

final class AchievementAction extends Action
{
    public function __construct(
        protected PoolCollectionRepository $poolCollectionRepos,
        protected TrophyRepository $trophyRepos,
        protected BadgeRepository $badgeRepos,
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
    public function fetchPoolCollection(Request $request, Response $response, array $args): Response
    {
        try {
            $poolCollection = $this->poolCollectionRepos->find((int)$args["poolCollectionId"]);
            if ($poolCollection === null) {
                throw new \Exception('kan de poolcollectie met id "' . $args["poolCollectionId"] . '" niet vinden', E_ERROR);
            }

            $achievements = [];

            $badges = $this->badgeRepos->findByPoolCollection($poolCollection);
            $trophies = $this->trophyRepos->findByPoolCollection($poolCollection);
            if (count($trophies) > 0) {
                $achievements = array_merge($achievements, $trophies );
            }
            if (count($badges) > 0) {
                $achievements = array_merge($achievements, $badges );
            }

            $json = $this->serializer->serialize($achievements, 'json');
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
    public function fetchUnviewed(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            $achievements = [];

            $badges = $this->badgeRepos->findUnviewed($poolUser);
            $trophies = $this->trophyRepos->findUnviewed($poolUser);
            if (count($trophies) > 0) {
                $achievements = array_merge($achievements, $trophies );
            }
            if (count($badges) > 0) {
                $achievements = array_merge($achievements, $badges );
            }

            $json = $this->serializer->serialize($achievements, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
