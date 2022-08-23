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
use Sports\Game\Against\Repository as AgainstGameRepository;

final class AgainstGameAction extends Action
{
    public function __construct(
        protected CompetitionRepository $competitionRepos,
        protected AgainstGameRepository $againstGameRepos,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetch(Request $request, Response $response, array $args): Response
    {
        try {
            $competition = $this->competitionRepos->find((int)$args["competitionId"]);
            if ($competition === null) {
                throw new \Exception('kan de $competitie niet vinden', E_ERROR);
            }

            $games = $this->againstGameRepos->getCompetitionGames(
                $competition,
                null,
                (int)$args["gameRoundNumber"]
            );

            $json = $this->serializer->serialize($games, 'json'/*, $this->getSerializationContext()*/);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

//    protected function getSerializationContext(): SerializationContext
//    {
//        $serGroups = ['Default','players'];
//        return SerializationContext::create()->setGroups($serGroups);
//    }
}
