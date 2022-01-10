<?php

declare(strict_types=1);

namespace App\Actions;

use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use SuperElf\CompetitionConfig\Administrator as CompetitionConfigAdminstrator;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;

final class CompetitionConfigAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected CompetitionConfigRepository $competitionConfigRepository,
        protected CompetitionConfigAdminstrator $administrator,
        protected Configuration $config
    ) {
        parent::__construct($logger, $serializer);
    }

//    /**
//     * @param Request $request
//     * @param Response $response
//     * @param array<string, int|string> $args
//     * @return Response
//     */
//    public function fetchOne(Request $request, Response $response, array $args): Response
//    {
//        try {
//            $activeConfig = $this->activeConfigService->getConfig();
//            $json = $this->serializer->serialize($activeConfig, 'json');
//            return $this->respondWithJson($response, $json);
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 400);
//        }
//    }
}
