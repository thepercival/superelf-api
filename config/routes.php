<?php

declare(strict_types=1);

use App\Actions\BetLineAction;
use App\Actions\LayBackAction;
use App\Actions\AuthAction;
use App\Actions\BookmakerAction;
use App\Actions\PoolAction;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use App\Actions\Sports\SportAction;
use App\Actions\Sports\AssociationAction;
use App\Actions\Sports\LeagueAction;
use App\Actions\Sports\SeasonAction;
use App\Actions\Sports\CompetitionAction;
use App\Actions\Sports\CompetitorAction;
use App\Actions\Sports\StructureAction;
use App\Actions\ExternalSourceAction;
use App\Actions\AttacherAction;

return function (App $app): void {
    $app->group('/public', function (Group $group): void {
        $group->group('/auth', function (Group $group): void {
            $group->options('/login', AuthAction::class . ':options');
            $group->post('/login', AuthAction::class . ':login');
        });
    });

    $app->group('/auth', function (Group $group): void {
        $group->options('/validatetoken', AuthAction::class . ':options');
        $group->post('/validatetoken', AuthAction::class . ':validateToken');
    });

//    $app->options('/betgames', BetGameAction::class . ':options');
//    $app->post('/betgames', BetGameAction::class . ':fetch');



//    $app->get('/laybacks', LayBackAction::class . ':fetch');


};
