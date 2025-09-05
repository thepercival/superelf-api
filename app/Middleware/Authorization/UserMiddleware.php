<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use SuperElf\Pool;
use SuperElf\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\AuthorizationMiddleware;

final class UserMiddleware extends AuthorizationMiddleware
{
    #[\Override]
    protected function isAuthorized(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->getAttribute('user');
        if ($user === null) {
            throw new \Exception("je moet ingelogd zijn voor dit toernooi", E_ERROR);
        };
    }
}
