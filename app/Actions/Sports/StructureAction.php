<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Actions\Action;
use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Structure\Repository as StructureRepository;

final class StructureAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected CompetitionRepository $competitionRepos,
        protected StructureRepository $structureRepos
    ) {
        parent::__construct($logger, $serializer);
    }

    /**
     * @return list<string>
     */
    protected function getDeserialzeGroups(): array
    {
        return ['Default', 'structure'];
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
            if ($competition === null) {
                throw new \Exception('kan de competitie niet vinden', E_ERROR);
            }

            $structure = $this->structureRepos->getStructure($competition);
            // var_dump($structure); die();

            $serContext = $this->getSerializationContext(['structure', 'games']);
            $json = $this->serializer->serialize($structure, 'json', $serContext);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $exception) {
            return new ErrorResponse($exception->getMessage(), 500, $this->logger);
        }
    }
}
