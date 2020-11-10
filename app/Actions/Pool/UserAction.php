<?php

declare(strict_types=1);

namespace App\Actions\Pool;

use App\Response\ForbiddenResponse;
use JMS\Serializer\SerializerInterface;
use App\Response\ErrorResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SuperElf\Pool;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use App\Actions\Action;
use Psr\Log\LoggerInterface;

final class UserAction extends Action
{
    /**
     * @var PoolUserRepository
     */
    private $poolUserRepos;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PoolUserRepository $poolUserRepos
    ) {
        parent::__construct($logger, $serializer);
        $this->poolUserRepos = $poolUserRepos;
        $this->serializer = $serializer;
    }

    public function remove(Request $request, Response $response, $args): Response
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
