<?php

declare(strict_types=1);

namespace App\Middleware\Authorization;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\AuthorizationMiddleware;

class PoolUserMiddleware extends AuthorizationMiddleware
{
    protected function isAuthorized(Request $request )
    {
        $poolUser = $request->getAttribute('poolUser');
        if ($poolUser === null) {
            throw new \Exception("de deelnemer kan niet gevonden worden", E_ERROR);
        };
    }
}
