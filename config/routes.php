<?php

declare(strict_types=1);

use App\Actions\AuthAction;
use App\Actions\ChatMessageAction;
use App\Actions\CompetitionConfigAction;
use App\Actions\FormationAction;
use App\Actions\GameRoundAction;
use App\Actions\PlayerAction;
use App\Actions\Pool\ShellAction;
use App\Actions\Pool\UserAction as PoolUserAction;
use App\Actions\PoolAction;
use App\Actions\AchievementAction;
use App\Actions\ScoutedPlayerAction;
use App\Actions\SeasonAction;
use App\Actions\Sports\AgainstGameAction;
use App\Actions\Sports\CompetitionAction;
use App\Actions\Sports\StructureAction;
use App\Actions\PoolTotalsAction;
use App\Actions\StatisticsAction;
use App\Actions\UserAction;
use App\Actions\TransferPeriodActionsAction;
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
        '/users/{userId}',
        function (Group $group): void {
            $group->options('/shells', ShellAction::class . ':options');
            $group->get('/shells', ShellAction::class . ':fetchWithRole');
        }
    )->add(UserAuthMiddleware::class)->add(UserMiddleware::class);

    $app->group('/auth', function (Group $group): void {
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
        '/shells',
        function (Group $group): void {
            $group->options('/shells', ShellAction::class . ':options');
            $group->get('/shells', ShellAction::class . ':fetchPublic');
        }
    );

    // OLD
    $app->group(
        '/public',
        function (Group $group): void {

            $group->options('/poolActions', CompetitionConfigAction::class . ':options');
            $group->get('/poolActions', CompetitionConfigAction::class . ':poolActions')->add(
                VersionMiddleware::class
            );

            $group->options('/seasons', SeasonAction::class . ':options');
            $group->get('/seasons', SeasonAction::class . ':fetch')->add(VersionMiddleware::class);
            $group->options('/pools/{poolId}', PoolAction::class . ':options');
            $group->get('/pools/{poolId}', PoolAction::class . ':fetchOne')->add(PoolMiddleware::class);
            $group->options('/pools/worldcupid/{seasonId}', CompetitionConfigAction::class . ':options');
            $group->get('/pools/worldcupid/{seasonId}', CompetitionConfigAction::class . ':fetchWorldCupId');


            $group->group(
                '/competitionconfigs',
                function (Group $group): void {
                    $group->options('/active', CompetitionConfigAction::class . ':options');
                    $group->get('/active', CompetitionConfigAction::class . ':fetchActive');

                    $group->group(
                        '/{competitionConfigId}/',
                        function (Group $group): void {
                            $group->options('viewperiods/{viewPeriodId}/gamerounds', GameRoundAction::class . ':options');
                            $group->get('viewperiods/{viewPeriodId}/gamerounds', GameRoundAction::class . ':fetchShells');

                            $group->options('viewperiods/{viewPeriodId}/gamerounds/active', GameRoundAction::class . ':options');
                            $group->get('viewperiods/{viewPeriodId}/gamerounds/active', GameRoundAction::class . ':fetchActive');

                            $group->options('availableformations', CompetitionConfigAction::class . ':options');
                            $group->get('availableformations', CompetitionConfigAction::class . ':fetchAvailableFormations');
                        }
                    );
                }

            )->add(VersionMiddleware::class);

            $group->group(
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
                        '/sourcegames/{gameId}',
                        function (Group $group): void {
                            $group->group(
                                '/lineups/{side}',
                                function (Group $group): void {
                                    $group->options('', AgainstGameAction::class . ':options');
                                    $group->get('', AgainstGameAction::class . ':fetchLineup');
                                },
                            );
                            $group->group(
                                '/events/{side}',
                                function (Group $group): void {
                                    $group->options('', AgainstGameAction::class . ':options');
                                    $group->get('', AgainstGameAction::class . ':fetchEvents');
                                },
                            );
                            $group->group(
                                '/sofascore',
                                function (Group $group): void {
                                    $group->options('', AgainstGameAction::class . ':options');
                                    $group->get('', AgainstGameAction::class . ':fetchSofaScoreLink');
                                },
                            );

                        },
                    );
                    $group->options('/structure', StructureAction::class . ':options');
                    $group->get('/structure', StructureAction::class . ':fetchOne');
                    $group->options('/firstpouleid', StructureAction::class . ':options');
                    $group->get('/firstpouleid', StructureAction::class . ':fetchFirstPouleId');
                },
            )->add(VersionMiddleware::class);

            $group->group(
                '/pools',
                function (Group $group): void {
                    $group->group(
                        '/{poolId}/',
                        function (Group $group): void {
                            $group->group(
                                'users',
                                function (Group $group): void {
                                    $group->options('/{poolUserId}', PoolUserAction::class . ':options');
                                    $group->get('/{poolUserId}', PoolUserAction::class . ':fetchOne');
                                }
                            );
                            $group->group(
                                'viewperiods/{viewPeriodId}/',
                                function (Group $group): void {
                                    $group->options('totals', PoolTotalsAction::class . ':options');
                                    $group->get('totals', PoolTotalsAction::class . ':fetchViewPeriod');

                                    $group->options('gamerounds/{gameRoundNr}/totals', PoolTotalsAction::class . ':options');
                                    $group->get('gamerounds/{gameRoundNr}/totals', PoolTotalsAction::class . ':fetchGameRound');
                                }
                            );

                        },
                    );
                }
            )->add(PoolMiddleware::class)->add(VersionMiddleware::class);
        }
    );



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
        '/poolcollections',
        function (Group $group): void {

            $group->group(
                '/{poolCollectionId}/',
                function (Group $group): void {
                    $group->options('achievements', AchievementAction::class . ':options');
                    $group->get('achievements', AchievementAction::class . ':fetchPoolCollection');
                },
            );
        }
    )->add(VersionMiddleware::class);

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

                    $group->options('poules/{pouleId}/messages', ChatMessageAction::class . ':options');
                    $group->get('poules/{pouleId}/messages', ChatMessageAction::class . ':fetch')
                        ->add(UserThroughPoolAuthMiddleware::class);
                    $group->post('poules/{pouleId}/messages', ChatMessageAction::class . ':add')
                        ->add(UserThroughPoolAuthMiddleware::class);
                    $group->options('poules/{pouleId}/nrofunreadmessages', ChatMessageAction::class . ':options');
                    $group->get(
                        'poules/{pouleId}/nrofunreadmessages',
                        ChatMessageAction::class . ':fetchNrOfUnreadMessages'
                    );

                    $group->group(
                        'viewperiods/{viewPeriodId}',
                        function (Group $group): void {
                           $group->options('', FormationAction::class . ':options');
                           $group->get('', FormationAction::class . ':fetch');
                        }
                    );

                    $group->options('achievements/unviewed', AchievementAction::class . ':options');
                    $group->get('achievements/unviewed', AchievementAction::class . ':fetchUnviewed');
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
            // $group->put('/{poolUserId}', PoolUserAction::class . ':edit')->add(PoolAdminAuthMiddleware::class);

            $group->group(
                '/{poolUserId}/viewperiods',
                function (Group $group): void {
                    $group->options('/{viewPeriodId}', FormationAction::class . ':options');
                    $group->get('/{viewPeriodId}', FormationAction::class . ':fetchOne');
                }
            );

            $group->group(
                '/{poolUserId}/formations',
                function (Group $group): void {
                    $group->options('', FormationAction::class . ':options');
                    $group->put('', FormationAction::class . ':edit');
                    $group->options('/{formationId}/places/{placeId}', FormationAction::class . ':options');
                    $group->post('/{formationId}/places/{placeId}', FormationAction::class . ':editPlace');
                }
            )->add(PoolUserAuthMiddleware::class);

            $group->group(
                '/{poolUserId}/replace',
                function (Group $group): void {
                    $group->options('', TransferPeriodActionsAction::class . ':options');
                    $group->post('', TransferPeriodActionsAction::class . ':replace');

                    $group->group(
                        '/{id}',
                        function (Group $group): void {
                            $group->options('', TransferPeriodActionsAction::class . ':options');
                            $group->delete('', TransferPeriodActionsAction::class . ':removeReplacement');
                        }
                    );
                }
            )->add(PoolUserAuthMiddleware::class);

            $group->group(
                '/{poolUserId}/transfer',
                function (Group $group): void {
                    $group->options('', TransferPeriodActionsAction::class . ':options');
                    $group->post('', TransferPeriodActionsAction::class . ':transfer');

                    $group->group(
                        '/{id}',
                        function (Group $group): void {
                            $group->options('', TransferPeriodActionsAction::class . ':options');
                            $group->delete('', TransferPeriodActionsAction::class . ':removeTransfer');
                        }
                    );
                }
            )->add(PoolUserAuthMiddleware::class);

            $group->group(
                '/{poolUserId}/doubletransfer',
                function (Group $group): void {
                    $group->options('/{placeIdA}/{placeIdB}', TransferPeriodActionsAction::class . ':options');
                    $group->post('/{placeIdA}/{placeIdB}', TransferPeriodActionsAction::class . ':doubletransfer');
                }
            )->add(PoolUserAuthMiddleware::class);

            $group->group(
                '/{poolUserId}/substitute',
                function (Group $group): void {
                    $group->options('', TransferPeriodActionsAction::class . ':options');
                    $group->post('', TransferPeriodActionsAction::class . ':substitute');

                    $group->group(
                        '/{id}',
                        function (Group $group): void {
                            $group->options('', TransferPeriodActionsAction::class . ':options');
                            $group->delete('', TransferPeriodActionsAction::class . ':removeSubstitution');
                        }
                    );
                }
            )->add(PoolUserAuthMiddleware::class);

            $group->group(
                '/{poolUserId}/achievements',
                function (Group $group): void {
                    $group->options('/viewed', AchievementAction::class . ':options');
                    $group->delete('/viewed', AchievementAction::class . ':viewAchievements');

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
                    $group->get('', StatisticsAction::class . ':fetchPlayer');
                }
            );
        }
    )->add(VersionMiddleware::class);

    $app->group(
        '/formations',
        function (Group $group): void {
            $group->group(
                '/{formationId}/statistics/{gameRoundNr}',
                function (Group $group): void {
                    $group->options('', StatisticsAction::class . ':options');
                    $group->get('', StatisticsAction::class . ':fetchFormationGameRound');
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
};
