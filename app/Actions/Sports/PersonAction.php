<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializationContext;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use Sports\Person\Repository as PersonRepository;
use Sports\Team\Repository as TeamRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Actions\Action;
use SuperElf\Filter;

final class PersonAction extends Action
{
    protected PersonRepository $personRepos;
    protected TeamRepository $teamRepos;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PersonRepository $personRepos,
        TeamRepository $teamRepos
    ) {
        parent::__construct($logger, $serializer);
        $this->personRepos = $personRepos;
        $this->teamRepos = $teamRepos;
    }

    // findByExt needs Period, so convert $personFilter->getSourceCompetitionId to Period if needed
//    public function fetch(Request $request, Response $response, $args): Response
//    {
//        try {
//            /** @var Filter $personFilter */
//            $personFilter = $this->serializer->deserialize($this->getRawData(), Filter::class, 'json');
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

    protected function getSerializationContext(): SerializationContext
    {
        $serGroups = ['Default','players'];
        return SerializationContext::create()->setGroups($serGroups);
    }
}
