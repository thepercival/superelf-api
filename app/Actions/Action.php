<?php

declare(strict_types=1);

namespace App\Actions;

use App\Domain\DomainException\DomainRecordNotFoundException;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Action
{
    public function __construct(protected LoggerInterface $logger, protected SerializerInterface $serializer)
    {
    }

    /**
     * @param Request  $request
     * @param Response $response
     * @param array    $args
     * @return Response
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    // abstract public function __invoke(Request $request, Response $response, $args): Response;

//
//    /**
//     * @return Response
//     * @throws DomainRecordNotFoundException
//     * @throws HttpBadRequestException
//     */
//    abstract protected function fetchOne( Request $request, Response $response, $args ): Response;
//    abstract protected function fetch( Request $request, Response $response, $args ): Response;
//    abstract protected function add( Request $request, Response $response, $args ): Response;
//    abstract protected function edit( Request $request, Response $response, $args ): Response;
//    abstract protected function remove( Request $request, Response $response, $args ): Response;
//

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function options(Request $request, Response $response, $args): Response
    {
        return $response;
    }

    protected function getFormData(Request $request): mixed
    {
        /** @var mixed $input */
        $input = json_decode($this->getRawData());
        if ($input === null) {
            return new \stdClass();
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpBadRequestException($request, 'Malformed JSON input.');
        }

        return $input;
    }

    protected function getRawData(): string
    {
        $rawData = file_get_contents('php://input');
        if ($rawData === false) {
            throw new \Exception("de invoer is ongeldig", E_ERROR);
        }
        return $rawData;
    }

    /**
     * @param  Request $request
     * @param  array<string, int|string> $args
     * @param  string $name
     * @return string|int
     * @throws HttpBadRequestException
     */
    protected function resolveArg(Request $request, array $args, string $name): int|string
    {
        if (!isset($args[$name])) {
            throw new HttpBadRequestException($request, "Could not resolve argument `{$name}`.");
        }

        return $args[$name];
    }

    /**
     * @param string $json
     * @return Response
     */
    protected function respondWithJson(Response $response, string $json): Response
    {
        $response->getBody()->write($json);
        return $response->withHeader('Content-Type', 'application/json');
    }

    protected function respondWithPlainText(Response $response, string $text): Response
    {
        $response->getBody()->write($text);
        return $response->withHeader('Content-Type', 'text/plain');
    }

    /**
     * @param array<array-key, mixed> $groups
     * @return SerializationContext
     */
    protected function getSerializationContext(array $groups): SerializationContext
    {
        return SerializationContext::create()->setGroups(array_merge(['Default'], $groups));
    }
}
