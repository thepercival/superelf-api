<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use SuperElf\ActiveConfig\Service as ActiveConfigService;
use SuperElf\ActiveConfig;
use Selective\Config\Configuration;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;

use SuperElf\User;

final class ActiveConfigAction extends Action
{
    protected Configuration $config;
    protected ActiveConfigService $activeConfigService;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        ActiveConfigService $activeConfigService,
        Configuration $config
    ) {
        parent::__construct($logger, $serializer);
        $this->config = $config;
        $this->activeConfigService = $activeConfigService;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchOne(Request $request, Response $response, array $args): Response
    {
        try {
            $activeConfig = $this->activeConfigService->getConfig();
            $json = $this->serializer->serialize($activeConfig,'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }
}
