<?php

declare(strict_types=1);

namespace App\Middleware\Authorization\Pool\Admin;

use App\Response\ForbiddenResponse as ForbiddenResponse;
use SuperElf\Pool;
use SuperElf\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\Authorization\Pool\AdminMiddleware as AuthorizationTournamentAdminMiddleware;
use SuperElf\Role;
use SuperElf\Competitor;

class AdminMiddleware extends AuthorizationTournamentAdminMiddleware
{
    protected function isPoolCompetitorAuthorized(Request $request, Competitor $competitor)
    {
        if ($competitor->getAdmin() === false) {
            throw new \Exception("je bent geen " . Role::getName(Role::ADMIN) . " voor deze pool", E_ERROR);
        };
    }
}
