<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializationContext;
use Selective\Config\Configuration;
use App\Actions\Action;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use Sports\Competition\Repository as CompetitionRepository;

final class CompetitionAction extends Action
{
    protected CompetitionRepository $competitionRepos;
    protected Configuration $config;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        CompetitionRepository $competitionRepos,
        Configuration $config
    ) {
        parent::__construct($logger, $serializer);
        $this->competitionRepos = $competitionRepos;
        $this->config = $config;
    }

    public function fetchOne(Request $request, Response $response, $args): Response
    {
        try {
            $competition = $this->competitionRepos->find( (int)$args["competitionId"] );
            $json = $this->serializer->serialize(
                $competition,
                'json',
                $this->getSerializationContext()
            );
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }

    protected function getSerializationContext()
    {
        $serGroups = ['Default'];
        return SerializationContext::create()->setGroups($serGroups);
    }
}
