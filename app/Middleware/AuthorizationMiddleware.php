<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Response\ForbiddenResponse as ForbiddenResponse;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

abstract class AuthorizationMiddleware implements MiddlewareInterface
{
    #[\Override]
    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getMethod() === "OPTIONS") {
            return $handler->handle($request);
        }
        try {
            $this->isAuthorized($request);
        } catch (Exception $e) {
            return new ForbiddenResponse($e->getMessage());
        }
        return $handler->handle($request);
    }

    abstract protected function isAuthorized(Request $request): void;
}
