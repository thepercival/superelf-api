<?php

declare(strict_types=1);

namespace App\Middleware\Authorization\Pool;

use App\Response\ForbiddenResponse as ForbiddenResponse;
use SuperElf\Pool;
use SuperElf\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\AuthorizationMiddleware;
use SuperElf\Competitor;

abstract class AdminMiddleware extends AuthorizationMiddleware
{
    protected function isAuthorized(Request $request, User $user = null, Pool $pool = null)
    {
        if ($user === null) {
            throw new \Exception("je moet ingelogd zijn voor deze pool", E_ERROR);
        };
        if ($pool === null) {
            throw new \Exception("de pool is onbekend", E_ERROR);
        }
        $competitor = $pool->getCompetitor($user);
        if ($competitor === null) {
            throw new \Exception("je doet niet mee aan deze pool", E_ERROR);
        }

        $this->isPoolCompetitorAuthorized($request, $competitor);
    }

    abstract protected function isPoolCompetitorAuthorized(Request $request, Competitor $competitor);
}
