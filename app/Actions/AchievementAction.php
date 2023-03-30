<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use App\Response\ForbiddenResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use SuperElf\Achievement\Badge;
use SuperElf\Achievement\Trophy;
use SuperElf\Achievement\Unviewed\Trophy as UnviewedTrophy;
use SuperElf\Achievement\Unviewed\Badge as UnviewedBadge;
use SuperElf\Pool;
use SuperElf\PoolCollection\Repository as PoolCollectionRepository;
use SuperElf\Achievement\Trophy\Repository as TrophyRepository;
use SuperElf\Achievement\Badge\Repository as BadgeRepository;
use SuperElf\Achievement\Unviewed\Trophy\Repository as UnviewedTrophyRepository;
use SuperElf\Achievement\Unviewed\Badge\Repository as UnviewedBadgeRepository;
use SuperElf\Pool\User as PoolUser;
use SuperElf\User;

final class AchievementAction extends Action
{
    public function __construct(
        protected PoolCollectionRepository $poolCollectionRepos,
        protected TrophyRepository $trophyRepos,
        protected BadgeRepository $badgeRepos,
        protected UnviewedTrophyRepository $unviewedTrophyRepos,
        protected UnviewedBadgeRepository $unviewedBadgeRepos,
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

            $context = $this->getSerializationContext(['noReference']);
            $json = $this->serializer->serialize($achievements, 'json', $context);
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
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");
            /** @var User $user */
            $user = $request->getAttribute("user");
            $poolUser = $pool->getUser($user);

            $achievements = [];
            if( $poolUser !== null ) {
                $unviewedTrophies = $this->unviewedTrophyRepos->findByPoolUser($poolUser);
                if (count($unviewedTrophies) > 0) {
                    $trophies = array_map(function(UnviewedTrophy $unviewedTrophy): Trophy {
                        return $unviewedTrophy->getTrophy();
                    }, $unviewedTrophies );
                    $achievements = array_merge($achievements, $trophies );
                }
                $unviewedBadges = $this->unviewedBadgeRepos->findByPoolUser($poolUser);
                if (count($unviewedBadges) > 0) {
                    $badges = array_map(function(UnviewedBadge $unviewedBadge): Badge {
                        return $unviewedBadge->getBadge();
                    }, $unviewedBadges );
                    $achievements = array_merge($achievements, $badges );
                }
            }

            $context = $this->getSerializationContext(['noReference']);
            $json = $this->serializer->serialize($achievements, 'json', $context);
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
    public function viewAchievements(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            $unviewedTrophies = $this->unviewedTrophyRepos->findByPoolUser($poolUser);
            foreach( $unviewedTrophies as $unviewedTrophy ) {
//                if( $unviewedTrophy->getPoolUser()->getPool() !== $poolUser->getPool() ) {
//                    continue;
//                }
                $this->unviewedTrophyRepos->remove($unviewedTrophy, true);
            }

            $unviewedBadges = $this->unviewedBadgeRepos->findByPoolUser($poolUser);
            foreach( $unviewedBadges as $unviewedBadge ) {
//                if( $unviewBadge->getPoolUser()->getPool() !== $poolUser->getPool() ) {
//                    continue;
//                }
                $this->unviewedBadgeRepos->remove($unviewedBadge, true);
            }

            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
