<?php

declare(strict_types=1);

namespace App\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Slim\Routing\RouteContext;
use App\Response\ForbiddenResponse as ForbiddenResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use SuperElf\Pool\User as PoolUser;

final class PoolUserMiddleware implements MiddlewareInterface
{
    /** @var EntityRepository<PoolUser>  */
    protected EntityRepository $poolUserRepos;

    public function __construct(
        protected EntityManagerInterface $entityManager)
    {
        $this->poolUserRepos = $this->entityManager->getRepository(PoolUser::class);
    }

    #[\Override]
    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getMethod() === "OPTIONS") {
            return $handler->handle($request);
        }

        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();

        $args = $routingResults->getRouteArguments();

        if (array_key_exists("poolUserId", $args) === false) {
            return $handler->handle($request);
        }

        $poolUser = $this->poolUserRepos->find((int)$args["poolUserId"]);
        if ($poolUser === null) {
            return new ForbiddenResponse("er kon geen deelnemer worden gevonden voor: " . $args["poolId"]);
        }

        return $handler->handle($request->withAttribute("poolUser", $poolUser));
    }
}
