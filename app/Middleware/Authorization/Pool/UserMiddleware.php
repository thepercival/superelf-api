<?php

declare(strict_types=1);

namespace App\Middleware\Authorization\Pool;

use Psr\Http\Message\ServerRequestInterface as Request;
use SuperElf\Pool;
use SuperElf\User;
use App\Middleware\AuthorizationMiddleware;

class UserMiddleware extends AuthorizationMiddleware
{
    protected function isAuthorized(Request $request): void
    {
        $user = $request->getAttribute('user');
        if ($user === null) {
            throw new \Exception("je moet ingelogd 2 zijn voor deze pool", E_ERROR);
        }
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
