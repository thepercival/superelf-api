<?php

declare(strict_types=1);

namespace App\Middleware\Authorization\Pool;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Middleware\Authorization\Pool\AdminMiddleware as AuthorizationTournamentAdminMiddleware;
use SuperElf\Competitor;

class UserMiddleware extends AuthorizationTournamentAdminMiddleware
{
    protected function isPoolCompetitorAuthorized(Request $request, Competitor $competitor)
    {
    }
}
