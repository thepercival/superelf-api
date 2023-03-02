<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use SuperElf\Player\Repository as PlayerRepository;
use SuperElf\Statistics\Repository as StatisticsRepository;
use SuperElf\Formation\Repository as S11FormationRepository;

final class StatisticsAction extends Action
{
    public function __construct(
        protected PlayerRepository $playerRepos,
        protected StatisticsRepository $statisticsRepos,
        protected S11FormationRepository $formationRepos,
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
    public function fetchPlayer(Request $request, Response $response, array $args): Response
    {
        try {
            $s11Player = $this->playerRepos->find((int)$args['playerId']);
            if ($s11Player === null) {
                throw new \Exception('de speler kan niet gevonden worden', E_ERROR);
            }

            $statistics = $this->statisticsRepos->findBy(['player' => $s11Player]);

            $serContext = SerializationContext::create()->setGroups(['Default','byPlayer']);

            $json = $this->serializer->serialize($statistics, 'json', $serContext);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchFormationGameRound(Request $request, Response $response, array $args): Response
    {
        try {
            // '/{formationId}/statistics/{gameRoundNumber}',
            $formation = $this->formationRepos->find((int)$args['formationId']);
            if ($formation === null) {
                throw new \Exception('de formatie kan niet gevonden worden', E_ERROR);
            }

            $statistics = $this->statisticsRepos->findByFormationGameRound($formation, (int)$args['gameRoundNr']);

            $serContext = SerializationContext::create()->setGroups(['Default','byGameRound']);

            $json = $this->serializer->serialize($statistics, 'json', $serContext);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
