<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Formation\Editor;

final class CompetitionConfigAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected CompetitionConfigRepository $competitionConfigRepos,
        protected Configuration $config,
        protected Editor $editor
    ) {
        parent::__construct($logger, $serializer);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchActive(Request $request, Response $response, array $args): Response
    {
        try {
            $competitionConfigs = $this->competitionConfigRepos->findActive();

            $json = $this->serializer->serialize($competitionConfigs, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function canCreateAndJoinPool(Request $request, Response $response, array $args): Response
    {
        try {
            $canCreateAndJoinPool = false;
            $now = new \DateTimeImmutable();

            $competitionConfigs = $this->competitionConfigRepos->findActive();
            foreach ($competitionConfigs as $competitionConfig) {
                if ($competitionConfig->getCreateAndJoinPeriod()->contains($now)) {
                    $canCreateAndJoinPool = true;
                    break;
                }
            }
            $json = $this->serializer->serialize($canCreateAndJoinPool, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchAvailableFormations(Request $request, Response $response, array $args): Response
    {
        try {
            $competitionConfig = $this->competitionConfigRepos->find((int)$args['competitionConfigId']);
            if ($competitionConfig === null) {
                throw new \Exception("er kan geen bron-competitie gevonden worden", E_ERROR);
            }

            $availableFormations = $this->editor->getAvailable();

            $json = $this->serializer->serialize($availableFormations, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }

}
