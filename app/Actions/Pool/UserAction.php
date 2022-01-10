<?php

declare(strict_types=1);

namespace App\Actions\Pool;

use App\Actions\Action;
use App\Response\ErrorResponse;
use App\Response\ForbiddenResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\User;

final class UserAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected PoolUserRepository $poolUserRepos
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
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");

            $poolUser = $this->poolUserRepos->find((int)$args['poolUserId']);
            if ($poolUser === null) {
                throw new \Exception("geen deelnemer met het opgegeven id gevonden", E_ERROR);
            }
            if ($poolUser->getPool() !== $pool) {
                return new ForbiddenResponse("de pool komt niet overeen met de pool van de deelnemer");
            }
            // alleen als je zelf admin bent
            return $this->fetchOneHelper($response, $poolUser, false);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchOneFromSession(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");
            /** @var User $user */
            $user = $request->getAttribute("user");

            $poolUser = $pool->getUser($user);
            if ($poolUser === null) {
                return new ForbiddenResponse("de deelnemer kan niet gevonden worden");
            }

            return $this->fetchOneHelper($response, $poolUser, true);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }

    public function fetchOneHelper(Response $response, PoolUser $poolUser, bool $self): Response
    {
        $serGroups = ['formations'];
        if ($self) {
            $serGroups[] = 'admin';
        }
        try {
            $json = $this->serializer->serialize(
                $poolUser,
                'json',
                $this->getSerializationContext($serGroups)
            );

            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
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
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");

            $json = $this->serializer->serialize(
                $pool->getUsers(),
                'json',
                $this->getSerializationContext(['admin'])
            );

            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function remove(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");

            $poolUser = $this->poolUserRepos->find((int)$args['poolUserId']);
            if ($poolUser === null) {
                throw new \Exception("geen deelnemer met het opgegeven id gevonden", E_ERROR);
            }
            if ($poolUser->getPool() !== $pool) {
                return new ForbiddenResponse("de pool komt niet overeen met de pool van de deelnemer");
            }

            $this->poolUserRepos->remove($poolUser);

            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }
}
