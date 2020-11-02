<?php

declare(strict_types=1);

use App\Actions\AuthAction;
use App\Actions\Pool\ShellAction;
use App\Actions\UserAction;
use App\Middleware\VersionMiddleware;
use App\Middleware\UserMiddleware;
use App\Middleware\Authorization\UserMiddleware as UserAuthMiddleware;
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
        '',
        function (Group $group): void {
            $group->options('/shellswithrole', ShellAction::class . ':options');
            $group->get('/shellswithrole', ShellAction::class . ':fetchWithRole');
            $group->options('/shells', ShellAction::class . ':options');
            $group->get('/shells', ShellAction::class . ':fetchPublic');
        }
    )->add(UserAuthMiddleware::class)->add(UserMiddleware::class)->add(VersionMiddleware::class);

};
