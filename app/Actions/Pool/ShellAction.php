<?php

declare(strict_types=1);

namespace App\Actions\Pool;

use App\Actions\Action;
use App\Response\ErrorResponse;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Season\Repository as S11SeasonRepository;
use SuperElf\Pool\Shell;
use SuperElf\User;

final class ShellAction extends Action
{
    public function __construct(
        protected SerializerInterface $serializer,
        protected PoolRepository $poolRepos,
        protected S11SeasonRepository $s11SeasonRepos,
        LoggerInterface $logger
    ) {
        parent::__construct($logger, $serializer);

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchPublic(Request $request, Response $response, array $args): Response
    {
        try {
            $queryParams = $request->getQueryParams();

            $nrOfUsers = false;

            $name = null;
            if (array_key_exists("name", $queryParams)) {
                $nameParam = (string)$queryParams["name"];
                if (strlen($nameParam) > 0) {
                    $name = $nameParam;
                }
            }

            $startDateTime = null;
            $endDateTime = null;
            if (array_key_exists("seasonId", $queryParams)) {
                $nrOfUsers = true;
                $seasonIdParam = (string)$queryParams["seasonId"];
                if (strlen($seasonIdParam) > 0) {
                    $season = $this->s11SeasonRepos->find($seasonIdParam);
                    if( $season !== null ) {
                        $startDateTime = $season->getStartDateTime();
                        $endDateTime = $season->getEndDateTime();
                    }


                }
            }

            $context = $this->getSerializationContext( $nrOfUsers ?  ['nrOfUsers'] : [] );

            $shells = [];
            $poolsByDates = $this->poolRepos->findByFilter($name, $startDateTime, $endDateTime);
            foreach ($poolsByDates as $pool) {
                $shells[] = new Shell($pool, null, $nrOfUsers);
            }

            $json = $this->serializer->serialize($shells, 'json', $context);
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
