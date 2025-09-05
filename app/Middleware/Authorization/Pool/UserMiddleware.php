<?php

declare(strict_types=1);

namespace App\Middleware\Authorization\Pool;

use Psr\Http\Message\ServerRequestInterface as Request;
use SuperElf\Pool;
use SuperElf\User;
use App\Middleware\AuthorizationMiddleware;

final class UserMiddleware extends AuthorizationMiddleware
{
    #[\Override]
    protected function isAuthorized(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->getAttribute('user');
        if ($user === null) {
            throw new \Exception("je moet ingelogd 2 zijn voor deze pool", E_ERROR);
        }
        /** @var Pool|null $pool */
        $pool = $request->getAttribute('pool');
        if ($pool === null) {
            throw new \Exception("de pool is onbekend", E_ERROR);
        }
        $poolUser = $pool->getUser($user);
        if ($poolUser === null) {
            throw new \Exception("je doet niet mee aan deze pool", E_ERROR);
        }
    }
}
