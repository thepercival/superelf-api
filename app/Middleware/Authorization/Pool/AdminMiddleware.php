<?php

declare(strict_types=1);

namespace App\Middleware\Authorization\Pool;

use App\Response\ForbiddenResponse as ForbiddenResponse;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Role;
use SuperElf\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\AuthorizationMiddleware;

class AdminMiddleware extends AuthorizationMiddleware
{
    protected function isAuthorized(Request $request, User $user = null, Pool $pool = null)
    {
        if ($user === null) {
            throw new \Exception("je moet ingelogd zijn voor deze pool", E_ERROR);
        };
        if ($pool === null) {
            throw new \Exception("de pool is onbekend", E_ERROR);
        }
        $poolUser = $pool->getUser($user);
        if ($poolUser === null) {
            throw new \Exception("je doet niet mee aan deze pool", E_ERROR);
        }
        if ($poolUser->getAdmin() === false) {
            throw new \Exception("je bent geen " . Role::getName(Role::ADMIN) . " voor deze pool", E_ERROR);
        };
    }
}
