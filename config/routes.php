<?php

declare(strict_types=1);

use App\Actions\ActiveConfigAction;
use App\Actions\AuthAction;
use App\Actions\Pool\ShellAction;
use App\Actions\Sports\CompetitionAction;
use App\Actions\ViewPeriodPersonAction;
use App\Actions\FormationAction;
use App\Actions\UserAction;
use App\Actions\PoolAction;
use App\Actions\Pool\UserAction as PoolUserAction;
use App\Actions\ScoutedPersonAction;
use App\Middleware\PoolMiddleware;
use App\Middleware\VersionMiddleware;
use App\Middleware\UserMiddleware;
use App\Middleware\PoolUserMiddleware;
use App\Middleware\Authorization\UserMiddleware as UserAuthMiddleware;
use App\Middleware\Authorization\Pool\AdminMiddleware as PoolAdminAuthMiddleware;
use App\Middleware\Authorization\Pool\UserMiddleware as UserThroughPoolAuthMiddleware;
use App\Middleware\Authorization\PoolUserMiddleware as PoolUserAuthMiddleware;

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app): void {
    $app->group('/public',
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
            $group->options('/shells', ShellAction::class . ':options');
            $group->get('/shells', ShellAction::class . ':fetchPublic')->add(VersionMiddleware::class);
    });

    $app->group('/auth',
        function (Group $group): void {
            $group->options('/extendtoken', AuthAction::class . ':options');
            $group->post('/extendtoken', AuthAction::class . ':extendToken');
            $group->options('/profile/{userId}', AuthAction::class . ':options');
            $group->put('/profile/{userId}', AuthAction::class . ':profile');
        }
    )->add(UserAuthMiddleware::class)->add(UserMiddleware::class)->add(VersionMiddleware::class);

    $app->options('/activeconfig', ActiveConfigAction::class . ':options');
    $app->get('/activeconfig', ActiveConfigAction::class . ':fetchOne')->add(VersionMiddleware::class);

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
                            $group->get('/session', PoolUserAction::class . ':fetchOneFromSession')->add(UserThroughPoolAuthMiddleware::class);

                            $group->options('', PoolUserAction::class . ':options');
                            $group->get('', PoolUserAction::class . ':fetch')->add(PoolAdminAuthMiddleware::class);
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
                    $group->post('', FormationAction::class . ':add');

                    // $group->get('/{formationId}', PoolUserAction::class . ':fetchOne')->add(PoolAdminAuthMiddleware::class);
                    $group->options('/{formationId}', FormationAction::class . ':options');
                    $group->delete('/{formationId}', FormationAction::class . ':remove');
                    $group->put('/{formationId}', FormationAction::class . ':edit');

//                    'poolusers/' + poolUserId + '/formations' + formationId + 'lines/' + lineNumber + '/person' { viewPeriodPerson }
//                    'poolusers/' + poolUserId + '/formations' + formationId + 'lines/' + lineNumber + '/substitute' { poolUserViewPeriodPerson }

                    $group->group(
                        '/{formationId}/lines/{lineNumber}/viewperiodpersons',
                        function (Group $group): void {
                            $group->options('', FormationAction::class . ':options');
                            $group->post('', FormationAction::class . ':addViewPeriodPerson');
                            $group->options('/{viewPeriodPersonId}', FormationAction::class . ':options');
                            $group->delete('/{viewPeriodPersonId}', FormationAction::class . ':removeViewPeriodPerson');
                        }
                    )->add(PoolUserAuthMiddleware::class);

                    $group->group(
                        '/{formationId}/lines/{lineNumber}/substitute',
                        function (Group $group): void {
                            $group->options('', FormationAction::class . ':options');
                            $group->post('', FormationAction::class . ':addSubstitute');
                            $group->options('/{substituteId}', FormationAction::class . ':options');
                            $group->delete('/{substituteId}', FormationAction::class . ':removeSubstitute');
                        }
                    )->add(PoolUserAuthMiddleware::class);
                }
            )->add(PoolUserAuthMiddleware::class);
        }
    )->add(UserAuthMiddleware::class)->add(PoolUserMiddleware::class)->add(UserMiddleware::class)->add(VersionMiddleware::class);

    $app->group(
        '/viewperiodpersons',
        function (Group $group): void {
            $group->options('', ViewPeriodPersonAction::class . ':options');
            $group->post('', ViewPeriodPersonAction::class . ':fetch');
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
            $group->get('', CompetitionAction::class . ':fetchOne')->add(PoolMiddleware::class);

            $group->group(
                '/scoutedpersons',
                function (Group $group): void {
                    $group->options('', ScoutedPersonAction::class . ':options');
                    $group->post('', ScoutedPersonAction::class . ':add');
                    $group->get('', ScoutedPersonAction::class . ':fetch');
                    $group->options('/{scoutedPersonId}', ScoutedPersonAction::class . ':options');
                    $group->put('/{scoutedPersonId}', ScoutedPersonAction::class . ':edit');
                    $group->delete('/{scoutedPersonId}', ScoutedPersonAction::class . ':remove');
                }
            )->add(UserAuthMiddleware::class)->add(UserMiddleware::class);
        },
    )->add(VersionMiddleware::class);



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
