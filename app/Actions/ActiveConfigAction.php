<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use SuperElf\ActiveConfig\Service as ActiveConfigService;

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
            $json = $this->serializer->serialize($activeConfig, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }
}
