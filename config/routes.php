<?php

declare(strict_types=1);

use App\Actions\AuthAction;
use App\Actions\CompetitionConfigAction;
use App\Actions\FormationAction;
use App\Actions\GameRoundAction;
use App\Actions\PlayerAction;
use App\Actions\Pool\ShellAction;
use App\Actions\Pool\UserAction as PoolUserAction;
use App\Actions\PoolAction;
use App\Actions\ScoutedPlayerAction;
use App\Actions\Sports\AgainstGameAction;
use App\Actions\Sports\CompetitionAction;
use App\Actions\Sports\StructureAction;
use App\Actions\StatisticsAction;
use App\Actions\UserAction;
use App\Middleware\Authorization\Pool\AdminMiddleware as PoolAdminAuthMiddleware;
use App\Middleware\Authorization\Pool\UserMiddleware as UserThroughPoolAuthMiddleware;
use App\Middleware\Authorization\PoolUserMiddleware as PoolUserAuthMiddleware;
use App\Middleware\Authorization\UserMiddleware as UserAuthMiddleware;
use App\Middleware\PoolMiddleware;
use App\Middleware\PoolUserMiddleware;
use App\Middleware\UserMiddleware;
use App\Middleware\VersionMiddleware;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app): void {
    $app->group(
        '/public',
        function (Group $group): void {
            $group->group('/auth', function (Group $group): void {
                $group->options('/register', AuthAction::class . ':options');
                $group->post('/register', AuthAction::class . ':register');
                $group->options('/validate', AuthAction::class . ':options');
                $group->post('/validate', AuthAction::class . ':validate');
                $group->options('/login', AuthAction::class . ':options');
                $group->post('/login', AuthAction::class . ':login');
                $group->options('/passwordreset', AuthAction::class . ':options');
                $group->post('/passwordreset', AuthAction::class . ':passwordreset');
                $group->options('/passwordchange', AuthAction::class . ':options');
                $group->post('/passwordchange', AuthAction::class . ':passwordchange');
            });
            $group->options('/cancreateandjoinpool', CompetitionConfigAction::class . ':options');
            $group->get('/cancreateandjoinpool', CompetitionConfigAction::class . ':canCreateAndJoinPool')->add(
                VersionMiddleware::class
            );

            $group->options('/shells', ShellAction::class . ':options');
            $group->get('/shells', ShellAction::class . ':fetchPublic')->add(VersionMiddleware::class);
        }
    );

    $app->group(
        '/auth',
        function (Group $group): void {
            $group->options('/extendtoken', AuthAction::class . ':options');
            $group->post('/extendtoken', AuthAction::class . ':extendToken');
            $group->options('/profile/{userId}', AuthAction::class . ':options');
            $group->put('/profile/{userId}', AuthAction::class . ':profile');
        }
    )->add(UserAuthMiddleware::class)->add(UserMiddleware::class)->add(VersionMiddleware::class);

    $app->group(
        '/competitionconfigs',
        function (Group $group): void {
            $group->options('/active', CompetitionConfigAction::class . ':options');
            $group->get('/active', CompetitionConfigAction::class . ':fetchActive');

            $group->group(
                '/{competitionConfigId}/',
                function (Group $group): void {
                    $group->options('viewperiods/{viewPeriodId}/fetchcustom', GameRoundAction::class . ':options');
                    $group->get('viewperiods/{viewPeriodId}/fetchcustom', GameRoundAction::class . ':fetchCustom');

                    $group->options('availableformations', CompetitionConfigAction::class . ':options');
                    $group->get('availableformations', CompetitionConfigAction::class . ':fetchAvailableFormations');
                }
            );
        }

    )->add(VersionMiddleware::class);

    $app->group(
        '/users/{userId}',
        function (Group $group): void {
            $group->options('', UserAction::class . ':options');
            $group->get('', UserAction::class . ':fetchOne');
            $group->put('', UserAction::class . ':edit');
            $group->delete('', UserAction::class . ':remove');
        }
    )->add(UserAuthMiddleware::class)->add(UserMiddleware::class)->add(VersionMiddleware::class);

    $app->group(
        '/pools',
        function (Group $group): void {
            $group->options('', PoolAction::class . ':options');
            $group->post('', PoolAction::class . ':add')->add(UserAuthMiddleware::class);
            $group->options('/{poolId}', PoolAction::class . ':options');
            $group->get('/{poolId}', PoolAction::class . ':fetchOne');
//            $group->put('/{poolId}', PoolAction::class . ':edit')
//                ->add(PoolAdminAuthMiddleware::class)->add(UserMiddleware::class)->add(
//                    PoolMiddleware::class
//                );
//            $group->delete('/{poolId}', PoolAction::class . ':remove')
//                ->add(PoolAdminAuthMiddleware::class)->add(UserMiddleware::class)->add(
//                    PoolMiddleware::class
//                );

            $group->group(
                '/{poolId}/',
                function (Group $group): void {
                    $group->options('join', PoolAction::class . ':options');
                    $group->post('join', PoolAction::class . ':join')->add(UserAuthMiddleware::class);

                    $group->options('joinurl', PoolAction::class . ':options');
                    $group->get('joinurl', PoolAction::class . ':joinUrl')->add(PoolAdminAuthMiddleware::class);

                    $group->group(
                        'users',
                        function (Group $group): void {
                            $group->options('/session', PoolUserAction::class . ':options');
                            $group->get('/session', PoolUserAction::class . ':fetchOneFromSession')->add(
                                UserThroughPoolAuthMiddleware::class
                            );

                            $group->options('/{poolUserId}', PoolUserAction::class . ':options');
                            $group->get('/{poolUserId}', PoolUserAction::class . ':fetchOne')->add(
                                UserThroughPoolAuthMiddleware::class
                            );

                            $group->options('', PoolUserAction::class . ':options');
                            $group->get('', PoolUserAction::class . ':fetch');
                        }
                    );
                },
            );
        }
    )->add(PoolMiddleware::class)->add(UserMiddleware::class)->add(VersionMiddleware::class);

    $app->group(
        '/poolusers',
        function (Group $group): void {
            $group->get('/{poolUserId}', PoolUserAction::class . ':fetchOne')->add(PoolAdminAuthMiddleware::class);
            $group->options('/{poolUserId}', PoolUserAction::class . ':options');
            $group->delete('/{poolUserId}', PoolUserAction::class . ':remove')->add(PoolAdminAuthMiddleware::class);

            $group->group(
                '/{poolUserId}/formations',
                function (Group $group): void {
                    $group->options('', FormationAction::class . ':options');
                    $group->put('', FormationAction::class . ':edit');
                    $group->options('/{formationId}/places/{placeId}', FormationAction::class . ':options');
                    $group->post('/{formationId}/places/{placeId}', FormationAction::class . ':editPlace');

//                    $group->group(
//                        '/{formationId}/lines/{lineNumber}/substitute',
//                        function (Group $group): void {
//                            $group->options('', FormationAction::class . ':options');
//                            $group->post('', FormationAction::class . ':addSubstitute');
//                            $group->options('/{substituteId}', FormationAction::class . ':options');
//                            $group->delete('/{substituteId}', FormationAction::class . ':removeSubstitute');
//                        }
//                    )/*->add(PoolUserAuthMiddleware::class)*/;
                }
            )->add(PoolUserAuthMiddleware::class);
        }
    )->add(UserAuthMiddleware::class)->add(PoolUserMiddleware::class)->add(UserMiddleware::class)->add(VersionMiddleware::class);

    $app->group(
        '/players',
        function (Group $group): void {
            $group->options('', PlayerAction::class . ':options');
            $group->post('', PlayerAction::class . ':fetch');
            $group->options('/{id}', PlayerAction::class . ':options');
            $group->get('/{id}', PlayerAction::class . ':fetchOne');

            $group->group(
                '/{playerId}/statistics',
                function (Group $group): void {
                    $group->options('', StatisticsAction::class . ':options');
                    $group->get('', StatisticsAction::class . ':fetch');
                }
            );
        }
    )->add(VersionMiddleware::class);

    /*$app->group(
        '/players',
        function (Group $group): void {
            $group->options('', PlayerAction::class . ':options');
            $group->post('', PlayerAction::class . ':fetch');
        }
    )->add(VersionMiddleware::class);*/

    $app->group(
        '/competitions/{competitionId}',
        function (Group $group): void {
            $group->options('', CompetitionAction::class . ':options');
            $group->get('', CompetitionAction::class . ':fetchOne');

            $group->group(
                '/gamerounds/{gameRoundNumber}',
                function (Group $group): void {
                    $group->options('', AgainstGameAction::class . ':options');
                    $group->get('', AgainstGameAction::class . ':fetch');
                },
            );
            $group->group(
                '/structure',
                function (Group $group): void {
                    $group->options('', StructureAction::class . ':options');
                    $group->get('', StructureAction::class . ':fetchOne');
                },
            );
        },
    )->add(VersionMiddleware::class);

    $app->group(
        '/viewperiods/{viewPeriodId}/scoutedplayers',
        function (Group $group): void {
            $group->options('', ScoutedPlayerAction::class . ':options');
            $group->post('', ScoutedPlayerAction::class . ':add');
            $group->get('', ScoutedPlayerAction::class . ':fetch');
            $group->options('/{scoutedPlayerId}', ScoutedPlayerAction::class . ':options');
            $group->put('/{scoutedPlayerId}', ScoutedPlayerAction::class . ':edit');
            $group->delete('/{scoutedPlayerId}', ScoutedPlayerAction::class . ':remove');
        },
    )->add(UserAuthMiddleware::class)->add(UserMiddleware::class)->add(VersionMiddleware::class);

    $app->group(
        '',
        function (Group $group): void {
            $group->options('/shellswithrole', ShellAction::class . ':options');
            $group->get('/shellswithrole', ShellAction::class . ':fetchWithRole');
            $group->options('/shells', ShellAction::class . ':options');
            $group->get('/shells', ShellAction::class . ':fetchPublic');
        }
    )->add(UserAuthMiddleware::class)->add(UserMiddleware::class)->add(VersionMiddleware::class);
};
