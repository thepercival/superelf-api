<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Actions\Action;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Sports\Repositories\PersonRepository;
use Sports\Team;

final class PersonAction extends Action
{
    /** @var EntityRepository<Team>  */
    protected EntityRepository $teamRepos;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected PersonRepository $personRepos,
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct($logger, $serializer);
        $this->teamRepos = $entityManager->getRepository(Team::class);
    }

    // findByExt needs Period, so convert $personFilter->getSourceCompetitionId to Period if needed
//    public function fetch(Request $request, Response $response, $args): Response
//    {
//        try {
//            /** @var S11PlayerFilter $personFilter */
//            $personFilter = $this->serializer->deserialize($this->getRawData(), S11PlayerFilter::class, 'json');
//            $maxResults = 50;
//            $team = $personFilter->getTeamId() !== null ? $this->teamRepos->find( $personFilter->getTeamId() ) : null;
//            $persons = $this->personRepos->findByExt( $personFilter->getPeriod(), $team, $personFilter->getLine(), $maxResults );
//
//            $json = $this->serializer->serialize($persons, 'json', $this->getSerializationContext() );
//            return $this->respondWithJson($response, $json);
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 422);
//        }
//    }

}
