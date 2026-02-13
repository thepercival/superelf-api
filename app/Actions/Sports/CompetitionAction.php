<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Actions\Action;
use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Repositories\CompetitionRepository;

final class CompetitionAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected CompetitionRepository $competitionRepos,
        protected Configuration $config
    ) {
        parent::__construct($logger, $serializer);
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
            $competition = $this->competitionRepos->find((int)$args["competitionId"]);
            $json = $this->serializer->serialize(
                $competition,
                'json',
                $this->getSerializationContext(['teamCompetitors','noReference'])
            );
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400, $this->logger);
        }
    }
}
