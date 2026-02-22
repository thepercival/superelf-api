<?php

declare(strict_types=1);

use App\Handlers\DefaultErrorHandler as AppDefaultErrorHandler;
use App\Middleware\CorsMiddleware;
use App\Renderer\ErrorRenderer as AppErrorRenderer;
use App\Response\ErrorResponse;
use JimTools\JwtAuth\Exceptions\AuthorizationException;
use JimTools\JwtAuth\Middleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Slim\App;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;

// middleware is executed by LIFO
/** @psalm-suppress UnusedClosureParam */
/** @psalm-suppress ArgumentTypeCoercion */
return function (App $app) {
    $container = $app->getContainer();
    if ($container === null) {
        return;
    }
    /** @var Configuration $config */
    $config = $container->get(Configuration::class);

    $app->add(
        function (Request $request, RequestHandler $handler): Response {
            $response = $handler->handle($request);
            header_remove("X-Powered-By");
            return $response; // ->withoutHeader("X-Powered-By");
        }
    );
    $app->add(Middleware\JwtAuthentication::class);
    $app->add((new Middlewares\ContentType(['html', 'json']))->errorResponse());
    $app->add((new Middlewares\ContentType())->charsets(['UTF-8'])->errorResponse());
    $app->add((new Middlewares\ContentEncoding(['gzip', 'deflate'])));

    $app->add(new CorsMiddleware($config->getString("www.wwwurl")));

    // Add Routing Middleware
    $app->addRoutingMiddleware();

    /** @var LoggerInterface $logger */
    $logger = $container->get(LoggerInterface::class);
    $appDefaultErrorHandler = new AppDefaultErrorHandler(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        $logger
    );

//    // always last, so it is called first!
    // $errorMiddleware = $app->addErrorMiddleware($config->getString('environment') === "development", true, true);
    //    // always last, so it is called first!
    $errorMiddleware = $app->addErrorMiddleware(
        $config->getString('environment') === 'development',
        true,
        true,
        $logger
    );
    $appDefaultErrorHandler->forceContentType('plain/text');
    $appDefaultErrorHandler->registerErrorRenderer('plain/text', AppErrorRenderer::class);
    $errorMiddleware->setDefaultErrorHandler($appDefaultErrorHandler);
    $errorMiddleware->setErrorHandler(
        AuthorizationException::class,
        function (Request $request, Throwable $exception, bool $displayErrorDetails) use($logger):  ErrorResponse {
            if( $displayErrorDetails ) {
                $previous = $exception->getPrevious();
                if( $previous !== null ) {
                    return new ErrorResponse($previous->getMessage(), 405, $logger);
                }

            }
            return new ErrorResponse($exception->getMessage(), 405, $logger);
        });

    // Set the Not Found Handler
    /** @psalm-suppress UnusedClosureParam */
    $errorMiddleware->setErrorHandler(
        HttpNotFoundException::class,
        function (Request $request, Throwable $exception, bool $displayErrorDetails) use ($logger): ErrorResponse {
            return new ErrorResponse($exception->getMessage(), 404, $logger);
        }
    );

    // Set the Not Allowed Handler
    /** @psalm-suppress UnusedClosureParam */
    $errorMiddleware->setErrorHandler(
        HttpMethodNotAllowedException::class,
        function (Request $request, Throwable $exception, bool $displayErrorDetails) use ($logger): ErrorResponse {
            return new ErrorResponse($exception->getMessage(), 405, $logger);
        }
    );
};
