<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Actions\Action;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Sports\Repositories\TeamPlayerRepository;
use Sports\Team;

final class PlayerAction extends Action
{
    /** @var EntityRepository<Team>  */
    protected EntityRepository $teamRepos;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected TeamPlayerRepository $teamPlayerRepos,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($logger, $serializer);
        $this->teamRepos = $entityManager->getRepository(Team::class);
    }

    // findByExt needs Period, so convert $personFilter->getSourceCompetitionId to Period if needed
//    public function fetch(Request $request, Response $response, $args): Response
//    {
//        try {
//            /** @var CompetitionPersonFilter $personFilter */
//            $personFilter = $this->serializer->deserialize($this->getRawData(), CompetitionPersonFilter::class, 'json');
//            $maxResults = 50;
//            $team = $personFilter->getTeamId() !== null ? $this->teamRepos->find( $personFilter->getTeamId() ) : null;
//            $players = $this->playerRepos->findByExt( $personFilter->getSourceCompetitionId(), $team, $personFilter->getLine(), $maxResults );
//
//            $json = $this->serializer->serialize($players, 'json', $this->getSerializationContext() );
//            return $this->respondWithJson($response, $json);
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 422);
//        }
//    }

}
