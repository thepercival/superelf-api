<?php

declare(strict_types=1);

namespace App\Middleware;

use Slim\Routing\RouteContext;
use SuperElf\Auth\Token as AuthToken;
use App\Response\ForbiddenResponse as ForbiddenResponse;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
    ) {
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getMethod() === "OPTIONS") {
            return $handler->handle($request);
        }

        $noAuthUrl = "/public";
        if (substr($request->getUri()->getPath(), 0, strlen($noAuthUrl)) === $noAuthUrl) {
            return $handler->handle($request);
        }

        /** @var AuthToken|null $token */
        $token = $request->getAttribute('token');
        if ($token === null) {
            return new ForbiddenResponse("lege of ongeldige token");
        }

        return $handler->handle($request);
    }
}
