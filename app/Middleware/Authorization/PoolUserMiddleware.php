<?php
declare(strict_types=1);

namespace App\Middleware\Authorization;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\AuthorizationMiddleware;
use SuperElf\User;
use SuperElf\Pool\User as PoolUser;

class PoolUserMiddleware extends AuthorizationMiddleware
{
    protected function isAuthorized(Request $request ): void
    {
        /** @var PoolUser|null $poolUser */
        $poolUser = $request->getAttribute('poolUser');
        if ($poolUser === null) {
            throw new \Exception("de deelnemer kan niet gevonden worden", E_ERROR);
        }
        /** @var User $user */
        $user = $request->getAttribute("user");

        if ($poolUser->getUser() !== $user ) {
            throw new \Exception("je mag alleen voor je eigen deelnemer iets wijzigen", E_ERROR);
        }
    }
}
