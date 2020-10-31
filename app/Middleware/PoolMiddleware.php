<?php

declare(strict_types=1);

namespace App\Middleware;

use Slim\Routing\RouteContext;
use SuperElf\Pool\Repository as PoolRepository;
use App\Response\ForbiddenResponse as ForbiddenResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class PoolMiddleware implements MiddlewareInterface
{
    protected PoolRepository $poolRepos;

    public function __construct(
        PoolRepository $poolRepos
    ) {
        $this->poolRepos = $poolRepos;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getMethod() === "OPTIONS") {
            return $handler->handle($request);
        }

        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();

        $args = $routingResults->getRouteArguments();

        if (array_key_exists("poolId", $args) === false) {
            return $handler->handle($request);
        }

        $pool = $this->poolRepos->find((int)$args["poolId"]);
        if ($pool === null) {
            return new ForbiddenResponse("er kon geen pool worden gevonden voor: " . $args["poolId"]);
        }

        return $handler->handle($request->withAttribute("pool", $pool));
    }
}
