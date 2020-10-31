<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use SuperElf\Pool;
use SuperElf\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\AuthorizationMiddleware;

class UserMiddleware extends AuthorizationMiddleware
{
    protected function isAuthorized(Request $request, User $user = null, Pool $pool = null)
    {
        if ($user === null) {
            throw new \Exception("je moet ingelogd zijn voor dit toernooi", E_ERROR);
        };
    }
}
