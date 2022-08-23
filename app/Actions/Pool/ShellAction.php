<?php

declare(strict_types=1);

namespace App\Actions\Pool;

use App\Actions\Action;
use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\Shell;
use SuperElf\User;

final class ShellAction extends Action
{
    /**
     * @var PoolRepository
     */
    private $poolRepos;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PoolRepository $poolRepos
    ) {
        parent::__construct($logger, $serializer);
        $this->poolRepos = $poolRepos;
        $this->serializer = $serializer;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchPublic(Request $request, Response $response, array $args): Response
    {
        /** @var User $user */
        $user = $request->getAttribute("user");
        try {
            $queryParams = $request->getQueryParams();

            $name = null;
            if (array_key_exists("name", $queryParams)) {
                $nameParam = (string)$queryParams["name"];
                if (strlen($nameParam) > 0) {
                    $name = $nameParam;
                }
            }

            $shells = [];
            $poolsByDates = $this->poolRepos->findByFilter($name);
            foreach ($poolsByDates as $pool) {
                $shells[] = new Shell($pool, $user);
            }

            $json = $this->serializer->serialize($shells, 'json');
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
    public function fetchWithRole(Request $request, Response $response, array $args): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $roles = 0;
            if (array_key_exists("roles", $queryParams)) {
                $rolesParam = (string)$queryParams["roles"];
                if (strlen($rolesParam) > 0) {
                    $roles = (int)$rolesParam;
                }
            }

            $shells = [];
            /** @var User $user */
            $user = $request->getAttribute("user");
            $poolsByRole = $this->poolRepos->findByRoles($user, $roles);
            foreach ($poolsByRole as $pool) {
                $shells[] = new Shell($pool, $user);
            }
            $json = $this->serializer->serialize($shells, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
