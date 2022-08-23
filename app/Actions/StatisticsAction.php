<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use SuperElf\Player\Repository as PlayerRepository;
use SuperElf\Statistics\Repository as StatisticsRepository;

final class StatisticsAction extends Action
{
    public function __construct(
        protected PlayerRepository $playerRepos,
        protected StatisticsRepository $statisticsRepos,
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
            $s11Player = $this->playerRepos->find((int)$args['playerId']);
            if ($s11Player === null) {
                throw new \Exception('de speler kan niet gevonden worden', E_ERROR);
            }

            $statistics = $this->statisticsRepos->findBy(['player' => $s11Player]);

            $json = $this->serializer->serialize($statistics, 'json');
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
